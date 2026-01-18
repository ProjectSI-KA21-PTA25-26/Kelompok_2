<?php

    session_start();

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='a'){
            header("location: ../login.php");
        }

    }else{
        header("location: ../login.php");
    }
    
    if($_POST){
        include("../connection.php");

        // basic sanitization/typing
        $title = trim($_POST["title"]);
        $docid = isset($_POST["docid"]) ? intval($_POST["docid"]) : 0;
        $nop = 1; // Always 1 patient per session/shift
        $date = $_POST["date"];
        $time = $_POST["time"];
        $patientId = isset($_POST["patient_id"]) ? intval($_POST["patient_id"]) : 0;

        // create the schedule
        $stmt = $database->prepare("insert into schedule (docid,title,scheduledate,scheduletime,nop) values (?,?,?,?,?)");
        $stmt->bind_param("isssi", $docid, $title, $date, $time, $nop);
        $stmt->execute();
        $scheduleId = $database->insert_id;

        // if a patient is chosen, auto-create their reservation/appointment
        if($patientId && $scheduleId){
            // ensure `status` column exists for downstream displays
            $colRes = $database->query("SHOW COLUMNS FROM appointment LIKE 'status'");
            if($colRes->num_rows==0){
                $database->query("ALTER TABLE appointment ADD COLUMN status VARCHAR(20) DEFAULT 'confirmed'");
            }

            // figure next appointment number on this schedule
            $countStmt = $database->prepare("SELECT IFNULL(MAX(apponum),0) as m FROM appointment WHERE scheduleid=?");
            $countStmt->bind_param("i", $scheduleId);
            $countStmt->execute();
            $countRow = $countStmt->get_result()->fetch_assoc();
            $apponum = intval($countRow["m"]) + 1;

            date_default_timezone_set('Asia/Jakarta');
            $appodate = date('Y-m-d'); // when this booking is placed
            $status = 'confirmed';

            $appStmt = $database->prepare("INSERT INTO appointment(pid,apponum,scheduleid,appodate,status) VALUES (?,?,?,?,?)");
            $appStmt->bind_param("iiiss", $patientId, $apponum, $scheduleId, $appodate, $status);
            $appStmt->execute();
        }

        header("location: schedule.php?action=session-added&title=$title");
    }


?>
