<?php
session_start();
include("connection.php");
require_once("notification_helper.php");
if(!isset($_SESSION['user'])){ header('location: login.php'); exit; }
$useremail = $_SESSION['user'];

// mark as read if requested
if(isset($_GET['mark']) && $_GET['mark']=='all'){
    ensure_notifications_table($database);
    $stmt = $database->prepare("update notifications set is_read=1 where recipient_email=?");
    $stmt->bind_param("s", $useremail);
    $stmt->execute();
    header('location: notifications.php');
    exit;
}

$list = fetch_notifications_for($database, $useremail, 100);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Notifications</title>
<link rel="stylesheet" href="css/main.css">
</head>
<body>
<div class="container">
    <div style="padding:20px;">
        <h2>Notifications</h2>
        <a href="?mark=all">Mark all read</a>
        <ul>
        <?php
        if($list && count($list)>0){
            foreach($list as $r){
                echo '<li style="margin-bottom:10px;'.($r['is_read']? 'opacity:0.6':'font-weight:700').'"><a href="'.$r['url'].'">'.htmlspecialchars($r['message']).'</a> <br><small>'.$r['created_at'].'</small></li>';
            }
        }else{
            echo '<li>No notifications</li>';
        }
        ?>
        </ul>
    </div>
</div>
</body>
</html>
