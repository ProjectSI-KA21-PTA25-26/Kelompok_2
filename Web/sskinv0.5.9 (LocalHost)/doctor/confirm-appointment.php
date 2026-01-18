<?php
session_start();

if(isset($_SESSION["user"])){
    if(($_SESSION["user"])=="" or $_SESSION['usertype']!='d'){
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
// get doctor id
$userrow = $database->query("select * from doctor where docemail='$useremail'");
$userfetch=$userrow->fetch_assoc();
$docid = $userfetch["docid"];

if($_GET){
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    // verify appointment exists and belongs to this doctor either via schedule.docid or via appointment.req_docid
    $sql = "select appointment.*, schedule.docid AS sched_docid, schedule.nop from appointment LEFT JOIN schedule on appointment.scheduleid=schedule.scheduleid where appointment.appoid=?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows==0){
        header("location: appointment.php");
        exit;
    }
    $row = $res->fetch_assoc();
    $ownedByDoctor = false;
    if(isset($row['sched_docid']) && intval($row['sched_docid'])===intval($docid)) $ownedByDoctor = true;
    if(!$ownedByDoctor && isset($row['req_docid']) && intval($row['req_docid'])===intval($docid)) $ownedByDoctor = true;
    if(!$ownedByDoctor){
        // not this doctor's appointment/request
        header("location: appointment.php");
        exit;
    }

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
            // privacy-scoped notifications: patient + admin only
                $pemailRes = $database->query("select patient.pemail, patient.pname from appointment inner join patient on appointment.pid=patient.pid where appointment.appoid=$id");
                if($pemailRes && $pemailRes->num_rows>0){
                    $prow = $pemailRes->fetch_assoc();
                    $pemail = $prow['pemail'];
                    $pname = $prow['pname'];

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

                    $notifMsg = "Janji (#".$row['apponum'].") dikonfirmasi oleh dokter.";
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
                        $emailBody = "Halo,\n\nReservasi Anda (#".$row['apponum'].") telah disetujui oleh dokter. Silakan cek halaman My Bookings untuk detail.\n\nTerima kasih.";
                        send_emails_to_list([$pemail], $emailSubject, $emailBody);
                    }

                    $adminMsg = "Janji (#".$row['apponum'].") dikonfirmasi dokter ID ".$docid.".";
                    notify_recipients($database, get_admin_emails($database), $adminMsg, "/sskinv0.4.3/admin/appointment.php");
                }
                header("location: appointment.php?action=session-added");
                exit;
        }else{
            // cannot confirm, full
            $upd = $database->prepare("update appointment set status='rejected' where appoid=?");
            $upd->bind_param("i", $id);
            $upd->execute();
            // notify patient about rejection due to full
            $pemailRes = $database->query("select patient.pemail from appointment inner join patient on appointment.pid=patient.pid where appointment.appoid=$id");
            if($pemailRes && $pemailRes->num_rows>0){
                $prow = $pemailRes->fetch_assoc();
                $pemail = $prow['pemail'];
                $pname = $prow['pname'] ?? '';
                $notifMsg = "Janji (#".$row['apponum'].") ditolak karena sesi penuh.";
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

                $adminMsg = "Janji (#".$row['apponum'].") ditolak karena sesi penuh oleh dokter ID ".$docid.".";
                notify_recipients($database, get_admin_emails($database), $adminMsg, "/sskinv0.4.3/admin/appointment.php");
            }
            header("location: appointment.php?action=drop");
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
        // notify and email patient about rejection
        $pemailRes = $database->query("select patient.pemail from appointment inner join patient on appointment.pid=patient.pid where appointment.appoid=$id");
        if($pemailRes && $pemailRes->num_rows>0){
            $prow = $pemailRes->fetch_assoc();
            $pemail = $prow['pemail'];
            $pname = $prow['pname'];
            $notifMsg = "Janji (#".$row['apponum'].") ditolak oleh dokter.";
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
                $emailBody = "Halo,\n\nReservasi Anda (#".$row['apponum'].") telah ditolak oleh dokter. Silakan cek halaman My Bookings untuk detail.\n\nTerima kasih.";
                send_emails_to_list([$pemail], $emailSubject, $emailBody);
            }
            $adminMsg = "Janji (#".$row['apponum'].") ditolak oleh dokter ID ".$docid.".";
            notify_recipients($database, get_admin_emails($database), $adminMsg, "/sskinv0.4.3/admin/appointment.php");
        }
        header("location: appointment.php?action=drop");
        exit;
    }
}

header("location: appointment.php");
exit;
