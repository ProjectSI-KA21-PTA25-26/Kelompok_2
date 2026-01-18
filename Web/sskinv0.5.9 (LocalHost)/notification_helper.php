<?php
// Attempt to autoload Composer dependencies (PHPMailer) when available
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Shared helpers for notifications: creation, retrieval, and schema bootstrapping.

/**
 * Load .env file into environment variables (simple parser; preserves existing envs)
 */
function load_dotenv(?string $path = null): void {
    if ($path === null) $path = __DIR__ . '/.env';
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // remove surrounding quotes
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value ?? '', 1, -1);
        }
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// load .env early
load_dotenv();

/**
 * Build email config from environment variables (env takes precedence) and fallback to config/email.php
 */
function get_email_config(): array {
    // defaults
    $defaults = [
        'enabled' => false,
        'smtp' => [
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => '',
            'password' => '',
            'secure' => 'tls',
            'auth' => true,
        ],
        'from_email' => 'no-reply@sskin.com',
        'from_name' => 'SSkIN Notifications',
        'is_html' => false,
    ];

    // start from config file if present
    $configFile = __DIR__ . '/config/email.php';
    if (file_exists($configFile)) {
        $fileConfig = include($configFile);
        if (is_array($fileConfig)) {
            $defaults = array_replace_recursive($defaults, $fileConfig);
        }
    }

    // env overrides
    $envEnabled = getenv('SMTP_ENABLED');
    if ($envEnabled !== false) {
        $defaults['enabled'] = in_array(strtolower($envEnabled), ['1', 'true', 'yes'], true);
    }
    $sHost = getenv('SMTP_HOST'); if ($sHost !== false) $defaults['smtp']['host'] = $sHost;
    $sPort = getenv('SMTP_PORT'); if ($sPort !== false) $defaults['smtp']['port'] = intval($sPort);
    $sUser = getenv('SMTP_USERNAME'); if ($sUser !== false) $defaults['smtp']['username'] = $sUser;
    $sPass = getenv('SMTP_PASSWORD'); if ($sPass !== false) $defaults['smtp']['password'] = $sPass;
    $sSecure = getenv('SMTP_SECURE'); if ($sSecure !== false) $defaults['smtp']['secure'] = $sSecure;
    $sAuth = getenv('SMTP_AUTH'); if ($sAuth !== false) $defaults['smtp']['auth'] = in_array(strtolower($sAuth), ['1','true','yes'], true);
    $fromEmail = getenv('SMTP_FROM_EMAIL'); if ($fromEmail !== false) $defaults['from_email'] = $fromEmail;
    $fromName = getenv('SMTP_FROM_NAME'); if ($fromName !== false) $defaults['from_name'] = $fromName;
    $isHtml = getenv('SMTP_IS_HTML'); if ($isHtml !== false) $defaults['is_html'] = in_array(strtolower($isHtml), ['1','true','yes'], true);

    return $defaults;
}

/**
 * Save environment variables to .env (atomic write). Accepts an associative array of keys to values.
 */
function save_env_vars(array $data, ?string $path = null): bool {
    if ($path === null) $path = __DIR__ . '/../.env';
    $existing = [];
    if (file_exists($path)) {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (!str_contains($line, '=')) continue;
            [$k, $v] = explode('=', $line, 2);
            $existing[trim($k)] = $v;
        }
    }
    foreach ($data as $k => $v) {
        $existing[$k] = $v;
    }
    // build content
    $content = '';
    foreach ($existing as $k => $v) {
        // quote values containing spaces or #
        if (preg_match('/\s|#/', (string)$v)) {
            $v = '"' . str_replace('"', '\\"', (string)$v) . '"';
        }
        $content .= $k . '=' . $v . PHP_EOL;
    }
    $tmp = $path . '.tmp';
    if (@file_put_contents($tmp, $content) === false) return false;
    if (!@rename($tmp, $path)) { @unlink($tmp); return false; }
    return true;
}

