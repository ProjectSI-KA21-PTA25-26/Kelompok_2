<?php
require_once 'notification_helper.php';
$to = $_GET['to'] ?? '';
if ($to === '') {
    echo 'Usage: test_email.php?to=you@domain.com';
    exit;
}
$ok = send_email($to, 'SSkIN Test Email', 'This is a test email from SSkIN.');
echo 'Sent: ' . ($ok ? 'OK' : 'Failed') . '<br>';
echo nl2br(@file_get_contents(__DIR__ . '/email_log.txt'));
