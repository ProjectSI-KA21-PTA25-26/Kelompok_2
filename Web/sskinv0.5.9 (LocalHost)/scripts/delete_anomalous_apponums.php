<?php
include __DIR__ . '/../connection.php';

$threshold = 1000;
$ts = date('Ymd_His');
$backup = "appointment_backup_{$ts}";

echo "Starting deletion of appointments with apponum >= $threshold\n";

// create backup
$create = $database->query("CREATE TABLE IF NOT EXISTS `{$backup}` AS SELECT * FROM appointment");
if (!$create) {
    echo "Failed to create backup table {$backup}: " . $database->error . "\n";
    exit(1);
}

echo "Backup table created: {$backup}\n";

// find affected rows (for reporting)
$res = $database->query("SELECT appoid, pid, apponum, scheduleid, appodate FROM appointment WHERE apponum >= $threshold");
$rows = [];
if ($res) {
    while ($r = $res->fetch_assoc()) $rows[] = $r;
}

if (count($rows) === 0) {
    echo "No anomalous appointments found. No changes made.\n";
    exit(0);
}

echo "Found " . count($rows) . " appointment(s) to delete:\n";
foreach ($rows as $r) {
    echo " - appoid:" . $r['appoid'] . " | apponum:" . $r['apponum'] . " | pid:" . $r['pid'] . " | scheduleid:" . ($r['scheduleid'] ?? 'NULL') . " | appodate:" . ($r['appodate'] ?? 'NULL') . "\n";
}

// perform delete
$del = $database->query("DELETE FROM appointment WHERE apponum >= $threshold");
if ($del === false) {
    echo "Delete failed: " . $database->error . "\nAttempting restore from backup...\n";
    // attempt to restore
    if ($database->query("DROP TABLE IF EXISTS appointment") === false) {
        echo "Failed to drop appointment table: " . $database->error . "\n";
        exit(1);
    }
    if ($database->query("RENAME TABLE `{$backup}` TO appointment") === false) {
        echo "Failed to restore backup table: " . $database->error . "\n";
        exit(1);
    }
    echo "Restored from backup.\n";
    exit(1);
}

$deleted = $database->affected_rows;
echo "Deleted $deleted appointment(s). Backup remains as {$backup}.\n";
echo "If you want to permanently remove the backup, run: DROP TABLE {$backup};\n";
