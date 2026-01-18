<?php
session_start();

if(isset($_SESSION["user"])){
    if(($_SESSION["user"])=="" or $_SESSION['usertype']!='p'){
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
$sqlmain= "select * from patient where pemail=?";
$stmt = $database->prepare($sqlmain);
$stmt->bind_param("s",$useremail);
$stmt->execute();
$userrow = $stmt->get_result();
$userfetch=$userrow->fetch_assoc();
$userid= $userfetch["pid"];
$username=$userfetch["pname"];
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
        
    <title>Harga Layanan</title>
    <style>
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }
        .service-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border: 2px solid #e2e8f0;
        }
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(14,165,233,0.2);
            border-color: #0ea5e9;
        }
        .service-title {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .service-desc {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        .service-price {
            font-size: 24px;
            font-weight: 700;
            color: #0ea5e9;
            margin-bottom: 20px;
        }
        .btn-select-service {
            width: 100%;
            background: linear-gradient(135deg, #0ea5e9, #7c3aed);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-select-service:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(14,165,233,0.3);
        }
        
        /* Reserve Popup Styles */
        .reserve-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }
        
        .reserve-overlay.active {
            display: flex;
        }
        
        .reserve-card {
            background: white;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .reserve-header {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .reserve-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            line-height: 1;
        }
        
        .reserve-close:hover {
            color: #333;
        }
        
        .reserve-body {
            padding: 25px;
        }
        
        .reserve-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        /* Shift Button Styles - IMPLEMENTASI DARI APPOINTMENT.PHP */
        .shift-buttons{
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .shift-buttons input[type="radio"]{
            display: none;
        }
        .shift-label{
            display: block;
            padding: 15px 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
            min-width: 120px;
        }
        .shift-buttons input[type="radio"]:checked + .shift-label{
            background: #0ea5e9;
            color: white;
            border-color: #0ea5e9;
        }
        .shift-buttons input[type="radio"]:disabled + .shift-label{
            background: #f5f5f5;
            color: #ccc;
            cursor: not-allowed;
            border-color: #eee;
        }
        
        /* Modern Select Styles */
        .modern-select {
            position: relative;
            width: 100%;
        }
        
        .modern-select__field {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .modern-select__input {
            flex: 1;
            padding: 10px 35px 10px 10px;
            border: 2px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }
        
        .modern-select__input:focus {
            border-color: #007bff;
            outline: none;
        }
        
        .modern-select__caret {
            position: absolute;
            right: 10px;
            font-size: 12px;
            color: #666;
            pointer-events: none;
            transition: transform 0.2s;
        }
        
        .modern-select.open .modern-select__caret {
            transform: rotate(180deg);
        }
        
        .modern-select__list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #ccc;
            border-top: none;
            border-radius: 0 0 6px 6px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .modern-select.open .modern-select__list {
            display: block;
        }
        
        .modern-select__option {
            padding: 10px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .modern-select__option:hover {
            background-color: #f8f9fa;
        }
        
        .modern-select__option small {
            color: #666;
            font-size: 0.85em;
        }
        
        /* Shift Buttons */
        .shift-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .shift-buttons input[type="radio"] {
            display: none;
        }
        
        .shift-label {
            padding: 12px 16px;
            border: 2px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
            font-size: 14px;
            line-height: 1.2;
        }
        
        .shift-label:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        
        .shift-buttons input[type="radio"]:checked + .shift-label {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }
        
        .shift-buttons input[type="radio"]:disabled + .shift-label {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
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
                                    <p class="profile-title"><?php echo substr($username,0,13)  ?>..</p>
                                    <p class="profile-subtitle"><?php echo substr($useremail,0,22)  ?></p>
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
                                    <a href="../logout.php" ><input type="button" value="Keluar" class="logout-btn btn-gradient-red btn-small"></a>
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
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-dashboard menu-active menu-icon-dashboard-active">
                        <a href="services.php" class="non-style-link-menu non-style-link-menu-active"><div><p class="menu-text">Harga Layanan</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row" >
                    <td class="menu-btn menu-icon-doctor">
                        <a href="doctors.php" class="non-style-link-menu"><div><p class="menu-text">Semua Dokter</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row" >
                    <td class="menu-btn menu-icon-appoinment">
                        <a href="appointment.php" class="non-style-link-menu"><div><p class="menu-text">Janji Temu Saya</p></div></a>
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
                        <a href="index.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Kembali</font></button></a>
                    </td>
                    <td>
                        <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Harga Layanan Kami</p>
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
                    <td colspan="4" style="padding-top:10px;">
                        <p class="heading-main12" style="margin-left: 45px;font-size:18px;color:rgb(49, 49, 49)">
                            Pilih layanan yang Anda inginkan dan buat janji temu dengan dokter kami
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <td colspan="4">
                        <div class="services-grid" style="padding: 20px 45px;">
                            <div class="service-card">
                                <h3 class="service-title">Detox Bronzer Facial</h3>
                                <p class="service-desc">Deep Clean, Bronze Glow Masker, Serum OxyJetPeel</p>
                                <p class="service-price">Rp 175.000</p>
                                <button class="btn-select-service" onclick="openReservePopup(1, 'Detox Bronzer Facial', 175000)">Pilih Layanan Ini</button>
                            </div>
                            
                            <div class="service-card">
                                <h3 class="service-title">Detox Radiant Glow Oxy</h3>
                                <p class="service-desc">Deep Clean, Serum OxyJetPeel, Sheet Mask</p>
                                <p class="service-price">Rp 275.000</p>
                                <button class="btn-select-service" onclick="openReservePopup(2, 'Detox Radiant Glow Oxy', 275000)">Pilih Layanan Ini</button>
                            </div>
                            
                            <div class="service-card">
                                <h3 class="service-title">Sensitif Skin Oxy</h3>
                                <p class="service-desc">Facial, Serum OxyJetPeel, Masker Peel off</p>
                                <p class="service-price">Rp 180.000</p>
                                <button class="btn-select-service" onclick="openReservePopup(3, 'Sensitif Skin Oxy', 180000)">Pilih Layanan Ini</button>
                            </div>
                            
                            <div class="service-card">
                                <h3 class="service-title">Glow Reshaping</h3>
                                <p class="service-desc">Facial, Serum Whitening, RF Full Face, Masker Peel off</p>
                                <p class="service-price">Rp 265.000</p>
                                <button class="btn-select-service" onclick="openReservePopup(4, 'Glow Reshaping', 265000)">Pilih Layanan Ini</button>
                            </div>
                            
                            <div class="service-card">
                                <h3 class="service-title">PeeLing Glow</h3>
                                <p class="service-desc">Facial, Peeling Whitening, Masker Peel Off</p>
                                <p class="service-price">Rp 260.000</p>
                                <button class="btn-select-service" onclick="openReservePopup(5, 'PeeLing Glow', 260000)">Pilih Layanan Ini</button>
                            </div>
                            
                            <div class="service-card">
                                <h3 class="service-title">PeeLing Acne</h3>
                                <p class="service-desc">Facial, Peeling Acne, Masker Peel off</p>
                                <p class="service-price">Rp 260.000</p>
                                <button class="btn-select-service" onclick="openReservePopup(6, 'PeeLing Acne', 260000)">Pilih Layanan Ini</button>
                            </div>
                            
                            <div class="service-card">
                                <h3 class="service-title">Platinum Peeling Glow IPL</h3>
                                <p class="service-desc">Facial, Peeling, Masker Peel off, IPL Face</p>
                                <p class="service-price">Rp 400.000</p>
                                <button class="btn-select-service" onclick="openReservePopup(7, 'Platinum Peeling Glow IPL', 400000)">Pilih Layanan Ini</button>
                            </div>
                            
                            <div class="service-card">
                                <h3 class="service-title">Glow Oxy Instant Rejuv</h3>
                                <p class="service-desc">Facial, Serum Ultrasound, Serum OxyJetPeel, IPL Face</p>
                                <p class="service-price">Rp 370.000</p>
                                <button class="btn-select-service" onclick="openReservePopup(8, 'Glow Oxy Instant Rejuv', 370000)">Pilih Layanan Ini</button>
                            </div>
                            
                            <div class="service-card">
                                <h3 class="service-title">Laser Flek on you</h3>
                                <p class="service-desc">Facial, Laser on spot, Serum OxyJetPeel, Masker Peel Off</p>
                                <p class="service-price">Rp 450.000</p>
                                <button class="btn-select-service" onclick="openReservePopup(9, 'Laser Flek on you', 450000)">Pilih Layanan Ini</button>
                            </div>
                            
                            <div class="service-card">
                                <h3 class="service-title">Laser Brightening Blackdoll</h3>
                                <p class="service-desc">Facial, Laser Blackdoll, Serum OxyJetPeel, Masker Peel off</p>
                                <p class="service-price">Rp 485.000</p>
                                <button class="btn-select-service" onclick="openReservePopup(10, 'Laser Brightening Blackdoll', 485000)">Pilih Layanan Ini</button>
                            </div>
                            
                            <div class="service-card">
                                <h3 class="service-title">Messo Needle WhiteGlow</h3>
                                <p class="service-desc">Facial, Serum Racikan, MicroNeedle, Sheet Mask</p>
                                <p class="service-price">Rp 770.000</p>
                                <button class="btn-select-service" onclick="openReservePopup(11, 'Messo Needle WhiteGlow', 770000)">Pilih Layanan Ini</button>
                            </div>
                            
                            <div class="service-card">
                                <h3 class="service-title">Messo Needle Acne No Way</h3>
                                <p class="service-desc">Facial, Serum Racikan, MicroNeedle, Sheet Mask</p>
                                <p class="service-price">Rp 500.000</p>
                                <button class="btn-select-service" onclick="openReservePopup(12, 'Messo Needle Acne No Way', 500000)">Pilih Layanan Ini</button>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Reserve Popup -->
    <div class="reserve-overlay" id="reserveOverlay" aria-hidden="true">
        <div class="reserve-card">
            <div class="reserve-header">
                <div>
                    <div style="font-size:18px;font-weight:700;">Buat Reservasi</div>
                    <div style="font-size:12px;opacity:0.9;">Isi form untuk mengirim permintaan janji</div>
                </div>
                <button class="reserve-close" type="button" id="closeReserve" aria-label="Tutup">×</button>
            </div>
            <div class="reserve-body">
                <form class="reserve-form" method="post" action="request-appointment.php">
                    <input type="hidden" name="request_submit" value="1">
                    <table width="100%" class="sub-table scrolldown" border="0">
                        <tr>
                            <td class="label-td">Pilih Layanan</td>
                            <td>
                                <input type="hidden" name="specialties" id="selectedServiceId">
                                <input type="text" class="input-text" id="selectedServiceName" readonly style="background-color: #f5f5f5;">
                                <input type="hidden" name="title" id="selectedServiceTitle">
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td">Pilih Dokter</td>
                            <td>
                                <div class="modern-select" id="modernDocSelect">
                                    <input type="hidden" name="docid" id="docidHidden">
                                    <div class="modern-select__field">
                                        <input type="text" class="modern-select__input" id="docidInput" placeholder="Pilih dokter" autocomplete="off">
                                        <span class="modern-select__caret" aria-hidden="true">▾</span>
                                    </div>
                                    <div class="modern-select__list" id="doctorsList">
                                        <!-- Doctors will be loaded here dynamically -->
                                    </div>
                                </div>
                                <div id="noDoctorsMessage" style="display:none;color:#666;margin-top:10px;font-style:italic;">
                                    Tidak Ada Dokter Tersedia
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td">Tanggal</td>
                            <td><input type="date" name="date" class="input-text" min="<?php echo date('Y-m-d'); ?>" required></td>
                        </tr>
                        <tr>
                            <td class="label-td">Shift</td>
                            <td>
                                <div class="shift-buttons">
                                    <input type="radio" id="shift1" name="time" value="10:00" required>
                                    <label for="shift1" class="shift-label">Shift 1<br>(10:00-12:00)</label>
                                    <input type="radio" id="shift2" name="time" value="12:00">
                                    <label for="shift2" class="shift-label">Shift 2<br>(12:00-14:00)</label>
                                    <input type="radio" id="shift3" name="time" value="15:00">
                                    <label for="shift3" class="shift-label">Shift 3<br>(15:00-17:00)</label>
                                    <input type="radio" id="shift4" name="time" value="17:00">
                                    <label for="shift4" class="shift-label">Shift 4<br>(17:00-19:00)</label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td">Jumlah Pasien</td>
                            <td><input type="number" name="nop" class="input-text" min="1" value="1"></td>
                        </tr>
                    </table>
                    <div class="reserve-actions">
                        <button type="button" class="login-btn btn-primary-soft btn" id="closeReserve2">Batal</button>
                        <button type="submit" class="login-btn btn-primary btn">Kirim Permintaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
function openReservePopup(serviceId, serviceName, price) {
    // Set service data
    document.getElementById('selectedServiceId').value = serviceId;
    document.getElementById('selectedServiceName').value = serviceName + ' - Rp ' + price.toLocaleString('id-ID');
    document.getElementById('selectedServiceTitle').value = serviceName;
    
    // Reset form
    document.getElementById('docidHidden').value = '';
    document.getElementById('docidInput').value = '';
    
    // Load doctors for this service
    loadDoctorsForService(serviceId);
    
    // Show popup
    var overlay = document.getElementById("reserveOverlay");
    overlay.classList.add("active");
    overlay.setAttribute("aria-hidden", "false");
    
    // Initialize date and update shift availability after popup opens
    setTimeout(function() {
        var dateInput = document.querySelector('input[name="date"]');
        if(dateInput && !dateInput.value) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }
        updateShiftAvailability();
    }, 100);
}

function loadDoctorsForService(serviceId) {
    var doctorsList = document.getElementById('doctorsList');
    var noDoctorsMessage = document.getElementById('noDoctorsMessage');
    var docSelect = document.getElementById('modernDocSelect');
    
    // Show loading
    doctorsList.innerHTML = '<div style="padding:10px;color:#666;">Loading...</div>';
    noDoctorsMessage.style.display = 'none';
    
    fetch('get_doctors_by_service.php?service_id=' + serviceId)
        .then(response => response.json())
        .then(data => {
            doctorsList.innerHTML = '';
            
            if (data.success && data.doctors && data.doctors.length > 0) {
                data.doctors.forEach(function(doctor) {
                    var option = document.createElement('div');
                    option.className = 'modern-select__option';
                    option.dataset.value = doctor.docid;
                    option.dataset.label = doctor.docname;
                    option.innerHTML = doctor.docname + '<small>' + doctor.docemail + '</small>';
                    
                    option.addEventListener('click', function() {
                        var val = this.dataset.value || "";
                        var label = this.dataset.label || this.textContent.trim();
                        document.getElementById('docidHidden').value = val;
                        document.getElementById('docidInput').value = label + " (" + (this.querySelector("small") ? this.querySelector("small").textContent : "") + ")";
                        docSelect.classList.remove('open');
                    });
                    
                    doctorsList.appendChild(option);
                });
                noDoctorsMessage.style.display = 'none';
            } else {
                noDoctorsMessage.style.display = 'block';
                doctorsList.innerHTML = '';
            }
        })
        .catch(error => {
            console.error('Error loading doctors:', error);
            doctorsList.innerHTML = '';
            noDoctorsMessage.style.display = 'block';
        });
}

// Fungsi untuk disable shift yang sudah lewat - IMPLEMENTASI DARI APPOINTMENT.PHP
function updateShiftAvailability(){
    var dateInput = document.querySelector('input[name="date"]');
    var shiftInputs = document.querySelectorAll('.shift-buttons input[type="radio"]');
    
    if(!dateInput || !dateInput.value) return;
    
    var selectedDate = new Date(dateInput.value);
    var now = new Date();
    
    // Set jam ke 0 untuk perbandingan tanggal saja
    var selectedDateOnly = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate());
    var todayOnly = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    
    // Jika tanggal yang dipilih bukan hari ini, enable semua shift
    if(selectedDateOnly.getTime() !== todayOnly.getTime()){
        shiftInputs.forEach(function(input){
            input.disabled = false;
        });
        return;
    }
    
    // Jika hari ini, cek waktu saat ini
    var currentHour = now.getHours();
    var currentMinute = now.getMinutes();
    var currentTimeInMinutes = currentHour * 60 + currentMinute;
    
    // Definisi shift (waktu akhir dalam menit dari tengah malam)
    var shifts = [
        {id: 'shift1', endTime: 12 * 60},      // Shift 1 berakhir jam 12:00
        {id: 'shift2', endTime: 14 * 60},      // Shift 2 berakhir jam 14:00
        {id: 'shift3', endTime: 17 * 60},      // Shift 3 berakhir jam 17:00
        {id: 'shift4', endTime: 19 * 60}       // Shift 4 berakhir jam 19:00
    ];
    
    shifts.forEach(function(shift){
        var input = document.getElementById(shift.id);
        if(input){
            // Disable jika waktu sekarang sudah melewati waktu akhir shift
            if(currentTimeInMinutes >= shift.endTime){
                input.disabled = true;
                input.checked = false; // Uncheck jika sedang dipilih
            } else {
                input.disabled = false;
            }
        }
    });
}

// Initialize popup functionality
(function(){
    var overlay = document.getElementById("reserveOverlay");
    var closeBtn = document.getElementById("closeReserve");
    var closeBtn2 = document.getElementById("closeReserve2");
    
    function closeModal(){
        overlay.classList.remove("active");
        overlay.setAttribute("aria-hidden", "true");
    }
    
    if(closeBtn){ closeBtn.addEventListener("click", closeModal); }
    if(closeBtn2){ closeBtn2.addEventListener("click", closeModal); }
    
    if(overlay){
        overlay.addEventListener("click", function(e){
            if(e.target === overlay){ closeModal(); }
        });
    }
    
    document.addEventListener("keydown", function(e){
        if(e.key === "Escape"){ closeModal(); }
    });

    // Modern select functionality
    var modernSelect = document.getElementById("modernDocSelect");
    if(modernSelect){
        var input = modernSelect.querySelector(".modern-select__input");
        var caret = modernSelect.querySelector(".modern-select__caret");
        var list = modernSelect.querySelector(".modern-select__list");

        function closeSelect(){
            modernSelect.classList.remove("open");
        }
        function openSelect(){
            modernSelect.classList.add("open");
        }
        
        if(caret) {
            caret.addEventListener("click", function(e){
                e.preventDefault();
                modernSelect.classList.contains("open") ? closeSelect() : openSelect();
                input.focus();
            });
        }
        
        if(input) {
            input.addEventListener("focus", function() {
                if(list.children.length > 0) openSelect();
            });
            
            input.addEventListener("input", function(){
                var term = input.value.toLowerCase();
                var options = modernSelect.querySelectorAll(".modern-select__option");
                options.forEach(function(opt){
                    var text = (opt.dataset.label + " " + (opt.querySelector("small")?.textContent || "")).toLowerCase();
                    opt.style.display = text.indexOf(term) !== -1 ? "flex" : "none";
                });
                if(list.children.length > 0) openSelect();
            });
        }
        
        document.addEventListener("click", function(e){
            if(!modernSelect.contains(e.target)){
                closeSelect();
            }
        });
    }

    // Form validation
    var reserveForm = document.querySelector(".reserve-form");
    if(reserveForm){
        reserveForm.addEventListener("submit", function(e){
            var docidHidden = document.getElementById("docidHidden");
            if(!docidHidden.value){
                e.preventDefault();
                alert("Silakan pilih dokter terlebih dahulu");
                document.getElementById("docidInput").focus();
            }
        });
    }
    
    // Event listener untuk perubahan tanggal - IMPLEMENTASI DARI APPOINTMENT.PHP
    var dateInput = document.querySelector('input[name="date"]');
    if(dateInput) {
        dateInput.addEventListener('change', updateShiftAvailability);
        // Set tanggal default ke hari ini dan cek shift availability saat halaman load
        if(!dateInput.value) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }
        updateShiftAvailability();
    }
})();
</script>

</body>
</html>
