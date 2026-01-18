<?php
// API untuk update attendance status
// File: doctor/update_attendance.php

session_start();

if(isset($_SESSION["user"])){
    if(($_SESSION["user"])=="" or $_SESSION['usertype']!='d'){
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }else{
        $useremail=$_SESSION["user"];
    }
}else{
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include("../connection.php");

// Get doctor ID
$userrow = $database->query("select * from doctor where docemail='$useremail'");
$userfetch=$userrow->fetch_assoc();
$userid= $userfetch["docid"];

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scheduleid']) && isset($_POST['attendance_status'])){
    $scheduleid = intval($_POST['scheduleid']);
    $attendance_status = $_POST['attendance_status'];
    
    // Validate attendance status
    if(!in_array($attendance_status, ['present', 'absent', 'pending'])){
        echo json_encode(['success' => false, 'message' => 'Invalid attendance status']);
        exit;
    }
    
    // Verify this schedule belongs to this doctor
    $stmt = $database->prepare("SELECT scheduleid FROM schedule WHERE scheduleid=? AND docid=?");
    $stmt->bind_param("ii", $scheduleid, $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 0){
        echo json_encode(['success' => false, 'message' => 'Schedule not found or unauthorized']);
        exit;
    }
    
    // Update attendance status
    $updateStmt = $database->prepare("UPDATE schedule SET attendance_status=? WHERE scheduleid=? AND docid=?");
    $updateStmt->bind_param("sii", $attendance_status, $scheduleid, $userid);
    
    if($updateStmt->execute()){
        echo json_encode(['success' => true, 'message' => 'Attendance status updated']);
    }else{
        echo json_encode(['success' => false, 'message' => 'Failed to update attendance status']);
    }
}else{
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
