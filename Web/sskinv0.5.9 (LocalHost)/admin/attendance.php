<?php
session_start();

if(isset($_SESSION["user"])){
    if(($_SESSION["user"])=="" or $_SESSION['usertype']!='a'){
        header("location: ../login.php");
        exit();
    }else{
        $useremail=$_SESSION["user"];
    }
}else{
    header("location: ../login.php");
    exit();
}

include("../connection.php");

date_default_timezone_set('Asia/Jakarta');

// Get current month/year from GET or use today
$currentMonth = isset($_GET['month']) ? intval($_GET['month']) : intval(date('n'));
$currentYear = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Calculate previous and next month
$prevMonth = $currentMonth - 1;
$prevYear = $currentYear;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $currentMonth + 1;
$nextYear = $currentYear;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

// Get month name in Indonesian
$monthNames = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$monthName = $monthNames[$currentMonth];

// Get calendar data
$firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$daysInMonth = date('t', $firstDay);
$dayOfWeek = date('N', $firstDay); // 1 (Mon) to 7 (Sun)

$today = date('Y-m-d');

// Initialize filter variables
$filterDate = isset($_GET['date']) ? $_GET['date'] : $today;
$filterDoctor = isset($_GET['docid']) ? intval($_GET['docid']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">  
    <link rel="stylesheet" href="../css/main.css">  
    <link rel="stylesheet" href="../css/admin.css">
        
    <title>Kalender Kehadiran Dokter</title>
    <style>
        .calendar-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }
        .calendar-title {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }
        .calendar-nav {
            display: flex;
            gap: 15px;
        }
        .nav-btn {
            background: linear-gradient(135deg, #0ea5e9, #7c3aed);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(14,165,233,0.3);
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }
        .calendar-day-header {
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
            padding: 12px;
            text-align: center;
            font-weight: 700;
            color: #475569;
            border-radius: 8px;
            font-size: 14px;
        }
        .calendar-cell {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            min-height: 100px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        .calendar-cell:hover {
            border-color: #0ea5e9;
            box-shadow: 0 4px 12px rgba(14,165,233,0.2);
            transform: translateY(-2px);
        }
        .calendar-cell.today {
            background: linear-gradient(135deg, #e0f2fe, #ddd6fe);
            border-color: #0ea5e9;
        }
        .calendar-cell.other-month {
            opacity: 0.4;
            cursor: default;
        }
        .calendar-cell.other-month:hover {
            transform: none;
            box-shadow: none;
            border-color: #e2e8f0;
        }
        .cell-date {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }
        .cell-summary {
            display: flex;
            flex-direction: column;
            gap: 5px;
            font-size: 12px;
        }
        .summary-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        .dot-present { background: #22c55e; }
        .dot-absent { background: #ef4444; }
        .dot-pending { background: #f59e0b; }
        
        .calendar-legend {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 80%;
            max-width: 800px;
            max-height: 80vh;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: slideIn 0.3s;
        }
        .modal-header {
            background: linear-gradient(135deg, #0ea5e9, #7c3aed);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h2 {
            margin: 0;
            font-size: 20px;
        }
        .close {
            color: white;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        .close:hover {
            opacity: 0.8;
        }
        .modal-body {
            padding: 25px;
            max-height: 60vh;
            overflow-y: auto;
        }
        .doctor-item {
            display: flex;
            flex-direction: column;
            padding: 20px;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            border-radius: 12px;
            margin-bottom: 15px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s;
        }
        .doctor-item:hover {
            border-color: #0ea5e9;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.15);
        }
        .doctor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
        }
        .doctor-info {
            flex: 1;
        }
        .doctor-name {
            font-weight: 700;
            font-size: 18px;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .doctor-specialty {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }
        .session-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-completed {
            background: #22c55e;
            color: white;
        }
        .badge-pending {
            background: #f59e0b;
            color: white;
        }
        .badge-failed {
            background: #ef4444;
            color: white;
        }
        .session-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-top: 12px;
        }
        .detail-item {
            display: flex;
            align-items: start;
            gap: 8px;
        }
        .detail-icon {
            font-size: 16px;
            margin-top: 2px;
        }
        .detail-content {
            flex: 1;
        }
        .detail-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .detail-value {
            font-size: 14px;
            color: #0f172a;
            font-weight: 600;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="menu">
            <table class="menu-container" border="0">
                <tr>
                    <td style="padding:6px" colspan="2">
                        <table border="0" class="profile-container">
                            <tr>
                                <td width="30%" style="padding-left:20px;">
                                    <img src="../img/user.png" alt="" width="100%" style="border-radius:50%">
                                </td>
                                <td style="padding:0px;margin:0px;">
                                    <p class="profile-title">Administrator</p>
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
                                    <a href="../logout.php"><input type="button" value="Keluar" class="logout-btn btn-gradient-red btn-small"></a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-dashbord">
                        <a href="index.php" class="non-style-link-menu"><div><p class="menu-text">Dasbor</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-doctor">
                        <a href="doctors.php" class="non-style-link-menu"><div><p class="menu-text">Dokter</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-session">
                        <a href="schedule.php" class="non-style-link-menu"><div><p class="menu-text">Jadwal Sesi</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-session menu-active menu-icon-session-active">
                        <a href="attendance.php" class="non-style-link-menu non-style-link-menu-active"><div><p class="menu-text">Kehadiran</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-appoinment">
                        <a href="appointment.php" class="non-style-link-menu"><div><p class="menu-text">Janji Temu</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-patient">
                        <a href="patient.php" class="non-style-link-menu"><div><p class="menu-text">Pasien</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-history">
                        <a href="history.php" class="non-style-link-menu"><div><p class="menu-text">Riwayat</p></div></a>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="dash-body">
            <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;">
                <tr>
                    <td width="13%">
                        <a href="schedule.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Kembali</font></button></a>
                    </td>
                    <td>
                        <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Kalender Kehadiran Dokter</p>
                    </td>
                    <td width="15%">
                        <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">
                            Tanggal Hari Ini
                        </p>
                        <p class="heading-sub12" style="padding: 0;margin: 0;">
                            <?php echo date('d-m-Y'); ?>
                        </p>
                    </td>
                    <td width="10%">
                        <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
                    </td>
                </tr>
                
                <tr>
                    <td colspan="4" style="padding-top: 30px;">
                        <div class="calendar-container">
                            <div class="calendar-header">
                                <h2 class="calendar-title"><?php echo $monthName . ' ' . $currentYear; ?></h2>
                                <div class="calendar-nav">
                                    <a href="export_attendance.php?month=<?php echo $currentMonth; ?>&year=<?php echo $currentYear; ?>">
                                        <button class="nav-btn" style="background: linear-gradient(135deg, #22c55e, #16a34a);">
                                            📊 Export Laporan
                                        </button>
                                    </a>
                                    <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>">
                                        <button class="nav-btn">← Bulan Sebelumnya</button>
                                    </a>
                                    <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>">
                                        <button class="nav-btn">Bulan Berikutnya →</button>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="calendar-grid">
                                <!-- Day headers -->
                                <div class="calendar-day-header">Senin</div>
                                <div class="calendar-day-header">Selasa</div>
                                <div class="calendar-day-header">Rabu</div>
                                <div class="calendar-day-header">Kamis</div>
                                <div class="calendar-day-header">Jumat</div>
                                <div class="calendar-day-header">Sabtu</div>
                                <div class="calendar-day-header">Minggu</div>
                                
                                <?php
                                // Fill empty cells before first day
                                for ($i = 1; $i < $dayOfWeek; $i++) {
                                    // Get last month's days
                                    $lastMonth = $currentMonth - 1;
                                    $lastMonthYear = $currentYear;
                                    if ($lastMonth < 1) {
                                        $lastMonth = 12;
                                        $lastMonthYear--;
                                    }
                                    $daysInLastMonth = date('t', mktime(0, 0, 0, $lastMonth, 1, $lastMonthYear));
                                    $dayNum = $daysInLastMonth - ($dayOfWeek - $i) + 1;
                                    echo '<div class="calendar-cell other-month">';
                                    echo '<div class="cell-date">' . $dayNum . '</div>';
                                    echo '</div>';
                                }
                                
                                // Loop through all days in month
                                for ($day = 1; $day <= $daysInMonth; $day++) {
                                    $date = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                                    $isToday = ($date === $today) ? 'today' : '';
                                    
                                    // Get attendance stats for this date based on appointment status
                                    $statsQuery = "
                                        SELECT 
                                            COUNT(DISTINCT CASE 
                                                WHEN EXISTS (
                                                    SELECT 1 FROM appointment a 
                                                    WHERE a.scheduleid = s.scheduleid 
                                                    AND a.status = 'success'
                                                ) THEN s.scheduleid 
                                            END) as total_present,
                                            COUNT(DISTINCT CASE 
                                                WHEN EXISTS (
                                                    SELECT 1 FROM appointment a 
                                                    WHERE a.scheduleid = s.scheduleid 
                                                    AND a.status = 'cancelled'
                                                ) THEN s.scheduleid 
                                            END) as total_absent,
                                            COUNT(DISTINCT CASE 
                                                WHEN NOT EXISTS (
                                                    SELECT 1 FROM appointment a 
                                                    WHERE a.scheduleid = s.scheduleid 
                                                    AND (a.status = 'success' OR a.status = 'cancelled')
                                                ) THEN s.scheduleid 
                                            END) as total_pending,
                                            COUNT(DISTINCT s.scheduleid) as total_doctors
                                        FROM schedule s
                                        LEFT JOIN doctor d ON s.docid = d.docid
                                        WHERE s.scheduledate = '$date'
                                    ";
                                    $statsResult = $database->query($statsQuery);
                                    $dayStats = $statsResult->fetch_assoc();
                                    
                                    echo '<div class="calendar-cell ' . $isToday . '" onclick="openDateModal(\'' . $date . '\')">';
                                    echo '<div class="cell-date">' . $day . '</div>';
                                    
                                    // Debug info for date 26
                                    if ($day == 26) {
                                        echo '<!-- DEBUG: Date 26 - Doctors: ' . ($dayStats['total_doctors'] ?? 0) . ', Details: ' . ($dayStats['doctor_details'] ?? 'none') . ' -->';
                                    }
                                    
                                    if ($dayStats['total_doctors'] > 0) {
                                        echo '<div class="cell-summary">';
                                        if ($dayStats['total_present'] > 0) {
                                            echo '<div class="summary-item">';
                                            echo '<span class="status-dot dot-present"></span>';
                                            echo '<span>Hadir: ' . $dayStats['total_present'] . '</span>';
                                            echo '</div>';
                                        }
                                        if ($dayStats['total_absent'] > 0) {
                                            echo '<div class="summary-item">';
                                            echo '<span class="status-dot dot-absent"></span>';
                                            echo '<span>Tidak: ' . $dayStats['total_absent'] . '</span>';
                                            echo '</div>';
                                        }
                                        if ($dayStats['total_pending'] > 0) {
                                            echo '<div class="summary-item">';
                                            echo '<span class="status-dot dot-pending"></span>';
                                            echo '<span>Pending: ' . $dayStats['total_pending'] . '</span>';
                                            echo '</div>';
                                        }
                                        echo '</div>';
                                    } else {
                                        echo '<div class="cell-summary" style="color: #94a3b8; font-size: 11px;">Tidak ada jadwal</div>';
                                    }
                                    
                                    echo '</div>';
                                }
                                
                                // Fill remaining cells
                                $totalCells = $dayOfWeek + $daysInMonth - 1;
                                $remainingCells = 7 - ($totalCells % 7);
                                if ($remainingCells < 7) {
                                    for ($i = 1; $i <= $remainingCells; $i++) {
                                        echo '<div class="calendar-cell other-month">';
                                        echo '<div class="cell-date">' . $i . '</div>';
                                        echo '</div>';
                                    }
                                }
                                ?>
                            </div>
                            
                            <div class="calendar-legend">
                                <div class="legend-item">
                                    <span class="status-dot dot-present"></span>
                                    <span>Hadir</span>
                                </div>
                                <div class="legend-item">
                                    <span class="status-dot dot-absent"></span>
                                    <span>Tidak Hadir</span>
                                </div>
                                <div class="legend-item">
                                    <span class="status-dot dot-pending"></span>
                                    <span>Pending</span>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <!-- Modal -->
    <div id="attendanceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Kehadiran Dokter</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body" id="modalBody">
                <p style="text-align: center; color: #64748b;">Loading...</p>
            </div>
        </div>
    </div>
    
    <script>
        let currentDate = '';
        
        function openDateModal(date) {
            currentDate = date;
            const modal = document.getElementById('attendanceModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            // Format date for display
            const dateObj = new Date(date + 'T00:00:00');
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const dateStr = dateObj.toLocaleDateString('id-ID', options);
            
            modalTitle.textContent = 'Kehadiran Dokter - ' + dateStr;
            modal.style.display = 'block';
            
            // Load doctors for this date
            loadDoctors(date);
        }
        
        function closeModal() {
            document.getElementById('attendanceModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('attendanceModal');
            if (event.target == modal) {
                closeModal();
            }
        }
        
        function loadDoctors(date) {
            const modalBody = document.getElementById('modalBody');
            modalBody.innerHTML = '<p style="text-align: center; color: #64748b;">Memuat data...</p>';
            
            fetch('get_attendance_data.php?date=' + date)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data); // Debug log
                    
                    if (data.success) {
                        let html = '';
                        if (data.doctors.length === 0) {
                            html = '<p style="text-align: center; color: #64748b;">Tidak ada jadwal dokter pada tanggal ini.</p>';
                        } else {
                            data.doctors.forEach(doctor => {
                                // Determine status badge
                                let statusBadge = '';
                                let statusText = '';
                                if (doctor.session_status === 'completed') {
                                    statusBadge = 'badge-completed';
                                    statusText = '✓ Selesai';
                                } else if (doctor.session_status === 'failed') {
                                    statusBadge = 'badge-failed';
                                    statusText = '✗ Gagal';
                                } else {
                                    statusBadge = 'badge-pending';
                                    statusText = '⏳ Sedang Berjalan';
                                }
                                
                                // Format time to HH:MM only
                                const timeFormatted = doctor.scheduletime ? doctor.scheduletime.substring(0, 5) : '-';
                                
                                html += `
                                    <div class="doctor-item">
                                        <div class="doctor-header">
                                            <div class="doctor-info">
                                                <div class="doctor-name">${doctor.docname}</div>
                                                <div class="doctor-specialty">${doctor.specialties}</div>
                                            </div>
                                            <div class="session-badge ${statusBadge}">${statusText}</div>
                                        </div>
                                        <div class="session-details">
                                            <div class="detail-item">
                                                <div class="detail-icon">📋</div>
                                                <div class="detail-content">
                                                    <div class="detail-label">Layanan Sesi</div>
                                                    <div class="detail-value">${doctor.title || '-'}</div>
                                                </div>
                                            </div>
                                            <div class="detail-item">
                                                <div class="detail-icon">🕐</div>
                                                <div class="detail-content">
                                                    <div class="detail-label">Pada Pukul</div>
                                                    <div class="detail-value">${timeFormatted}</div>
                                                </div>
                                            </div>
                                            <div class="detail-item">
                                                <div class="detail-icon">👥</div>
                                                <div class="detail-content">
                                                    <div class="detail-label">Kapasitas Pasien</div>
                                                    <div class="detail-value">${doctor.nop || 0} orang</div>
                                                </div>
                                            </div>
                                            <div class="detail-item">
                                                <div class="detail-icon">✅</div>
                                                <div class="detail-content">
                                                    <div class="detail-label">Pasien Terdaftar</div>
                                                    <div class="detail-value">${doctor.confirmed_patients || 0} orang</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                        }
                        modalBody.innerHTML = html;
                    } else {
                        modalBody.innerHTML = '<p style="text-align: center; color: #ef4444;">Error: ' + (data.message || 'Unknown error') + '</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = '<p style="text-align: center; color: #ef4444;">Terjadi kesalahan: ' + error.message + '</p>';
                });
        }
    </script>
</body>
</html>
                    <td width="15%">
                        <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">
                            Tanggal Hari Ini
                        </p>
                        <p class="heading-sub12" style="padding: 0;margin: 0;">
                            <?php echo $today; ?>
                        </p>
                    </td>
                    <td width="10%">
                        <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
                    </td>
                </tr>
                
                <!-- Statistics Cards -->
                <tr>
                    <td colspan="4" style="padding: 20px;">
                        <div style="display: flex; gap: 20px;">
                            <div class="stats-card" style="flex: 1;">
                                <h3>Dokter Hadir</h3>
                                <div class="number" style="color: #22c55e;"><?php echo $stats['total_present'] ?? 0; ?></div>
                            </div>
                            <div class="stats-card" style="flex: 1;">
                                <h3>Dokter Tidak Hadir</h3>
                                <div class="number" style="color: #ef4444;"><?php echo $stats['total_absent'] ?? 0; ?></div>
                            </div>
                            <div class="stats-card" style="flex: 1;">
                                <h3>Belum Konfirmasi</h3>
                                <div class="number" style="color: #f59e0b;"><?php echo $stats['total_pending'] ?? 0; ?></div>
                            </div>
                        </div>
                    </td>
                </tr>
                
                <!-- Filter Section -->
                <tr>
                    <td colspan="4" style="padding: 0 20px;">
                        <div class="filter-section">
                            <form method="GET" action="" style="display: flex; gap: 15px; align-items: center;">
                                <div>
                                    <label style="font-size: 14px; margin-bottom: 5px; display: block;">Tanggal:</label>
                                    <input type="date" name="date" value="<?php echo $filterDate; ?>" class="input-text" style="width: 200px;">
                                </div>
                                <div>
                                    <label style="font-size: 14px; margin-bottom: 5px; display: block;">Dokter:</label>
                                    <select name="docid" class="box" style="width: 250px;">
                                        <option value="0">Semua Dokter</option>
                                        <?php
                                        $doctorList = $database->query("SELECT docid, docname FROM doctor ORDER BY docname ASC");
                                        while($doc = $doctorList->fetch_assoc()){
                                            $selected = ($filterDoctor == $doc['docid']) ? 'selected' : '';
                                            echo '<option value="'.$doc['docid'].'" '.$selected.'>'.htmlspecialchars($doc['docname']).'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div style="margin-top: 20px;">
                                    <input type="submit" value="Filter" class="login-btn btn-primary btn" style="padding: 10px 30px;">
                                    <a href="attendance.php"><button type="button" class="login-btn btn-primary-soft btn" style="padding: 10px 20px;">Reset</button></a>
                                </div>
                            </form>
                        </div>
                    </td>
                </tr>
                
                <!-- Attendance Table -->
                <tr>
                    <td colspan="4">
                        <center>
                            <div class="abc scroll">
                                <table width="93%" class="sub-table scrolldown" border="0">
                                    <thead>
                                        <tr>
                                            <th class="table-headin">Nama Dokter</th>
                                            <th class="table-headin">Judul Shift</th>
                                            <th class="table-headin">Tanggal & Waktu</th>
                                            <th class="table-headin">Status Kehadiran</th>
                                            <th class="table-headin">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Build query with filters
                                        $sqlWhere = "WHERE schedule.scheduledate = '$filterDate'";
                                        if($filterDoctor > 0){
                                            $sqlWhere .= " AND doctor.docid = $filterDoctor";
                                        }
                                        
                                        $sqlmain = "
                                            SELECT schedule.*, doctor.docname, doctor.docemail 
                                            FROM schedule 
                                            INNER JOIN doctor ON schedule.docid = doctor.docid 
                                            $sqlWhere
                                            ORDER BY doctor.docname ASC, schedule.scheduletime ASC
                                        ";
                                        
                                        $result = $database->query($sqlmain);

                                        if($result->num_rows==0){
                                            echo '<tr>
                                                <td colspan="5">
                                                    <br><br><br><br>
                                                    <center>
                                                        <img src="../img/notfound.svg" width="25%">
                                                        <br>
                                                        <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Tidak ada jadwal ditemukan untuk filter ini!</p>
                                                    </center>
                                                    <br><br><br><br>
                                                </td>
                                            </tr>';
                                        }else{
                                            while($row = $result->fetch_assoc()){
                                                $scheduleid = $row["scheduleid"];
                                                $docname = $row["docname"];
                                                $title = $row["title"];
                                                $scheduledate = $row["scheduledate"];
                                                $scheduletime = $row["scheduletime"];
                                                $attendance_status = $row["attendance_status"] ?? 'pending';
                                                
                                                // Determine badge class
                                                $badge_class = 'attendance-pending';
                                                $status_text = 'Menunggu';
                                                if($attendance_status == 'present'){
                                                    $badge_class = 'attendance-present';
                                                    $status_text = 'Hadir';
                                                }elseif($attendance_status == 'absent'){
                                                    $badge_class = 'attendance-absent';
                                                    $status_text = 'Tidak Hadir';
                                                }
                                                
                                                echo '<tr>
                                                    <td>&nbsp;'.htmlspecialchars(substr($docname ?? '',0,30)).'</td>
                                                    <td>&nbsp;'.htmlspecialchars(substr($title ?? '',0,30)).'</td>
                                                    <td style="text-align:center;">'.substr($scheduledate ?? '',0,10).' '.substr($scheduletime ?? '',0,5).'</td>
                                                    <td style="text-align:center;">
                                                        <span class="attendance-badge '.$badge_class.'" id="badge-'.$scheduleid.'">'.$status_text.'</span>
                                                    </td>
                                                    <td style="text-align:center;">
                                                        <button class="btn-attendance btn-present" onclick="updateAttendance('.$scheduleid.', \'present\')">Hadir</button>
                                                        <button class="btn-attendance btn-absent" onclick="updateAttendance('.$scheduleid.', \'absent\')">Tidak Hadir</button>
                                                    </td>
                                                </tr>';
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </center>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <script>
    function updateAttendance(scheduleid, status) {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "update_attendance_admin.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if(response.success) {
                        var badge = document.getElementById('badge-' + scheduleid);
                        badge.className = 'attendance-badge';
                        
                        if(status === 'present') {
                            badge.classList.add('attendance-present');
                            badge.textContent = 'Hadir';
                        } else if(status === 'absent') {
                            badge.classList.add('attendance-absent');
                            badge.textContent = 'Tidak Hadir';
                        }
                        
                        alert('Status kehadiran berhasil diupdate!');
                        location.reload(); // Reload untuk update statistik
                    } else {
                        alert('Gagal update: ' + response.message);
                    }
                } catch(e) {
                    alert('Error: ' + e.message);
                }
            }
        };
        
        xhr.send("scheduleid=" + scheduleid + "&attendance_status=" + status);
    }
    </script>
</body>
</html>
