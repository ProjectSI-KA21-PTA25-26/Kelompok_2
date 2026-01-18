<?php
require __DIR__ . '/../notification_helper.php';

$config = get_email_config();
$to = $argv[1] ?? 'beautycareshaskin@gmail.com';

// Capture PHPMailer SMTP debug output
$debug = '';
try {
    if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
        throw new Exception('PHPMailer not installed');
    }
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->SMTPDebug = 2; // verbose debug
    $mail->Debugoutput = function($str, $level) use (&$debug) { $debug .= $str . PHP_EOL; };
    $mail->isSMTP();
    $mail->Host = $config['smtp']['host'];
    $mail->SMTPAuth = $config['smtp']['auth'];
    $mail->Username = $config['smtp']['username'];
    $mail->Password = $config['smtp']['password'];
    $secure = $config['smtp']['secure'] ?? '';
    if (!empty($secure)) $mail->SMTPSecure = $secure;
    $mail->Port = $config['smtp']['port'];
    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($to);
    $mail->Subject = 'SSkIN SMTP Debug Test';
    $mail->Body = 'This is a detailed SMTP debug test.';
    $mail->isHTML($config['is_html']);
    $sent = $mail->send();
    echo "Send result: " . ($sent ? "OK" : "FAILED") . PHP_EOL;
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
}

echo "\n--- SMTP Debug ---\n";
echo $debug;

echo "\n--- email_log.txt ---\n";
echo file_get_contents(__DIR__ . '/../email_log.txt');
