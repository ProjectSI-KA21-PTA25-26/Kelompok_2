<?php
session_start();

if(isset($_SESSION["user"])){
    if(($_SESSION["user"])=="" or $_SESSION['usertype']!='a'){
        header("location: ../login.php");
        exit;
    }else{
        $useremail=$_SESSION["user"];
    }
} else {
    header("location: ../login.php");
    exit;
}

include("../connection.php");
require_once("../notification_helper.php");

if($_GET){
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    // verify appointment exists
    $sql = "select appointment.*, schedule.docid AS sched_docid, schedule.nop, patient.pemail, patient.pname from appointment LEFT JOIN schedule on appointment.scheduleid=schedule.scheduleid LEFT JOIN patient on appointment.pid=patient.pid where appointment.appoid=?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows==0){
        header("location: appointment.php");
        exit;
    }
    $row = $res->fetch_assoc();

    if($action=='confirm'){
        // ensure status column exists
        $colRes = $database->query("SHOW COLUMNS FROM appointment LIKE 'status'");
        if($colRes->num_rows==0){
            $database->query("ALTER TABLE appointment ADD COLUMN status VARCHAR(20) DEFAULT 'pending'");
        }

        // if this appointment is a request without schedule, create schedule first
        if(empty($row['scheduleid']) || $row['scheduleid']==0){
            // fetch requested fields
            $req_docid = intval($row['req_docid']);
            $req_title = $database->real_escape_string($row['req_title']);
            $req_date = $row['req_scheduledate'];
            $req_time = $row['req_scheduletime'];
            $req_nop = 1; // default; could be improved by storing nop in request

            // create schedule
            $insSched = $database->prepare("INSERT INTO schedule(docid,title,scheduledate,scheduletime,nop) VALUES (?,?,?,?,?)");
            $insSched->bind_param("isssi", $req_docid, $req_title, $req_date, $req_time, $req_nop);
            $insSched->execute();
            $newSchedId = $database->insert_id;

            // update appointment to attach schedule
            $updSched = $database->prepare("update appointment set scheduleid=? where appoid=?");
            $updSched->bind_param("ii", $newSchedId, $id);
            $updSched->execute();

            // reload row data for capacity check
            $rowCheck = $database->query("select nop from schedule where scheduleid=$newSchedId")->fetch_assoc();
            $nop = intval($rowCheck['nop']);
            $schedId = $newSchedId;
        } else {
            // check how many confirmed already for this schedule
            $schedId = intval($row['scheduleid']);
            $stmt2 = $database->prepare("select COUNT(*) as c from appointment where scheduleid=? and status='confirmed'");
            $stmt2->bind_param("i", $schedId);
            $stmt2->execute();
            $r2 = $stmt2->get_result()->fetch_assoc();
            $confirmedCount = intval($r2['c']);
            $nop = intval($row['nop']);
        }

        // now attempt to confirm
        $stmtCount = $database->prepare("select COUNT(*) as c from appointment where scheduleid=? and status='confirmed'");
        $stmtCount->bind_param("i", $schedId);
        $stmtCount->execute();
        $confirmedCount = $stmtCount->get_result()->fetch_assoc()['c'];
        if($confirmedCount < $nop){
            $upd = $database->prepare("update appointment set status='confirmed' where appoid=?");
            $upd->bind_param("i", $id);
            $upd->execute();
            
            // get patient info and send notifications
            $pemail = $row['pemail'];
            $pname = $row['pname'];

            // attempt to fetch service info
            $serviceTitle = 'Layanan';
            $serviceDate = '';
            $serviceTime = '';
            if (!empty($schedId)) {
                $q = $database->prepare("SELECT title, scheduledate, scheduletime FROM schedule WHERE scheduleid=? LIMIT 1");
                $q->bind_param("i", $schedId);
                $q->execute();
                $r = $q->get_result();
                if($r && $r->num_rows>0){
                    $s = $r->fetch_assoc();
                    $serviceTitle = $s['title'] ?? $serviceTitle;
                    $serviceDate  = $s['scheduledate'] ?? $serviceDate;
                    $serviceTime  = $s['scheduletime'] ?? $serviceTime;
                }
            } elseif (!empty($row['req_title'])) {
                $serviceTitle = $row['req_title'];
                $serviceDate = $row['req_scheduledate'];
                $serviceTime = $row['req_scheduletime'];
            }

            // notify patient
            $notifMsg = "Janji temu (#".$row['apponum'].") Anda telah dikonfirmasi oleh admin.";
            notify_recipients($database, [$pemail], $notifMsg, "/sskinv0.4.3/patient/appointment.php");
            
            // send templated confirmation email to patient
            $templateData = [
                'nama_pelanggan' => $pname,
                'kode_reservasi' => $row['apponum'],
                'nama_layanan'   => $serviceTitle,
                'tanggal_reservasi' => $serviceDate,
                'waktu_reservasi'   => $serviceTime
            ];
            if(function_exists('send_templated_emails_to_list')){
                send_templated_emails_to_list([$pemail], 'approved', $templateData);
            } else if(function_exists('send_emails_to_list')){
                $emailSubject = "Reservasi Disetujui";
                $emailBody = "Halo ".$pname.",\n\nReservasi Anda (#".$row['apponum'].") telah disetujui oleh admin. Silakan cek halaman My Bookings untuk detail.\n\nTerima kasih.";
                send_emails_to_list([$pemail], $emailSubject, $emailBody);
            }

            // notify doctor if exists
            if(!empty($row['req_docid'])){
                $docEmailRes = $database->query("select docemail from doctor where docid=".$row['req_docid']);
                if($docEmailRes && $docEmailRes->num_rows>0){
                    $docRow = $docEmailRes->fetch_assoc();
                    $docEmail = $docRow['docemail'];
                    $docNotifMsg = "Janji temu baru (#".$row['apponum'].") dikonfirmasi admin untuk Anda.";
                    notify_recipients($database, [$docEmail], $docNotifMsg, "/sskinv0.4.3/doctor/appointment.php");
                }
            }

            header("location: appointment.php?action=appointment-confirmed&apponum=".$row['apponum']);
            exit;
        }else{
            // cannot confirm, full
            $upd = $database->prepare("update appointment set status='rejected' where appoid=?");
            $upd->bind_param("i", $id);
            $upd->execute();
            
            // notify patient about rejection due to full
            $pemail = $row['pemail'];
            $pname = $row['pname'];
            $notifMsg = "Janji temu (#".$row['apponum'].") ditolak karena sesi sudah penuh.";
            notify_recipients($database, [$pemail], $notifMsg, "/sskinv0.4.3/patient/appointment.php");

            // send templated cancellation email to patient
            $templateData = [
                'nama_pelanggan' => $pname,
                'kode_reservasi' => $row['apponum'],
                'nama_layanan'   => '',
                'tanggal_reservasi' => '',
                'waktu_reservasi'   => ''
            ];
            if(function_exists('send_templated_emails_to_list')){
                send_templated_emails_to_list([$pemail], 'cancelled', $templateData);
            }

            header("location: appointment.php?action=appointment-rejected-full");
            exit;
        }

    }elseif($action=='reject'){
        // set rejected
        $colRes = $database->query("SHOW COLUMNS FROM appointment LIKE 'status'");
        if($colRes->num_rows==0){
            $database->query("ALTER TABLE appointment ADD COLUMN status VARCHAR(20) DEFAULT 'pending'");
        }
        $upd = $database->prepare("update appointment set status='rejected' where appoid=?");
        $upd->bind_param("i", $id);
        $upd->execute();
        
        // notify patient about rejection
        $pemail = $row['pemail'];
        $pname = $row['pname'];
        $notifMsg = "Janji temu (#".$row['apponum'].") Anda ditolak oleh admin.";
        notify_recipients($database, [$pemail], $notifMsg, "/sskinv0.4.3/patient/appointment.php");
        
        $templateData = [
            'nama_pelanggan' => $pname,
            'kode_reservasi' => $row['apponum'],
            'nama_layanan'   => '',
            'tanggal_reservasi' => '',
            'waktu_reservasi'   => ''
        ];
        if(function_exists('send_templated_emails_to_list')){
            send_templated_emails_to_list([$pemail], 'cancelled', $templateData);
        } else if(function_exists('send_emails_to_list')){
            $emailSubject = "Reservasi Ditolak";
            $emailBody = "Halo ".$pname.",\n\nReservasi Anda (#".$row['apponum'].") telah ditolak oleh admin. Silakan hubungi kami untuk informasi lebih lanjut.\n\nTerima kasih.";
            send_emails_to_list([$pemail], $emailSubject, $emailBody);
        }

        // notify doctor if exists
        if(!empty($row['req_docid'])){
            $docEmailRes = $database->query("select docemail from doctor where docid=".$row['req_docid']);
            if($docEmailRes && $docEmailRes->num_rows>0){
                $docRow = $docEmailRes->fetch_assoc();
                $docEmail = $docRow['docemail'];
                $docNotifMsg = "Janji temu (#".$row['apponum'].") ditolak oleh admin.";
                notify_recipients($database, [$docEmail], $docNotifMsg, "/sskinv0.4.3/doctor/appointment.php");
            }
        }

        header("location: appointment.php?action=appointment-rejected");
        exit;
    }
}

header("location: appointment.php");
exit;
