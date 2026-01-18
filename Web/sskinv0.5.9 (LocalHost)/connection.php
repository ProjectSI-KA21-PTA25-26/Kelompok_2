<?php
// Load environment variables from .env file if it exists
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Only set if not already set in environment
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Database configuration with fallback to default values
$servername = getenv('DB_HOST') ?: "localhost";
$username = getenv('DB_USERNAME') ?: "root";  
$password = getenv('DB_PASSWORD') ?: ""; 
$dbname = getenv('DB_NAME') ?: "edoc";
$port = getenv('DB_PORT') ?: 3306;

// Create connection
$database = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($database->connect_error) {
    // More detailed error message for debugging
    $error_msg = "Database Connection Failed: " . $database->connect_error;
    error_log($error_msg);
    
    // User-friendly error message
    die("Échec de la connexion à la base de données. Veuillez vérifier votre configuration dans le fichier .env");
}

// Set charset to UTF-8 to avoid encoding issues
$database->set_charset("utf8mb4");
?>

