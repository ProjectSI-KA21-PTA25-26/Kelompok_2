<?php

    session_start();

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='a'){
            header("location: ../login.php");
        }

    }else{
        header("location: ../login.php");
    }
    
    
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])){
        include("../connection.php");
        require_once(__DIR__ . "/../notification_helper.php");
        $id = intval($_GET["id"]);
        $cancel_reason = isset($_POST['cancel_reason']) ? trim($_POST['cancel_reason']) : 'Dibatalkan oleh dokter';

        // Mark all related appointments as cancelled and send notifications/emails
        $stmtA = $database->prepare("SELECT appointment.appoid, appointment.apponum, patient.pemail, patient.pname, schedule.title, schedule.scheduledate, schedule.scheduletime FROM appointment LEFT JOIN patient ON appointment.pid=patient.pid LEFT JOIN schedule ON appointment.scheduleid = schedule.scheduleid WHERE appointment.scheduleid = ?");
        $stmtA->bind_param("i", $id);
        $stmtA->execute();
        $resA = $stmtA->get_result();
        if($resA && $resA->num_rows>0){
            while($row = $resA->fetch_assoc()){
                $templateData = [
                    'nama_pelanggan' => $row['pname'] ?? '',
                    'kode_reservasi' => $row['apponum'] ?? '',
                    'nama_layanan' => $row['title'] ?? '',
                    'tanggal_reservasi' => $row['scheduledate'] ?? '',
                    'waktu_reservasi' => $row['scheduletime'] ?? '',
                    'cancel_reason' => $cancel_reason,
                    'cancel_reason_section' => $cancel_reason ? ('<span style="color:#d9534f;"><b>Alasan pembatalan:</b> '.htmlspecialchars($cancel_reason).'</span><br>') : '',
                    'cancel_by' => 'Dokter',
                    'cancelled_at' => date('Y-m-d H:i:s')
                ];
                // Update appointment status
                $upd = $database->prepare("UPDATE appointment SET status='cancelled', cancel_reason=?, cancel_by='Dokter', cancelled_at=NOW() WHERE appoid = ?");
                $upd->bind_param("si", $cancel_reason, $row['appoid']);
                $upd->execute();
                // Send notification and email
                $notifMsg = "Reservasi (#".$row['apponum'].") dibatalkan oleh dokter. Alasan: ".$cancel_reason;
                notify_recipients($database, [$row['pemail']], $notifMsg, "/sskinv0.4.3/patient/appointment.php");
                if(function_exists('send_templated_emails_to_list')){
                    send_templated_emails_to_list([$row['pemail']], 'cancelled', $templateData);
                }
            }
        }
        // Delete the session
        $sql= $database->query("delete from schedule where scheduleid='$id';");
        header("location: schedule.php");
        exit;
    }

    // fallback GET lama (redirect saja)
    if($_GET){
        include("../connection.php");
        require_once(__DIR__ . "/../notification_helper.php");
        $id = intval($_GET["id"]);

        // Mark all related appointments as cancelled and send notifications/emails
        $stmtA = $database->prepare("SELECT appointment.appoid, appointment.apponum, patient.pemail, patient.pname, schedule.title, schedule.scheduledate, schedule.scheduletime FROM appointment LEFT JOIN patient ON appointment.pid=patient.pid LEFT JOIN schedule ON appointment.scheduleid = schedule.scheduleid WHERE appointment.scheduleid = ?");
        $stmtA->bind_param("i", $id);
        $stmtA->execute();
        $resA = $stmtA->get_result();
        if($resA && $resA->num_rows>0){
            while($row = $resA->fetch_assoc()){
                $templateData = [
                    'nama_pelanggan' => $row['pname'] ?? '',
                    'kode_reservasi' => $row['apponum'] ?? '',
                    'nama_layanan' => $row['title'] ?? '',
                    'tanggal_reservasi' => $row['scheduledate'] ?? '',
                    'waktu_reservasi' => $row['scheduletime'] ?? '',
                    'cancel_reason' => 'Dibatalkan oleh dokter',
                    'cancel_by' => 'Dokter',
                    'cancelled_at' => date('Y-m-d H:i:s')
                ];
                // Update appointment status
                $upd = $database->prepare("UPDATE appointment SET status='cancelled', cancel_reason=?, cancel_by=?, cancelled_at=NOW() WHERE appoid = ?");
                $reason = $templateData['cancel_reason'];
                $by = $templateData['cancel_by'];
                $upd->bind_param("ssi", $reason, $by, $row['appoid']);
                $upd->execute();
                // Send notification and email
                $notifMsg = "Reservasi (#".$row['apponum'].") dibatalkan oleh dokter.";
                notify_recipients($database, [$row['pemail']], $notifMsg, "/sskinv0.4.3/patient/appointment.php");
                if(function_exists('send_templated_emails_to_list')){
                    send_templated_emails_to_list([$row['pemail']], 'cancelled', $templateData);
                }
            }
        }
        // Delete the session
        $sql= $database->query("delete from schedule where scheduleid='$id';");
        header("location: schedule.php");
    }


?>
