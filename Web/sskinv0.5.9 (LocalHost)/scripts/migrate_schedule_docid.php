<?php
// Migration script: backup `schedule` table and change `docid` column to INT
// Usage: run from project root: php scripts/migrate_schedule_docid.php

date_default_timezone_set('Asia/Jakarta');
$timestamp = date('Ymd_His');
$backup_table = 'schedule_backup_' . $timestamp;

// load DB connection
include __DIR__ . '/../connection.php';

if (!isset($database) || !($database instanceof mysqli)){
    echo "Database connection not found or invalid. Aborting.\n";
    exit(1);
}

echo "Starting migration at $timestamp\n";

// 1) Ensure schedule table exists
$res = $database->query("SHOW TABLES LIKE 'schedule'");
if (!$res || $res->num_rows == 0){
    echo "Table `schedule` does not exist. Aborting.\n";
    exit(1);
}

// 2) Create backup table
echo "Creating backup table `$backup_table`...\n";
$create_sql = "CREATE TABLE `$backup_table` AS SELECT * FROM schedule";
if (!$database->query($create_sql)){
    echo "Failed to create backup table: " . $database->error . "\n";
    echo "Aborting without changes.\n";
    exit(1);
}
echo "Backup created: $backup_table\n";

// 3) Verify docid values are numeric (or NULL)
echo "Checking `docid` values for non-numeric entries...\n";
$check_sql = "SELECT COUNT(*) AS total, SUM(docid REGEXP '^[0-9]+$') AS numeric_count FROM schedule";
$r = $database->query($check_sql);
if (!$r){
    echo "Failed to check docid values: " . $database->error . "\n";
    echo "Restoration not needed (backup exists). Manual inspection recommended.\n";
    exit(1);
}
$arr = $r->fetch_assoc();
$total = intval($arr['total']);
$numeric = intval($arr['numeric_count']);

if ($total > 0 && $numeric !== $total){
    echo "Found non-numeric `docid` values ($numeric of $total numeric).\n";
    echo "Listing up to 20 non-numeric docid rows:\n";
    $bad = $database->query("SELECT scheduleid, docid FROM schedule WHERE NOT (docid REGEXP '^[0-9]+$') LIMIT 20");
    while($row = $bad->fetch_assoc()){
        echo " scheduleid={$row['scheduleid']} docid='". $row['docid'] ."'\n";
    }
    echo "\nCannot safely ALTER column while non-numeric values exist.\n";
    echo "You can fix or delete those rows, then re-run this script. Backup table: $backup_table\n";
    exit(1);
}

// 4) Perform ALTER
echo "All docid values numeric (or table empty). Proceeding to ALTER column.\n";
$alter_sql = "ALTER TABLE schedule MODIFY COLUMN docid INT(11) DEFAULT NULL";
if (!$database->query($alter_sql)){
    echo "ALTER failed: " . $database->error . "\n";
    echo "Attempting to restore from backup...\n";
    // try restore: drop current and rename backup
    if ($database->query("DROP TABLE IF EXISTS schedule")){
        if ($database->query("RENAME TABLE `$backup_table` TO schedule")){
            echo "Restored schedule from backup ($backup_table).\n";
            exit(1);
        } else {
            echo "Failed to rename backup to schedule: " . $database->error . "\n";
            echo "Manual restore required. Backup table left as: $backup_table\n";
            exit(1);
        }
    } else {
        echo "Failed to drop corrupted schedule table: " . $database->error . "\n";
        echo "Manual restore required. Backup table left as: $backup_table\n";
        exit(1);
    }
}

echo "ALTER succeeded. Column `docid` is now INT.\n";
echo "Backup table retained as: $backup_table (you may drop it after verifying).\n";
echo "Migration completed successfully.\n";
exit(0);

