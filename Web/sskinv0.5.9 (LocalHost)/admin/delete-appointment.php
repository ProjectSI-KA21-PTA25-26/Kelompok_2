<?php

    session_start();

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='a'){
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
        $id = intval($_GET["id"]);
        $cancel_reason = isset($_POST['cancel_reason']) ? trim($_POST['cancel_reason']) : '';

        $sql = "SELECT appointment.*, patient.pemail, patient.pname, schedule.title AS s_title, schedule.scheduledate AS s_date, schedule.scheduletime AS s_time, doctor.docemail AS doc_email
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

        // update appointment status to cancelled, simpan alasan
        $upd = $database->prepare("UPDATE appointment SET status='cancelled', cancel_reason=?, cancel_by='Admin', cancelled_at=NOW() WHERE appoid = ?");
        $upd->bind_param("si", $cancel_reason, $id);
        $upd->execute();

        $templateData = [
            'nama_pelanggan' => $row['pname'] ?? '',
            'kode_reservasi' => $row['apponum'] ?? '',
            'nama_layanan'   => $row['s_title'] ?? ($row['req_title'] ?? ''),
            'tanggal_reservasi' => $row['s_date'] ?? ($row['req_scheduledate'] ?? ''),
            'waktu_reservasi'   => $row['s_time'] ?? ($row['req_scheduletime'] ?? ''),
            'cancel_reason'     => $cancel_reason,
            'cancel_reason_section' => $cancel_reason ? ('<span style="color:#d9534f;"><b>Alasan pembatalan:</b> '.htmlspecialchars($cancel_reason).'</span><br>') : ''
        ];

        // notify patient & doctor
        $notifMsg = "Reservasi (#".$row['apponum'].") dibatalkan oleh admin. Alasan: ".$cancel_reason;
        notify_recipients($database, [$row['pemail']], $notifMsg, "/sskinv0.4.3/patient/appointment.php");
        if(function_exists('send_templated_emails_to_list')){
            send_templated_emails_to_list([$row['pemail']], 'cancelled', $templateData);
        }
        if(!empty($row['doc_email'])){
            notify_recipients($database, [$row['doc_email']], $notifMsg, "/sskinv0.4.3/doctor/appointment.php");
        }

        header("location: appointment.php");
        exit;
    }

    // fallback GET lama (redirect saja)
    if($_GET){
        include("../connection.php");
        require_once(__DIR__ . "/../notification_helper.php");
        $id = intval($_GET["id"]);

        $sql = "SELECT appointment.*, patient.pemail, patient.pname, schedule.title AS s_title, schedule.scheduledate AS s_date, schedule.scheduletime AS s_time, doctor.docemail AS doc_email
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

        $templateData = [
            'nama_pelanggan' => $row['pname'] ?? '',
            'kode_reservasi' => $row['apponum'] ?? '',
            'nama_layanan'   => $row['s_title'] ?? ($row['req_title'] ?? ''),
            'tanggal_reservasi' => $row['s_date'] ?? ($row['req_scheduledate'] ?? ''),
            'waktu_reservasi'   => $row['s_time'] ?? ($row['req_scheduletime'] ?? '')
        ];

        // Update appointment status to cancelled instead of deleting
        $upd = $database->prepare("UPDATE appointment SET status='cancelled', cancel_by='admin' WHERE appoid = ?");
        $upd->bind_param("i", $id);
        $upd->execute();

        // notify patient & doctor
        $notifMsg = "Reservasi (#".$row['apponum'].") dibatalkan oleh admin.";
        notify_recipients($database, [$row['pemail']], $notifMsg, "/sskinv0.4.3/patient/appointment.php");
        if(function_exists('send_templated_emails_to_list')){
            send_templated_emails_to_list([$row['pemail']], 'cancelled', $templateData);
        }
        if(!empty($row['doc_email'])){
            notify_recipients($database, [$row['doc_email']], $notifMsg, "/sskinv0.4.3/doctor/appointment.php");
        }

        header("location: appointment.php");
    }


?>
