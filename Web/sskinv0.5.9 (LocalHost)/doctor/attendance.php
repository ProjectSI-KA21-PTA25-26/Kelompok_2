<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">  
    <link rel="stylesheet" href="../css/main.css">  
    <link rel="stylesheet" href="../css/admin.css">
        
    <title>Attendance Management</title>
    <style>
        .attendance-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }
        .attendance-present {
            background-color: #22c55e;
            color: white;
        }
        .attendance-absent {
            background-color: #ef4444;
            color: white;
        }
        .attendance-pending {
            background-color: #f59e0b;
            color: white;
        }
        .btn-attendance {
            padding: 8px 15px;
            margin: 2px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
        }
        .btn-present {
            background-color: #22c55e;
            color: white;
        }
        .btn-present:hover {
            background-color: #16a34a;
        }
        .btn-absent {
            background-color: #ef4444;
            color: white;
        }
        .btn-absent:hover {
            background-color: #dc2626;
        }
    </style>
</head>
<body>
    <?php
    session_start();

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='d'){
            header("location: ../login.php");
        }else{
            $useremail=$_SESSION["user"];
        }
    }else{
        header("location: ../login.php");
    }

    include("../connection.php");
    $userrow = $database->query("select * from doctor where docemail='$useremail'");
    $userfetch=$userrow->fetch_assoc();
    $userid= $userfetch["docid"];
    $username=$userfetch["docname"];
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
                    <td class="menu-btn menu-icon-dashbord" >
                        <a href="index.php" class="non-style-link-menu"><div><p class="menu-text">Dasbor</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-appoinment">
                        <a href="appointment.php" class="non-style-link-menu"><div><p class="menu-text">Janji Temu Saya</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row" >
                    <td class="menu-btn menu-icon-session menu-active menu-icon-session-active">
                        <a href="schedule.php" class="non-style-link-menu non-style-link-menu-active"><div><p class="menu-text">Sesi Saya</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row" >
                    <td class="menu-btn menu-icon-patient">
                        <a href="patient.php" class="non-style-link-menu"><div><p class="menu-text">Pasien Saya</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-history">
                        <a href="history.php" class="non-style-link-menu"><div><p class="menu-text">Riwayat</p></div></a>
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
            <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;">
                <tr>
                    <td width="13%">
                        <a href="schedule.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Kembali</font></button></a>
                    </td>
                    <td>
                        <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Kelola Kehadiran Shift</p>
                    </td>
                    <td width="15%">
                        <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">
                            Tanggal Hari Ini
                        </p>
                        <p class="heading-sub12" style="padding: 0;margin: 0;">
                            <?php 
                                date_default_timezone_set('Asia/Jakarta');
                                $today = date('Y-m-d');
                                echo $today;
                            ?>
                        </p>
                    </td>
                    <td width="10%">
                        <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
                    </td>
                </tr>
                
                <tr>
                    <td colspan="4">
                        <center>
                            <div class="abc scroll">
                                <table width="93%" class="sub-table scrolldown" border="0">
                                    <thead>
                                        <tr>
                                            <th class="table-headin">No.</th>
                                            <th class="table-headin">Judul Sesi</th>
                                            <th class="table-headin">Tanggal & Waktu</th>
                                            <th class="table-headin">Status Kehadiran</th>
                                            <th class="table-headin">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Get all schedules for this doctor
                                        $sqlmain = "SELECT * FROM schedule WHERE docid=? ORDER BY scheduledate DESC, scheduletime DESC";
                                        $stmt = $database->prepare($sqlmain);
                                        $stmt->bind_param("i", $userid);
                                        $stmt->execute();
                                        $result = $stmt->get_result();

                                        if($result->num_rows==0){
                                            echo '<tr>
                                                <td colspan="5">
                                                    <br><br><br><br>
                                                    <center>
                                                        <img src="../img/notfound.svg" width="25%">
                                                        <br>
                                                        <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Tidak ada jadwal shift ditemukan!</p>
                                                    </center>
                                                    <br><br><br><br>
                                                </td>
                                            </tr>';
                                        }else{
                                            $row_number = 1;
                                            while($row = $result->fetch_assoc()){
                                                $scheduleid = $row["scheduleid"];
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
                                                    <td style="text-align:center;">'.$row_number.'</td>
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
                                                $row_number++;
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
        // Send AJAX request
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "update_attendance.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if(response.success) {
                        // Update badge
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
