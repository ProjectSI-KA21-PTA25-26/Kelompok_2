<?php
// admin/mark_session_completed.php
// Marks all appointments in a session as 'success' (Selesai)

session_start();
if (!isset($_SESSION["user"]) || $_SESSION["user"] == "" || $_SESSION['usertype'] != 'a') {
    header("location: ../login.php");
    exit();
}
include("../connection.php");

if (isset($_POST['scheduleid'])) {
    $scheduleid = intval($_POST['scheduleid']);
    // Update all appointments in this session to status 'success'
    $stmt = $database->prepare("UPDATE appointment SET status = 'success' WHERE scheduleid = ?");
    $stmt->bind_param("i", $scheduleid);
    $stmt->execute();
    $stmt->close();
    // Redirect back to schedule page with a success message
    header("Location: schedule.php?completed=1");
    exit();
} else {
    header("Location: schedule.php");
    exit();
}
