<?php

    session_start();

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='p'){
            header("location: ../login.php");
            exit;
        }
    }else{
        header("location: ../login.php");
        exit;
    }

    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])){
        include("../connection.php");
        require_once(__DIR__ . "/../notification_helper.php");
        date_default_timezone_set('Asia/Jakarta');
        $id = intval($_GET["id"]);
        $cancel_reason = isset($_POST['cancel_reason']) ? trim($_POST['cancel_reason']) : '';

        // fetch appointment details and related patient/doctor/schedule info
        $sql = "SELECT appointment.*, patient.pemail, patient.pname, schedule.title AS s_title, schedule.scheduledate AS s_date, schedule.scheduletime AS s_time, doctor.docemail AS doc_email, doctor.docname AS doc_name
                FROM appointment
                LEFT JOIN patient ON appointment.pid = patient.pid
                LEFT JOIN schedule ON appointment.scheduleid = schedule.scheduleid
                LEFT JOIN doctor ON schedule.docid = doctor.docid
                WHERE appointment.appoid = ? LIMIT 1";
        $stmt = $database->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if(!$res || $res->num_rows==0){
            header("location: appointment.php?error=notfound");
            exit;
        }
        $row = $res->fetch_assoc();

        // verify current user is the owner (patient)
        $useremail = $_SESSION['user'];
        if(strtolower(trim($useremail)) !== strtolower(trim($row['pemail']))){
            // not the owner
            header("location: appointment.php?error=unauthorized");
            exit;
        }

        // VALIDASI: Cek apakah pembatalan dilakukan minimal 1 jam sebelum sesi dimulai
        $scheduleDate = $row['s_date'] ?? $row['req_scheduledate'];
        $scheduleTime = $row['s_time'] ?? $row['req_scheduletime'];
        
        if($scheduleDate && $scheduleTime) {
            $sessionDateTime = strtotime($scheduleDate . ' ' . $scheduleTime);
            $currentDateTime = time();
            $timeDifference = $sessionDateTime - $currentDateTime;
            
            // Jika kurang dari 1 jam (3600 detik), tolak pembatalan
            if($timeDifference < 3600 && $timeDifference > 0) {
                header("location: appointment.php?error=toolate");
                exit;
            }
        }

        // prepare template data
        $templateData = [
            'nama_pelanggan' => $row['pname'] ?? '',
            'kode_reservasi' => $row['apponum'] ?? '',
            'nama_layanan'   => $row['s_title'] ?? ($row['req_title'] ?? ''),
            'tanggal_reservasi' => $row['s_date'] ?? ($row['req_scheduledate'] ?? ''),
            'waktu_reservasi'   => $row['s_time'] ?? ($row['req_scheduletime'] ?? ''),
            'cancel_reason'     => $cancel_reason,
            'cancel_reason_section' => $cancel_reason ? ('<span style="color:#d9534f;"><b>Alasan pembatalan:</b> '.htmlspecialchars($cancel_reason).'</span><br>') : ''
        ];

        // Update appointment status to cancelled instead of deleting
        $upd = $database->prepare("UPDATE appointment SET status='cancelled', cancel_reason=?, cancel_by='patient' WHERE appoid = ?");
        $upd->bind_param("si", $cancel_reason, $id);
        $upd->execute();

        // notify patient (self) and doctor
        $notifMsg = "Reservasi (#".$row['apponum'].") dibatalkan oleh pasien. Alasan: ".$cancel_reason;
        notify_recipients($database, [$row['pemail']], $notifMsg, "/sskinv0.4.3/patient/appointment.php");
        if(function_exists('send_templated_emails_to_list')){
            send_templated_emails_to_list([$row['pemail']], 'cancelled', $templateData);
        }

        if(!empty($row['doc_email'])){
            $notifDoc = "Reservasi (#".$row['apponum'].") dibatalkan oleh pasien. Alasan: ".$cancel_reason;
            notify_recipients($database, [$row['doc_email']], $notifDoc, "/sskinv0.4.3/doctor/appointment.php");
            // optional: inform doctor via email
            if(function_exists('send_templated_emails_to_list')){
                // send a cancelled template to patient only (doctor may prefer plain notification). If desired, uncomment to send to doctor as well.
                // send_templated_emails_to_list([$row['doc_email']], 'cancelled', $templateData);
            }
        }

        header("location: appointment.php");
        exit;
    }

    // fallback GET (lama, redirect saja)
    if($_GET){
        include("../connection.php");
        require_once(__DIR__ . "/../notification_helper.php");
        $id = intval($_GET["id"]);

        // fetch appointment details and related patient/doctor/schedule info
        $sql = "SELECT appointment.*, patient.pemail, patient.pname, schedule.title AS s_title, schedule.scheduledate AS s_date, schedule.scheduletime AS s_time, doctor.docemail AS doc_email, doctor.docname AS doc_name
                FROM appointment
                LEFT JOIN patient ON appointment.pid = patient.pid
                LEFT JOIN schedule ON appointment.scheduleid = schedule.scheduleid
                LEFT JOIN doctor ON schedule.docid = doctor.docid
                WHERE appointment.appoid = ? LIMIT 1";
        $stmt = $database->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if(!$res || $res->num_rows==0){
            header("location: appointment.php");
            exit;
        }
        $row = $res->fetch_assoc();

        // verify current user is the owner (patient)
        $useremail = $_SESSION['user'];
        if(strtolower(trim($useremail)) !== strtolower(trim($row['pemail']))){
            // not the owner
            header("location: appointment.php");
            exit;
        }

        // prepare template data
        $templateData = [
            'nama_pelanggan' => $row['pname'] ?? '',
            'kode_reservasi' => $row['apponum'] ?? '',
            'nama_layanan'   => $row['s_title'] ?? ($row['req_title'] ?? ''),
            'tanggal_reservasi' => $row['s_date'] ?? ($row['req_scheduledate'] ?? ''),
            'waktu_reservasi'   => $row['s_time'] ?? ($row['req_scheduletime'] ?? '')
        ];

        // Update appointment status to cancelled instead of deleting (fallback GET method)
        $upd = $database->prepare("UPDATE appointment SET status='cancelled', cancel_by='patient' WHERE appoid = ?");
        $upd->bind_param("i", $id);
        $upd->execute();

        // notify patient (self) and doctor
        $notifMsg = "Reservasi (#".$row['apponum'].") dibatalkan oleh pasien.";
        notify_recipients($database, [$row['pemail']], $notifMsg, "/sskinv0.4.3/patient/appointment.php");
        if(function_exists('send_templated_emails_to_list')){
            send_templated_emails_to_list([$row['pemail']], 'cancelled', $templateData);
        }

        if(!empty($row['doc_email'])){
            $notifDoc = "Reservasi (#".$row['apponum'].") dibatalkan oleh pasien.";
            notify_recipients($database, [$row['doc_email']], $notifDoc, "/sskinv0.4.3/doctor/appointment.php");
            // optional: inform doctor via email
            if(function_exists('send_templated_emails_to_list')){
                // send a cancelled template to patient only (doctor may prefer plain notification). If desired, uncomment to send to doctor as well.
                // send_templated_emails_to_list([$row['doc_email']], 'cancelled', $templateData);
            }
        }

        header("location: appointment.php");
        exit;
    }


?>
