<?php
/**
 * Test Database Connection
 * 
 * Gunakan file ini untuk memverifikasi koneksi database Anda
 * PENTING: Hapus file ini setelah testing untuk keamanan!
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Connection Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { background: #f0f0f0; padding: 10px; border-left: 4px solid #333; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
    </style>
</head>
<body>
    <h1>🔍 Database Connection Test</h1>";

// Load environment from .env file
if (file_exists(__DIR__ . '/.env')) {
    echo "<p class='success'>✅ File .env ditemukan</p>";
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!getenv($key)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
} else {
    echo "<p class='error'>❌ File .env tidak ditemukan! Copy .env.example ke .env terlebih dahulu.</p>";
}

// Get configuration
$servername = getenv('DB_HOST') ?: "localhost";
$username = getenv('DB_USERNAME') ?: "root";
$password = getenv('DB_PASSWORD') ?: "";
$dbname = getenv('DB_NAME') ?: "edoc";
$port = getenv('DB_PORT') ?: 3306;

echo "<div class='info'>
    <h3>Konfigurasi Database:</h3>
    <ul>
        <li><strong>Host:</strong> $servername</li>
        <li><strong>Username:</strong> $username</li>
        <li><strong>Password:</strong> " . (empty($password) ? "(kosong)" : "***") . "</li>
        <li><strong>Database:</strong> $dbname</li>
        <li><strong>Port:</strong> $port</li>
    </ul>
</div>";

// Test connection
echo "<h2>Testing Connection...</h2>";

try {
    $database = new mysqli($servername, $username, $password, $dbname, $port);
    
    if ($database->connect_error) {
        throw new Exception($database->connect_error);
    }
    
    echo "<p class='success'>✅ SUKSES! Koneksi ke database berhasil!</p>";
    
    // Test query - show tables
    echo "<h3>Tables dalam database:</h3>";
    $result = $database->query("SHOW TABLES");
    
    if ($result && $result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>#</th><th>Table Name</th></tr>";
        $i = 1;
        while ($row = $result->fetch_array()) {
            echo "<tr><td>$i</td><td>" . htmlspecialchars($row[0]) . "</td></tr>";
            $i++;
        }
        echo "</table>";
        echo "<p>Total: " . ($i - 1) . " tables</p>";
    } else {
        echo "<p class='error'>⚠️ Database kosong atau tidak ada tables. Silakan import edoc.sql</p>";
    }
    
    // Check PHP MySQL extension
    echo "<h3>PHP Configuration:</h3>";
    echo "<ul>";
    echo "<li>PHP Version: <strong>" . phpversion() . "</strong></li>";
    echo "<li>MySQLi Extension: <strong>" . (extension_loaded('mysqli') ? '✅ Loaded' : '❌ Not Loaded') . "</strong></li>";
    echo "<li>PDO Extension: <strong>" . (extension_loaded('pdo') ? '✅ Loaded' : '❌ Not Loaded') . "</strong></li>";
    echo "</ul>";
    
    $database->close();
    
} catch (Exception $e) {
    echo "<p class='error'>❌ GAGAL! Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<div class='info'>
        <h4>Troubleshooting:</h4>
        <ol>
            <li>Pastikan MySQL/MariaDB service sudah running</li>
            <li>Verifikasi username dan password di file .env</li>
            <li>Cek apakah database '$dbname' sudah dibuat</li>
            <li>Pastikan port 3306 tidak terblokir</li>
        </ol>
    </div>";
}

echo "
    <hr>
    <p><strong>⚠️ PENTING:</strong> Hapus file ini (test_connection.php) setelah testing selesai untuk keamanan!</p>
</body>
</html>";
?>
