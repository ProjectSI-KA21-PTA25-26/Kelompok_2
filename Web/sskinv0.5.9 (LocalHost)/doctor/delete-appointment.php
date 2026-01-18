<?php

    session_start();

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='d'){
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

        // fetch appointment and related info
        $sql = "SELECT appointment.*, patient.pemail, patient.pname, schedule.title AS s_title, schedule.scheduledate AS s_date, schedule.scheduletime AS s_time, schedule.docid AS sched_docid
                FROM appointment
                LEFT JOIN patient ON appointment.pid = patient.pid
                LEFT JOIN schedule ON appointment.scheduleid = schedule.scheduleid
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

        // verify doctor owns this appointment
        $useremail = $_SESSION['user'];
        $docRes = $database->prepare("SELECT docid FROM doctor WHERE docemail = ? LIMIT 1");
        $docRes->bind_param("s", $useremail);
        $docRes->execute();
        $dres = $docRes->get_result();
        $docid = null;
        if($dres && $dres->num_rows>0) $docid = intval($dres->fetch_assoc()['docid']);
        if($docid === null || intval($row['sched_docid']) !== $docid){
            header("location: appointment.php");
            exit;
        }

        // update appointment status to cancelled, simpan alasan
        $upd = $database->prepare("UPDATE appointment SET status='cancelled', cancel_reason=?, cancel_by='Dokter', cancelled_at=NOW() WHERE appoid = ?");
        $upd->bind_param("si", $cancel_reason, $id);
        $upd->execute();

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

        // notify patient
        $notifMsg = "Reservasi (#".$row['apponum'].") dibatalkan oleh dokter. Alasan: ".$cancel_reason;
        notify_recipients($database, [$row['pemail']], $notifMsg, "/sskinv0.4.3/patient/appointment.php");
        if(function_exists('send_templated_emails_to_list')){
            send_templated_emails_to_list([$row['pemail']], 'cancelled', $templateData);
        }

        header("location: appointment.php");
        exit;
    }

    // fallback GET lama (redirect saja)
    if($_GET){
        include("../connection.php");
        require_once(__DIR__ . "/../notification_helper.php");
        $id = intval($_GET["id"]);

        // fetch appointment and related info
        $sql = "SELECT appointment.*, patient.pemail, patient.pname, schedule.title AS s_title, schedule.scheduledate AS s_date, schedule.scheduletime AS s_time, schedule.docid AS sched_docid
                FROM appointment
                LEFT JOIN patient ON appointment.pid = patient.pid
                LEFT JOIN schedule ON appointment.scheduleid = schedule.scheduleid
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

        // verify doctor owns this appointment
        $useremail = $_SESSION['user'];
        // get docid for current user
        $docRes = $database->prepare("SELECT docid FROM doctor WHERE docemail = ? LIMIT 1");
        $docRes->bind_param("s", $useremail);
        $docRes->execute();
        $dres = $docRes->get_result();
        $docid = null;
        if($dres && $dres->num_rows>0) $docid = intval($dres->fetch_assoc()['docid']);

        if($docid === null || intval($row['sched_docid']) !== $docid){
            // not owner's appointment
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

        // Update appointment status to cancelled instead of deleting
        $upd = $database->prepare("UPDATE appointment SET status='cancelled', cancel_by='doctor' WHERE appoid = ?");
        $upd->bind_param("i", $id);
        $upd->execute();

        // notify patient
        $notifMsg = "Reservasi (#".$row['apponum'].") dibatalkan oleh dokter.";
        notify_recipients($database, [$row['pemail']], $notifMsg, "/sskinv0.4.3/patient/appointment.php");
        if(function_exists('send_templated_emails_to_list')){
            send_templated_emails_to_list([$row['pemail']], 'cancelled', $templateData);
        }

        header("location: appointment.php");
    }


?>
