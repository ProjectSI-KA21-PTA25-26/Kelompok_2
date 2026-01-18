<?php

    //learn from w3schools.com

    session_start();

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='p'){
            header("location: ../login.php");
        }else{
            $useremail=$_SESSION["user"];
        }

    }else{
        header("location: ../login.php");
    }
    

    //import database
    include("../connection.php");
    require_once("../notification_helper.php");
    $sqlmain= "select * from patient where pemail=?";
    $stmt = $database->prepare($sqlmain);
    $stmt->bind_param("s",$useremail);
    $stmt->execute();
    $userrow = $stmt->get_result();
    $userfetch=$userrow->fetch_assoc();
    $userid= $userfetch["pid"];
    $username=$userfetch["pname"];


    if($_POST){
        if(isset($_POST["booknow"])){
            $scheduleid = intval($_POST["scheduleid"]);
            $date = $database->real_escape_string($_POST["date"]);
            // recompute apponum server-side to avoid client manipulation
            $stmtMax = $database->prepare("SELECT IFNULL(MAX(apponum),0) AS m FROM appointment WHERE scheduleid=?");
            $stmtMax->bind_param("i", $scheduleid);
            $stmtMax->execute();
            $resMax = $stmtMax->get_result()->fetch_assoc();
            $apponum = intval($resMax['m']) + 1;
            // ensure 'status' column exists
            $colRes = $database->query("SHOW COLUMNS FROM appointment LIKE 'status'");
            if($colRes->num_rows==0){
                $database->query("ALTER TABLE appointment ADD COLUMN status VARCHAR(20) DEFAULT 'confirmed'");
            }

            $stmtIns = $database->prepare("INSERT INTO appointment(pid,apponum,scheduleid,appodate,status) VALUES (?,?,?,?,?)");
            $status = 'confirmed';
            $stmtIns->bind_param("iiiss", $userid, $apponum, $scheduleid, $date, $status);
            $stmtIns->execute();

            // privacy-scoped notifications: doctor, patient, admin only
            $docRes = $database->query("select docemail from schedule inner join doctor on schedule.docid=doctor.docid where schedule.scheduleid=$scheduleid");
            $docEmail = '';
            if($docRes && $docRes->num_rows>0){
                $docEmail = $docRes->fetch_assoc()['docemail'];
                $docMsg = "Booking #".$apponum." dikonfirmasi untuk ".$date.".";
                notify_recipients($database, [$docEmail], $docMsg, "/sskinv0.4.3/doctor/appointment.php");
            }
            $patientMsg = "Booking #".$apponum." berhasil dikonfirmasi pada ".$date.".";
            notify_recipients($database, [$useremail], $patientMsg, "/sskinv0.4.3/patient/appointment.php");
            $adminMsg = "Booking #".$apponum." dikonfirmasi oleh pasien ".$username.".";
            notify_recipients($database, get_admin_emails($database), $adminMsg, "/sskinv0.4.3/admin/appointment.php");

            header("location: appointment.php?action=booking-added&id=".$apponum."&titleget=none");

        }
    }
 ?>
