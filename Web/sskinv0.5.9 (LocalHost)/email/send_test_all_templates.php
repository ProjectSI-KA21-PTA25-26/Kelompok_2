<?php
require_once __DIR__ . '/../notification_helper.php';
require_once __DIR__ . '/EmailNotification.php';

$to = 'malamsabtu135@gmail.com';
$types = ['pending', 'approved', 'cancelled'];

foreach ($types as $type) {
    $data = [
        'nama_pelanggan' => 'Al Farros',
        'kode_reservasi' => 'TEST-' . strtoupper($type) . '-' . rand(100,999),
        'nama_layanan' => $type === 'approved' ? 'Facial Glow' : 'Konsultasi Kulit',
        'tanggal_reservasi' => date('Y-m-d', strtotime('+'.rand(1,7).' days')),
        'waktu_reservasi' => sprintf('%02d:00', rand(9,17)),
        // keep template fragments unchanged; allow wrapper to add minimal HTML
    ];

    try {
        $ok = EmailNotification::send($type, $to, $data);
        echo ($ok ? "[OK]" : "[FAIL]") . " Sent type={$type} to {$to}\n";
        @file_put_contents(__DIR__ . '/../email_log.txt', date('Y-m-d H:i:s') . " | test send: $type to: $to | success: " . ($ok ? "1" : "0") . PHP_EOL, FILE_APPEND);
    } catch (Exception $e) {
        echo "[ERR] type={$type} - " . $e->getMessage() . "\n";
        @file_put_contents(__DIR__ . '/../email_log.txt', date('Y-m-d H:i:s') . " | test send: $type to: $to | success: 0 | err: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    }
}
