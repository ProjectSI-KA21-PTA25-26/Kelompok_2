<?php
// API untuk admin update attendance status semua dokter
// File: admin/update_attendance_admin.php

session_start();

if(isset($_SESSION["user"])){
    if(($_SESSION["user"])=="" or $_SESSION['usertype']!='a'){
        echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin only']);
        exit;
    }
}else{
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please login']);
    exit;
}

include("../connection.php");

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scheduleid']) && isset($_POST['attendance_status'])){
    $scheduleid = intval($_POST['scheduleid']);
    $attendance_status = $_POST['attendance_status'];
    
    // Validate attendance status
    if(!in_array($attendance_status, ['present', 'absent', 'pending'])){
        echo json_encode(['success' => false, 'message' => 'Invalid attendance status']);
        exit;
    }
    
    // Admin can update any schedule, no need to check ownership
    $stmt = $database->prepare("SELECT scheduleid FROM schedule WHERE scheduleid=?");
    $stmt->bind_param("i", $scheduleid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 0){
        echo json_encode(['success' => false, 'message' => 'Schedule not found']);
        exit;
    }
    
    // Update attendance status
    $updateStmt = $database->prepare("UPDATE schedule SET attendance_status=? WHERE scheduleid=?");
    $updateStmt->bind_param("si", $attendance_status, $scheduleid);
    
    if($updateStmt->execute()){
        echo json_encode(['success' => true, 'message' => 'Attendance status updated by admin']);
    }else{
        echo json_encode(['success' => false, 'message' => 'Failed to update attendance status']);
    }
}else{
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
