<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">  
    <link rel="stylesheet" href="../css/main.css">  
    <link rel="stylesheet" href="../css/admin.css">
    <title>Riwayat Saya</title>
    <style>
        .tab-container { display: flex; gap: 10px; margin-bottom: 20px; padding-left: 20px; }
        .tab-btn { padding: 10px 20px; border: none; background: #f0f0f0; cursor: pointer; border-radius: 5px; font-weight: 600; }
        .tab-btn.active { background: linear-gradient(to right, #3498db, #2980b9); color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['usertype']!='d'){
    header('Location: ../login.php'); exit;
}
include('../connection.php');
$useremail = $_SESSION['user'];
$userrow = $database->query("select * from doctor where docemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid = intval($userfetch['docid']);
$username = $userfetch['docname'];
date_default_timezone_set('Asia/Jakarta');
$today = date('Y-m-d');

// Appointment History Query
$sql = "SELECT appointment.appoid, appointment.scheduleid, COALESCE(schedule.title, appointment.req_title) AS title, COALESCE(patient.pname,'') AS pname, COALESCE(schedule.scheduledate, appointment.req_scheduledate) AS scheduledate, COALESCE(schedule.scheduletime, appointment.req_scheduletime) AS scheduletime, appointment.apponum, appointment.appodate, IFNULL(appointment.status,'') as status, appointment.cancel_reason FROM appointment LEFT JOIN schedule ON schedule.scheduleid=appointment.scheduleid LEFT JOIN patient ON patient.pid=appointment.pid WHERE IFNULL(appointment.status,'') IN ('cancelled','rejected','success') AND (schedule.docid=? OR appointment.req_docid=?) ORDER BY appointment.appodate DESC";
$stmt = $database->prepare($sql);
$stmt->bind_param('ii', $userid, $userid);
$stmt->execute();
$res = $stmt->get_result();

// Session History Query - show running (confirmed) and completed sessions for this doctor
$sqlSession = "SELECT schedule.scheduleid, schedule.title, schedule.scheduledate, schedule.scheduletime, schedule.nop, 
    (SELECT COUNT(*) FROM appointment WHERE appointment.scheduleid = schedule.scheduleid) as booked,
    CASE 
        WHEN schedule.scheduleid IN (SELECT DISTINCT scheduleid FROM appointment WHERE IFNULL(status,'')='confirmed' AND scheduleid IS NOT NULL) THEN 'Berjalan'
        WHEN schedule.scheduleid IN (SELECT DISTINCT scheduleid FROM appointment WHERE status='success' AND scheduleid IS NOT NULL) THEN 'Selesai'
        ELSE 'Dibatalkan'
    END as status
    FROM schedule 
    WHERE schedule.docid = ? AND (schedule.scheduleid IN (SELECT DISTINCT scheduleid FROM appointment WHERE status IN ('confirmed','success','cancelled') AND scheduleid IS NOT NULL))
    ORDER BY schedule.scheduledate DESC";
$stmtSession = $database->prepare($sqlSession);
$stmtSession->bind_param('i', $userid);
$stmtSession->execute();
$resSession = $stmtSession->get_result();
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
                <td class="menu-btn menu-icon-dashbord " >
                    <a href="index.php" class="non-style-link-menu "><div><p class="menu-text">Dasbor</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-appoinment  ">
                    <a href="appointment.php" class="non-style-link-menu"><div><p class="menu-text">Janji Temu Saya</p></div></a>
                </td>
            </tr>
            <tr class="menu-row" >
                <td class="menu-btn menu-icon-session">
                    <a href="schedule.php" class="non-style-link-menu"><div><p class="menu-text">Sesi Saya</p></div></a>
                </td>
            </tr>
            <tr class="menu-row" >
                <td class="menu-btn menu-icon-patient">
                    <a href="patient.php" class="non-style-link-menu"><div><p class="menu-text">Pasien Saya</p></div></a>
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
                <td><p style="font-size:23px;font-weight:600;">Riwayat Saya</p></td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="tab-container">
                        <button class="tab-btn active" onclick="showTab('appointments')">Janji Temu</button>
                        <button class="tab-btn" onclick="showTab('sessions')">Sesi</button>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <!-- Appointments Tab -->
                    <div id="appointments-tab" class="tab-content active">
                        <div class="abc scroll">
                            <table width="95%" class="sub-table scrolldown" border="0">
                                <thead>
                                    <tr>
                                        <th>Janji Temu #</th>
                                        <th>Pasien</th>
                                        <th>Sesi</th>
                                        <th>Terjadwal</th>
                                        <th>Dipesan Pada</th>
                                        <th>Status</th>
                                        <th>Alasan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                if($res->num_rows==0){
                                    echo '<tr><td colspan="7"><center><p class="heading-main12">Tidak ada riwayat ditemukan.</p></center></td></tr>';
                                } else {
                                    while($row = $res->fetch_assoc()){
                                        echo '<tr>';
                                        echo '<td style="text-align:center;">'.htmlspecialchars($row['apponum']).'</td>';
                                        echo '<td>'.htmlspecialchars($row['pname']).'</td>';
                                        echo '<td>'.htmlspecialchars($row['title']).'</td>';
                                        echo '<td style="text-align:center;">'.htmlspecialchars($row['scheduledate']).' '.htmlspecialchars(substr($row['scheduletime'] ?? '',0,5)).'</td>';
                                        echo '<td style="text-align:center;">'.htmlspecialchars($row['appodate']).'</td>';
                                        // Display status with proper formatting
                                        $statusDisplay = $row['status'];
                                        if($row['status'] == 'success'){
                                            $statusDisplay = '<span style="color:#28a745;font-weight:bold;">Selesai</span>';
                                        } elseif($row['status'] == 'cancelled'){
                                            $statusDisplay = '<span style="color:#dc3545;">Dibatalkan</span>';
                                        } elseif($row['status'] == 'rejected'){
                                            $statusDisplay = '<span style="color:#dc3545;">Ditolak</span>';
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
                    </div>
                    
                    <!-- Sessions Tab -->
                    <div id="sessions-tab" class="tab-content">
                        <div class="abc scroll">
                            <table width="95%" class="sub-table scrolldown" border="0">
                                <thead>
                                    <tr>
                                        <th>ID Sesi</th>
                                        <th>Judul</th>
                                        <th>Tanggal Terjadwal</th>
                                        <th>Waktu Terjadwal</th>
                                        <th>Maksimal Pasien</th>
                                        <th>Terdaftar</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                if($resSession->num_rows==0){
                                    echo '<tr><td colspan="7"><center><p class="heading-main12">No session history found.</p></center></td></tr>';
                                } else {
                                    while($row = $resSession->fetch_assoc()){
                                        echo '<tr>';
                                        echo '<td style="text-align:center;">'.htmlspecialchars($row['scheduleid']).'</td>';
                                        echo '<td>'.htmlspecialchars($row['title']).'</td>';
                                        echo '<td style="text-align:center;">'.htmlspecialchars($row['scheduledate']).'</td>';
                                        echo '<td style="text-align:center;">'.htmlspecialchars(substr($row['scheduletime'],0,5)).'</td>';
                                        echo '<td style="text-align:center;">'.htmlspecialchars($row['nop']).'</td>';
                                        echo '<td style="text-align:center;">'.htmlspecialchars($row['booked']).'</td>';
                                        // Color code status
                                        $statusColor = '#28a745'; // default green
                                        if($row['status'] == 'Berjalan'){
                                            $statusColor = '#ff9800'; // orange for running
                                        } elseif($row['status'] == 'Selesai'){
                                            $statusColor = '#28a745'; // green for completed
                                        } elseif($row['status'] == 'Dibatalkan'){
                                            $statusColor = '#dc3545'; // red for cancelled
                                        }
                                        echo '<td style="text-align:center;"><span style="color:'.$statusColor.';font-weight:bold;">'.htmlspecialchars($row['status']).'</span></td>';;
                                        echo '</tr>';
                                    }
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>
<script>
function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    document.getElementById(tabName + '-tab').classList.add('active');
    event.target.classList.add('active');
}
</script>
</body>
</html>