/**
 * Ensure notifications table exists.
 */
function ensure_notifications_table(mysqli $database): void {
    $database->query("CREATE TABLE IF NOT EXISTS notifications (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        recipient_email VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        url VARCHAR(255) DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;");
}

/**
 * Insert a notification for each recipient (emails).
 */
function notify_recipients(mysqli $database, array $recipients, string $message, ?string $url = null): void {
    if (empty($recipients)) {
        return;
    }
    ensure_notifications_table($database);
    $stmt = $database->prepare("INSERT INTO notifications(recipient_email, message, url) VALUES(?,?,?)");
    foreach ($recipients as $email) {
        $cleanEmail = trim($email ?? '');
        if ($cleanEmail === '') {
            continue;
        }
        $stmt->bind_param("sss", $cleanEmail, $message, $url);
        $stmt->execute();
    }
}

/**
 * Get admin email list (for privacy-scoped notifications).
 */
function get_admin_emails(mysqli $database): array {
    $emails = [];
    if ($res = $database->query("SELECT email FROM webuser WHERE usertype='a'")) {
        while ($row = $res->fetch_assoc()) {
            if (!empty($row['email'])) {
                $emails[] = $row['email'];
            }
        }
    }
    if (empty($emails)) {
        // fallback to default seed email if table is empty
        $emails[] = 'admin@edoc.com';
    }
    return array_unique($emails);
}

/**
 * Fetch recent notifications for a user.
 */
function fetch_notifications_for(mysqli $database, string $recipientEmail, int $limit = 20): array {
    ensure_notifications_table($database);
    $stmt = $database->prepare("SELECT id, message, url, is_read, created_at FROM notifications WHERE recipient_email=? ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("si", $recipientEmail, $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

/**
 * Count unread notifications for a user.
 */
function count_unread_for(mysqli $database, string $recipientEmail): int {
    ensure_notifications_table($database);
    $stmt = $database->prepare("SELECT COUNT(*) AS c FROM notifications WHERE recipient_email=? AND is_read=0");
    $stmt->bind_param("s", $recipientEmail);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        return intval($row['c']);
    }
    return 0;
}

// load effective email config (merged from config/email.php + .env)
$EMAIL_CONFIG = get_email_config();

/**
 * Try sending an email via PHPMailer SMTP if configured.
 */
function send_email_smtp(string $to, string $subject, string $body, array $config): bool {
    // require PHPMailer if installed via composer
    if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
        return false;
    }
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $config['smtp']['host'] ?? '';
        $mail->SMTPAuth = $config['smtp']['auth'] ?? true;
        $mail->Username = $config['smtp']['username'] ?? '';
        $mail->Password = $config['smtp']['password'] ?? '';
        $secure = $config['smtp']['secure'] ?? '';
        if (!empty($secure)) $mail->SMTPSecure = $secure;
        $mail->Port = $config['smtp']['port'] ?? 587;

        $fromEmail = $config['from_email'] ?? 'no-reply@sskin.com';
        $fromName = $config['from_name'] ?? 'SSkIN';
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->isHTML($config['is_html'] ?? false);
        $sent = $mail->send();
        $logline = date('Y-m-d H:i:s') . " | smtp to: $to | subject: $subject | success: " . ($sent ? "1" : "0") . PHP_EOL;
        @file_put_contents(__DIR__ . "/email_log.txt", $logline, FILE_APPEND);
        return (bool)$sent;
    } catch (Exception $e) {
        $logline = date('Y-m-d H:i:s') . " | smtp error: " . $e->getMessage() . PHP_EOL;
        @file_put_contents(__DIR__ . "/email_log.txt", $logline, FILE_APPEND);
        return false;
    }
}

/**
 * Save email config array to config/email.php (atomic write)
 */
