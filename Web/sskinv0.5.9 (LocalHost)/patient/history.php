<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">  
    <link rel="stylesheet" href="../css/main.css">  
    <link rel="stylesheet" href="../css/admin.css">
    <title>My History</title>
</head>
<body>
<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['usertype']!='p'){
    header('Location: ../login.php'); exit;
}
include('../connection.php');
$useremail = $_SESSION['user'];
$stmtU = $database->prepare("SELECT * FROM patient WHERE pemail=? LIMIT 1");
$stmtU->bind_param('s', $useremail);
$stmtU->execute();
$userfetch = $stmtU->get_result()->fetch_assoc();
$userid = intval($userfetch['pid']);
$username = $userfetch['pname'];
date_default_timezone_set('Asia/Jakarta');
$today = date('Y-m-d');

// Combined History Query - show all statuses (pending, confirmed, success, cancelled, rejected)
$sql = "SELECT appointment.appoid, appointment.scheduleid, COALESCE(schedule.title, appointment.req_title) AS title, COALESCE(doctor.docname,'') AS docname, COALESCE(schedule.scheduledate, appointment.req_scheduledate) AS scheduledate, COALESCE(schedule.scheduletime, appointment.req_scheduletime) AS scheduletime, appointment.apponum, appointment.appodate, IFNULL(appointment.status,'') as status, appointment.cancel_reason FROM appointment LEFT JOIN schedule ON schedule.scheduleid=appointment.scheduleid LEFT JOIN doctor ON doctor.docid=COALESCE(schedule.docid, appointment.req_docid) WHERE appointment.pid=? ORDER BY appointment.appodate DESC";
$stmt = $database->prepare($sql);
$stmt->bind_param('i', $userid);
$stmt->execute();
$res = $stmt->get_result();
?>
<div class="container">
    <div class="menu">
        <table class="menu-container" border="0">
            <tr>
                <td style="padding:10px" colspan="2">
                    <table border="0" class="profile-container">
                        <tr>
                            <td width="30%" style="padding-left:20px" >
                                <img src="../img/user.png" alt="" width="100%" style="border-radius:50%">
                            </td>
                            <td style="padding:0px;margin:0px;">
                                <p class="profile-title"><?php echo substr($username ?? '',0,13)  ?>..</p>
                                <p class="profile-subtitle"><?php echo substr($useremail ?? '',0,22)  ?></p>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php
                                    $notifApiPath = "../notifications_api.php";
                                    include(__DIR__ . "/../notifications_box.php");
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <a href="../logout.php" ><input type="button" value="Keluar" class="logout-btn btn-primary-soft btn"></a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="menu-row" >
                <td class="menu-btn menu-icon-home" >
                    <a href="index.php" class="non-style-link-menu"><div><p class="menu-text">Beranda</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">                <td class="menu-btn menu-icon-dashboard">
                    <a href="services.php" class="non-style-link-menu"><div><p class="menu-text">Harga Layanan</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">                <td class="menu-btn menu-icon-doctor">
                    <a href="doctors.php" class="non-style-link-menu"><div><p class="menu-text">Semua Dokter</p></div></a>
                </td>
            </tr>
            <tr class="menu-row" >
                <td class="menu-btn menu-icon-appoinment">
                    <a href="appointment.php" class="non-style-link-menu"><div><p class="menu-text">Janji Temu Saya</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-history menu-active menu-icon-history-active">
                    <a href="history.php" class="non-style-link-menu non-style-link-menu-active"><div><p class="menu-text">Riwayat</p></div></a>
                </td>
            </tr>
            <tr class="menu-row" >
                <td class="menu-btn menu-icon-settings">
                    <a href="settings.php" class="non-style-link-menu"><div><p class="menu-text">Pengaturan</p></div></a>
                </td>
            </tr>
        </table>
    </div>
    <div class="dash-body">
        <table border="0" width="100%">
            <tr>
                <td width="13%"><a href="appointment.php"><button class="login-btn btn-primary-soft btn btn-icon-back">Kembali</button></a></td>
                <td><p style="font-size:23px;font-weight:600;">My History</p></td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="abc scroll">
                        <table width="95%" class="sub-table scrolldown" border="0">
                            <thead>
                                <tr>
                                    <th>Appointment #</th>
                                    <th>Doctor</th>
                                    <th>Session</th>
                                    <th>Scheduled</th>
                                    <th>Booked On</th>
                                    <th>Status</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            if($res->num_rows==0){
                                echo '<tr><td colspan="7"><center><p class="heading-main12">No history found.</p></center></td></tr>';
                            } else {
                                while($row = $res->fetch_assoc()){
                                    echo '<tr>';
                                    echo '<td style="text-align:center;">'.htmlspecialchars($row['apponum']).'</td>';
                                    echo '<td>'.htmlspecialchars($row['docname']).'</td>';
                                    echo '<td>'.htmlspecialchars($row['title']).'</td>';
                                    echo '<td style="text-align:center;">'.htmlspecialchars($row['scheduledate']).' '.htmlspecialchars(substr($row['scheduletime'] ?? '',0,5)).'</td>';
                                    echo '<td style="text-align:center;">'.htmlspecialchars($row['appodate']).'</td>';
                                    // Display status with proper formatting
                                    $statusDisplay = '';
                                    $st = trim($row['status']);
                                    if($st == '' || $st == 'pending'){
                                        $statusDisplay = '<span style="color:#b35;font-weight:bold;">Menunggu Konfirmasi</span>';
                                    } elseif($st == 'confirmed'){
                                        $statusDisplay = '<span style="color:#ff9800;font-weight:bold;">Berjalan</span>';
                                    } elseif($st == 'success'){
                                        $statusDisplay = '<span style="color:#28a745;font-weight:bold;">Selesai</span>';
                                    } elseif($st == 'cancelled'){
                                        $statusDisplay = '<span style="color:#dc3545;">Dibatalkan</span>';
                                    } elseif($st == 'rejected'){
                                        $statusDisplay = '<span style="color:#dc3545;">Ditolak</span>';
                                    } else {
                                        $statusDisplay = '<span>'.htmlspecialchars($row['status']).'</span>';
                                    }
                                    echo '<td style="text-align:center;">'.$statusDisplay.'</td>';
                                    echo '<td>'.(!empty($row['cancel_reason']) ? htmlspecialchars($row['cancel_reason']) : '-').'</td>';
                                    echo '</tr>';
                                }
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
</body>
</html>
