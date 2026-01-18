<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php'; // PHPMailer

class EmailNotification
{
    private static function mailer()
    {
        // Ensure environment variables from .env are loaded (notification_helper provides load_dotenv)
        if (function_exists('load_dotenv')) {
            load_dotenv();
        }

        $configPath = __DIR__ . '/../config/email.php';
        $cfg = is_file($configPath) ? include $configPath : [];

        // Allow explicit environment overrides (take precedence over config file)
        $envEnabled = getenv('SMTP_ENABLED');
        if ($envEnabled !== false) $cfg['enabled'] = in_array(strtolower($envEnabled), ['1','true','yes'], true);

        $cfg['smtp'] = $cfg['smtp'] ?? [];
        $sHost = getenv('SMTP_HOST'); if ($sHost !== false) $cfg['smtp']['host'] = $sHost;
        $sPort = getenv('SMTP_PORT'); if ($sPort !== false) $cfg['smtp']['port'] = intval($sPort);
        $sUser = getenv('SMTP_USERNAME'); if ($sUser !== false) $cfg['smtp']['username'] = $sUser;
        $sPass = getenv('SMTP_PASSWORD'); if ($sPass !== false) $cfg['smtp']['password'] = $sPass;
        $sSecure = getenv('SMTP_SECURE'); if ($sSecure !== false) $cfg['smtp']['secure'] = $sSecure;
        $sAuth = getenv('SMTP_AUTH'); if ($sAuth !== false) $cfg['smtp']['auth'] = in_array(strtolower($sAuth), ['1','true','yes'], true);

        $fromEmail = getenv('SMTP_FROM_EMAIL') ?: ($cfg['from_email'] ?? 'no-reply@sskin.com');
        // Ensure consistent branding for from name
        $fromName  = getenv('SMTP_FROM_NAME') ?: ($cfg['from_name'] ?? 'ShaSkin Beauty Care');

        $mail = new PHPMailer(true);

        // Use SMTP transport only when enabled in config or via env; otherwise fall back to PHP mail()
        if (is_array($cfg) && !empty($cfg['enabled'])) {
            $mail->isSMTP();
            $smtp = $cfg['smtp'] ?? [];
            $mail->Host       = $smtp['host'] ?? 'smtp.example.com';
            $mail->SMTPAuth   = $smtp['auth'] ?? true;
            $mail->Username   = $smtp['username'] ?? '';
            $mail->Password   = $smtp['password'] ?? '';
            $secure = $smtp['secure'] ?? 'tls';
            if (in_array(strtolower($secure), ['ssl','tls'])) {
                $mail->SMTPSecure = (strtolower($secure) === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->Port       = $smtp['port'] ?? 587;
        } else {
            // use PHP mail() as transport
            $mail->isMail();
        }

        $mail->CharSet    = 'UTF-8';
        $mail->setFrom($fromEmail, $fromName);
        return $mail;
    }

    public static function send($type, $toEmail, $data = [])
    {
        // Prefer 'template' directory, keep legacy 'templates' as fallback
        $templatePath = __DIR__ . "/template/{$type}.html";
        if (!file_exists($templatePath)) {
            $alt = __DIR__ . "/templates/{$type}.html";
            if (file_exists($alt)) {
                $templatePath = $alt;
            } else {
                throw new Exception("Template email '{$type}' tidak ditemukan");
            }
        }

        $html = file_get_contents($templatePath);

        // default branding placeholders (can be overridden via $data)
        if (!isset($data['website_url'])) $data['website_url'] = 'https://shaskin.id';
        if (!isset($data['logo_url']))    $data['logo_url']    = 'https://shaskin.id/img/icons/logo-shaskin.jpg';

        foreach ($data as $key => $value) {
            $html = str_replace('{{'.$key.'}}', $value, $html);
        }

        $subjectMap = [
            'pending'   => 'Reservasi Menunggu Konfirmasi',
            'approved'  => 'Reservasi Anda Disetujui',
            'cancelled' => 'Reservasi Anda Dibatalkan'
        ];

        // If template is an HTML fragment (no <html> tag), wrap it into a minimal HTML skeleton so clients render it as HTML
        if (!preg_match('/<\/?html/i', $html)) {
            $pre = '';
            if (isset($data['kode_reservasi'])) $pre = 'Reservasi ' . (string)$data['kode_reservasi'];
            elseif (isset($data['nama_pelanggan'])) $pre = 'Notifikasi untuk ' . (string)$data['nama_pelanggan'];
            $pre = htmlspecialchars($pre, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $wrapper = "<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'><style>body{font-family:Arial,sans-serif;color:#222;background:#fff;padding:16px}</style></head><body><span style='display:none;max-height:0;overflow:hidden;'>" . $pre . "</span>" . $html . "</body></html>";
            $html = $wrapper;
        }

        // a simple plain-text alternative
        $altBody = trim(preg_replace('/\s+/', ' ', strip_tags($html)));

        $mail = self::mailer();
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = $subjectMap[$type] ?? 'Notifikasi Reservasi';
        $mail->Body    = $html;
        $mail->AltBody = $altBody;

        return $mail->send();
    }
}
