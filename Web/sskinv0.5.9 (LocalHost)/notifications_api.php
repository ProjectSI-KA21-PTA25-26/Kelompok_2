<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

include("connection.php");
require_once("notification_helper.php");

$useremail = $_SESSION['user'];
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'mark_all_read') {
        ensure_notifications_table($database);
        $stmt = $database->prepare("UPDATE notifications SET is_read=1 WHERE recipient_email=?");
        $stmt->bind_param("s", $useremail);
        $stmt->execute();
        echo json_encode(['ok' => true]);
        exit;
    }
}

// default: list recent notifications
$limit = isset($_GET['limit']) ? max(1, min(50, intval($_GET['limit']))) : 20;
$notifications = fetch_notifications_for($database, $useremail, $limit);
$unread = count_unread_for($database, $useremail);

echo json_encode([
    'notifications' => $notifications,
    'unread_count' => $unread
]);
exit;
