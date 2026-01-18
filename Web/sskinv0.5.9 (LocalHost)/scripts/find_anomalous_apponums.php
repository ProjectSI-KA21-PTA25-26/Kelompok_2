<?php
include __DIR__ . '/../connection.php';

$threshold = 1000; // default threshold to consider anomalous

$stmt = $database->prepare("SELECT appoid, pid, apponum, scheduleid, appodate, status FROM appointment WHERE apponum >= ? ORDER BY apponum DESC LIMIT 200");
$stmt->bind_param("i", $threshold);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
}

echo "Found " . count($rows) . " anomalous appointment(s) with apponum >= $threshold\n";
if (count($rows) > 0) {
    foreach ($rows as $row) {
        echo implode(" | ", [
            'appoid:'.$row['appoid'],
            'pid:'.$row['pid'],
            'apponum:'.$row['apponum'],
            'scheduleid:'.($row['scheduleid'] ?? 'NULL'),
            'appodate:'.($row['appodate'] ?? 'NULL'),
            'status:'.($row['status'] ?? 'NULL')
        ]) . "\n";
    }
} 

echo "\nTo delete these safely, run: php scripts/delete_anomalous_apponums.php (will create a backup first)\n";
