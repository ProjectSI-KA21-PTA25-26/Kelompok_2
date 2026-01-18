<?php
session_start();
if(isset($_SESSION["user"])){
    if(($_SESSION["user"])=="" or $_SESSION['usertype']!='a'){
        header("location: ../login.php");
        exit;
    }else{
        $useremail=$_SESSION["user"];
    }
}else{
    header("location: ../login.php");
    exit;
}

include("../connection.php");
require_once(__DIR__ . "/../notification_helper.php");

// load config via helper (will read .env if present)
$emailConfig = get_email_config();

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        $envData = [];
        $envData['SMTP_ENABLED'] = isset($_POST['enabled']) && $_POST['enabled']=='1' ? '1' : '0';
        $envData['SMTP_HOST'] = trim($_POST['host'] ?? '');
        $envData['SMTP_PORT'] = intval($_POST['port'] ?? 587);
        $envData['SMTP_USERNAME'] = trim($_POST['username'] ?? '');
        $envData['SMTP_PASSWORD'] = trim($_POST['password'] ?? '');
        $envData['SMTP_SECURE'] = trim($_POST['secure'] ?? 'tls');
        $envData['SMTP_AUTH'] = isset($_POST['auth']) && $_POST['auth']=='1' ? '1' : '0';
        $envData['SMTP_FROM_EMAIL'] = trim($_POST['from_email'] ?? 'no-reply@sskin.com');
        $envData['SMTP_FROM_NAME'] = trim($_POST['from_name'] ?? 'SSkIN Notifications');
        $envData['SMTP_IS_HTML'] = isset($_POST['is_html']) && $_POST['is_html']=='1' ? '1' : '0';

        if (save_env_vars($envData)) {
            $successMsg = 'Pengaturan email berhasil disimpan ke .env.';
            // reload env into runtime and refresh emailConfig
            load_dotenv();
            $emailConfig = get_email_config();
        } else {
            $errorMsg = 'Gagal menyimpan konfigurasi .env. Periksa permission file.';
        }
    } elseif (isset($_POST['send_test'])) {
        $to = trim($_POST['test_email'] ?? '');
        if ($to === '') {
            $errorMsg = 'Masukkan alamat email untuk diuji.';
        } else {
            $ok = send_email($to, 'Test Email SSkIN', 'Ini email tes dari SSkIN.');
            if ($ok) $successMsg = 'Email tes terkirim (cek email dan email_log.txt).';
            else $errorMsg = 'Pengiriman tes gagal. Periksa email_log.txt untuk detail.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="../scripts/prevent-double-submit.js"></script>
    <title>Email Settings</title>
</head>
<body>
<div class="container">
    <div class="menu">
        <?php include(__DIR__ . "/../admin/email-menu-snippet.php"); // if you have a snippet for consistent menu; otherwise it's fine ?>
    </div>
    <div class="dash-body">
        <h2>SMTP / Email Settings</h2>
        <?php if ($successMsg) echo '<div style="color:green">' . htmlspecialchars($successMsg) . '</div>'; ?>
        <?php if ($errorMsg) echo '<div style="color:red">' . htmlspecialchars($errorMsg) . '</div>'; ?>
        <?php
            // PHPMailer availability status
            $phpmailer_ok = class_exists('\PHPMailer\PHPMailer\PHPMailer');
            echo '<p><strong>PHPMailer: </strong>' . ($phpmailer_ok ? '<span style="color:green">Installed</span>' : '<span style="color:orange">Not installed</span>') . '</p>';
            echo '<p><strong>SMTP Active: </strong>' . (!empty($emailConfig['enabled']) ? '<span style="color:green">Yes</span>' : '<span style="color:grey">No</span>') . '</p>';
            echo '<p><small>Note: Gmail requires an <em>App Password</em> (if 2FA enabled) or allowing less-secure access; use App Password for reliable SMTP. For testing, consider Mailtrap.</small></p>';
        ?>

        <form method="post">
            <label><input type="checkbox" name="enabled" value="1" <?php echo (!empty($emailConfig['enabled'])?'checked':''); ?>> Enable SMTP (use PHPMailer)</label>
            <h3>SMTP</h3>
            <label>Host: <input type="text" name="host" value="<?php echo htmlspecialchars($emailConfig['smtp']['host'] ?? ''); ?>"></label><br>
            <label>Port: <input type="number" name="port" value="<?php echo htmlspecialchars($emailConfig['smtp']['port'] ?? 587); ?>"></label><br>
            <label>Username: <input type="text" name="username" value="<?php echo htmlspecialchars($emailConfig['smtp']['username'] ?? ''); ?>"></label><br>
            <label>Password: <input type="password" name="password" value="<?php echo htmlspecialchars($emailConfig['smtp']['password'] ?? ''); ?>"></label><br>
            <label>Secure (tls/ssl/empty): <input type="text" name="secure" value="<?php echo htmlspecialchars($emailConfig['smtp']['secure'] ?? 'tls'); ?>"></label><br>
            <label><input type="checkbox" name="auth" value="1" <?php echo (!empty($emailConfig['smtp']['auth'])?'checked':''); ?>> Require Auth</label><br>
            <h3>From</h3>
            <label>From Email: <input type="email" name="from_email" value="<?php echo htmlspecialchars($emailConfig['from_email'] ?? 'no-reply@sskin.com'); ?>"></label><br>
            <label>From Name: <input type="text" name="from_name" value="<?php echo htmlspecialchars($emailConfig['from_name'] ?? 'SSkIN Notifications'); ?>"></label><br>
            <label><input type="checkbox" name="is_html" value="1" <?php echo (!empty($emailConfig['is_html'])?'checked':''); ?>> Send HTML</label><br>
            <br>
            <button type="submit" name="save" class="login-btn btn-primary btn">Save Settings</button>
        </form>

        <hr>
        <h3>Test Email</h3>
        <form method="post">
            <label>To (email): <input type="email" name="test_email" required></label>
            <button type="submit" name="send_test" class="login-btn btn-primary btn">Send Test Email</button>
        </form>
        <p>Note: Composer & PHPMailer must be installed (`composer require phpmailer/phpmailer`) for SMTP; otherwise fallback to PHP <code>mail()</code> (likely to fail on Windows).</p>
    </div>
</div>
</body>
</html>