function save_email_config(array $config): bool {
    $path = __DIR__ . '/config/email.php';
    $export = var_export($config, true);
    $content = "<?php\n// SMTP/email configuration for SSkIN\nreturn " . $export . ";\n";
    $tmp = $path . '.tmp';
    if (@file_put_contents($tmp, $content) === false) {
        return false;
    }
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

/**
 * Send an email using PHP mail() or PHPMailer when enabled/configured; always log outcome.
 */
function send_email(string $to, string $subject, string $body, string $from = 'no-reply@sskin.com'): bool {
    global $EMAIL_CONFIG;
    // try SMTP if enabled and config present
    if (is_array($EMAIL_CONFIG) && !empty($EMAIL_CONFIG['enabled'])) {
        $ok = send_email_smtp($to, $subject, $body, $EMAIL_CONFIG);
        if ($ok) return true; // success via smtp
    }

    // fallback to PHP mail()
    $headers = "From: " . $from . "\r\n" .
               "MIME-Version: 1.0\r\n" .
               "Content-Type: text/plain; charset=UTF-8\r\n";
    $success = mail($to, $subject, $body, $headers);
    // log outcome for local debugging
    $logline = date('Y-m-d H:i:s') . " | to: $to | subject: $subject | success: " . ($success ? "1" : "0") . PHP_EOL;
    @file_put_contents(__DIR__ . "/email_log.txt", $logline, FILE_APPEND);
    return $success;
}

/**
 * Send an email to an array of recipients.
 */
function send_emails_to_list(array $recipients, string $subject, string $body, string $from = 'no-reply@sskin.com'): void {
    foreach ($recipients as $r) {
        $email = trim($r ?? '');
        if ($email === '') continue;
        send_email($email, $subject, $body, $from);
    }
}

/**
 * Send HTML templated email to a list. Falls back to plain text if templating not available.
 */
function send_templated_emails_to_list(array $recipients, string $type, array $data = [], string $from = 'no-reply@sskin.com'): void {
    // ensure EmailNotification class is available
    $emailClassPath = __DIR__ . '/email/EmailNotification.php';
    if (!class_exists('EmailNotification') && is_file($emailClassPath)) {
        require_once $emailClassPath;
    }

    foreach ($recipients as $r) {
        $email = trim($r ?? '');
        if ($email === '') continue;

        // try using EmailNotification if available and template exists
        if (class_exists('EmailNotification')) {
            try {
                // add recipient-specific data
                $d = $data;
                if (!isset($d['nama_pelanggan'])) {
                    // attempt to fill nama_pelanggan from DB if pid provided
                }
                EmailNotification::send($type, $email, $d);
                // log result
                $logline = date('Y-m-d H:i:s') . " | smtp template: $email | type: $type | success: 1" . PHP_EOL;
                @file_put_contents(__DIR__ . "/email_log.txt", $logline, FILE_APPEND);
                continue;
            } catch (Exception $ex) {
                // fall through to plain text fallback
                $logline = date('Y-m-d H:i:s') . " | smtp template: $email | type: $type | success: 0 | err: " . $ex->getMessage() . PHP_EOL;
                @file_put_contents(__DIR__ . "/email_log.txt", $logline, FILE_APPEND);
            }
        }

        // fallback: send plain text using existing helper
        $fallbackSubject = isset($data['subject']) ? $data['subject'] : 'Notifikasi Reservasi';
        $fallbackBody = isset($data['plain_body']) ? $data['plain_body'] : (isset($data['message']) ? $data['message'] : $fallbackSubject);
        send_email($email, $fallbackSubject, $fallbackBody, $from);
    }
}

/**
 * Convenience: create notification entries and also send email to recipients (for final notifications).
 */
function notify_and_email(mysqli $database, array $recipients, string $message, ?string $url = null, string $emailSubject = '', string $emailBody = ''): void {
    // default email subject/body if not provided
    if ($emailSubject === '') $emailSubject = 'Notifikasi dari SSkIN';
    if ($emailBody === '') $emailBody = $message;
    notify_recipients($database, $recipients, $message, $url);
    send_emails_to_list($recipients, $emailSubject, $emailBody);
}
