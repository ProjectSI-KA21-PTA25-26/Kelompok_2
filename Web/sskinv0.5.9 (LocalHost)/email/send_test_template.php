<?php
// Simple test script to send a templated email using existing helpers
require_once __DIR__ . '/../notification_helper.php';
require_once __DIR__ . '/EmailNotification.php';

$to = 'malamsabtu135@gmail.com';
$data = [
    'nama_pelanggan' => 'Al Farros',
    'kode_reservasi' => 'TEST-001',
    'nama_layanan' => 'Facial Glow',
    'tanggal_reservasi' => date('Y-m-d', strtotime('+2 days')),
    'waktu_reservasi' => '14:00',
    // ensure we include website/logo (will override defaults)
    'website_url' => getenv('SMTP_WEBSITE_URL') ?: 'https://shaskin.id',
    'logo_url' => getenv('SMTP_LOGO_URL') ?: 'https://shaskin.id/img/icons/logo-shaskin.jpg',
];

try {
    $ok = EmailNotification::send('pending', $to, $data);
    echo $ok ? "Email sent successfully to $to\n" : "Email sending failed to $to\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// Also write a log line for convenience
@file_put_contents(__DIR__ . '/../email_log.txt', date('Y-m-d H:i:s') . " | test send to: $to | success: " . ($ok ? "1" : "0") . PHP_EOL, FILE_APPEND);
