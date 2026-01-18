<?php
session_start();

if(isset($_SESSION["user"])){
    if(($_SESSION["user"])=="" or $_SESSION['usertype']!='p'){
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
require_once("../notification_helper.php");

$sqlmain= "select * from patient where pemail=?";
$stmt = $database->prepare($sqlmain);
$stmt->bind_param("s",$useremail);
$stmt->execute();
$result = $stmt->get_result();
$userfetch=$result->fetch_assoc();
$userid= $userfetch["pid"];
$username=$userfetch["pname"];

date_default_timezone_set('Asia/Jakarta');
$today = date('Y-m-d');

// Get service ID from URL if provided
$preselect_service = isset($_GET['service']) ? intval($_GET['service']) : 0;

// Handle POST: create schedule then appointment
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['request_submit'])){
        $docid = intval($_POST['docid']);
        $title = $database->real_escape_string(trim($_POST['title']));
        $scheduledate = $_POST['date'];
        $scheduletime = $_POST['time'];
        $nop = isset($_POST['nop']) && intval($_POST['nop'])>0 ? intval($_POST['nop']) : 1;

        // Check if a schedule already exists for this doctor at same date & time
        $stmtSched = $database->prepare("SELECT scheduleid,nop FROM schedule WHERE docid=? AND scheduledate=? AND scheduletime=? LIMIT 1");
        $stmtSched->bind_param("iss", $docid, $scheduledate, $scheduletime);
        $stmtSched->execute();
        $resSched = $stmtSched->get_result();

        if($resSched && $resSched->num_rows>0){
            // attach to existing schedule
            $schedRow = $resSched->fetch_assoc();
            $scheduleid = intval($schedRow['scheduleid']);
            $schedNop = intval($schedRow['nop']);

            // count non-rejected appointments (pending + confirmed)
            $stmtCnt = $database->prepare("SELECT IFNULL(MAX(apponum),0) AS m FROM appointment WHERE scheduleid=? AND (status!='rejected' OR status IS NULL)");
            $stmtCnt->bind_param("i", $scheduleid);
            $stmtCnt->execute();
            $cntR = $stmtCnt->get_result()->fetch_assoc();
            $maxApp = intval($cntR['m']);

            if($schedNop>0 && $maxApp >= $schedNop){
                $error = "Maaf, sesi pada waktu tersebut sudah penuh. Silakan pilih waktu lain.";
            }else{
                $apponum = $maxApp + 1;

                // ensure `status` column exists in appointment table
                $colRes = $database->query("SHOW COLUMNS FROM appointment LIKE 'status'");
                if($colRes->num_rows==0){
                    $database->query("ALTER TABLE appointment ADD COLUMN status VARCHAR(20) DEFAULT 'pending'");
                }

                // insert appointment with pending status attached to existing schedule
                // ensure req_* columns exist (not needed in this path but keep schema consistent)
                $colResReq = $database->query("SHOW COLUMNS FROM appointment LIKE 'req_docid'");
                if($colResReq->num_rows==0){
                    $database->query("ALTER TABLE appointment ADD COLUMN req_docid INT NULL, ADD COLUMN req_title VARCHAR(255) NULL, ADD COLUMN req_scheduledate DATE NULL, ADD COLUMN req_scheduletime TIME NULL");
                }

                $sql3 = "INSERT INTO appointment(pid,apponum,scheduleid,appodate,status) VALUES (?,?,?,?,?)";
                $stmt3 = $database->prepare($sql3);
                $status = 'pending';
                $stmt3->bind_param("iisss", $userid, $apponum, $scheduleid, $today, $status);
                $stmt3->execute();

                // try to fetch service title for the schedule (so we can populate template)
                $serviceTitle = 'Layanan';
                $stmtTitle = $database->prepare("SELECT title FROM schedule WHERE scheduleid=? LIMIT 1");
                $stmtTitle->bind_param("i", $scheduleid);
                $stmtTitle->execute();
                $resTitle = $stmtTitle->get_result();
                if($resTitle && $resTitle->num_rows>0) $serviceTitle = $resTitle->fetch_assoc()['title'];

                // privacy-scoped notifications: doctor, patient, admin only
                $docRes = $database->prepare("select docemail from doctor where docid=?");
                $docRes->bind_param("i", $docid);
                $docRes->execute();
                $docR = $docRes->get_result();
                $docEmail = '';
                if($docR && $docR->num_rows>0) $docEmail = $docR->fetch_assoc()['docemail'];
                $notifDoc = "Permintaan janji (#".$apponum.") dari ".$username." untuk ".$scheduledate." @ ".$scheduletime;
                if($docEmail!=""){
                    notify_recipients($database, [$docEmail], $notifDoc, "/sskinv0.4.3/doctor/appointment.php");
                }
                $notifPatient = "Reservasi telah diajukan ke dokter, silakan tunggu konfirmasi dari dokter";
                notify_recipients($database, [$useremail], $notifPatient, "/sskinv0.4.3/patient/appointment.php");
                // send templated email to patient
                $templateData = [
                    'nama_pelanggan' => $username,
                    'kode_reservasi' => $apponum,
                    'nama_layanan'   => $serviceTitle,
                    'tanggal_reservasi' => $scheduledate,
                    'waktu_reservasi'   => $scheduletime
                ];
                if(function_exists('send_templated_emails_to_list')){
                    send_templated_emails_to_list([$useremail], 'pending', $templateData);
                } else if(function_exists('send_emails_to_list')){
                    // fallback
                    $emailSubject = "Reservasi Dikirim";
                    $emailBody = "Halo $username,\n\nReservasi Anda (#$apponum) telah diajukan ke dokter. Silakan tunggu konfirmasi dari dokter.\n\nTerima kasih.";
                    send_emails_to_list([$useremail], $emailSubject, $emailBody);
                }
                $notifAdmin = "Permintaan janji baru (#".$apponum.") oleh ".$username." untuk dokter ID ".$docid.".";
                notify_recipients($database, get_admin_emails($database), $notifAdmin, "/sskinv0.4.3/admin/appointment.php");

                header("Location: appointment.php?action=requested&id=".$apponum);
                exit;
            }

        }else{
            // No existing schedule: create appointment request (do NOT create schedule yet)
            // ensure req_* columns exist
            $colResReq = $database->query("SHOW COLUMNS FROM appointment LIKE 'req_docid'");
            if($colResReq->num_rows==0){
                $database->query("ALTER TABLE appointment ADD COLUMN req_docid INT NULL, ADD COLUMN req_title VARCHAR(255) NULL, ADD COLUMN req_scheduledate DATE NULL, ADD COLUMN req_scheduletime TIME NULL");
            }
            // ensure `status` column exists
            $colRes = $database->query("SHOW COLUMNS FROM appointment LIKE 'status'");
            if($colRes->num_rows==0){
                $database->query("ALTER TABLE appointment ADD COLUMN status VARCHAR(20) DEFAULT 'pending'");
            }

            // compute apponum as count of all appointments (including requests) for this doctor on that date +1
            $stmtCntAll = $database->prepare("SELECT IFNULL(MAX(apponum),0) AS m FROM appointment WHERE (req_docid=? OR scheduleid IN (SELECT scheduleid FROM schedule WHERE docid=?)) AND (appodate=? OR req_scheduledate=? )");
            $stmtCntAll->bind_param("iiss", $docid, $docid, $today, $scheduledate);
            $stmtCntAll->execute();
            $cr = $stmtCntAll->get_result()->fetch_assoc();
            $apponum = intval($cr['m']) + 1;

            $sql3 = "INSERT INTO appointment(pid,apponum,scheduleid,appodate,status,req_docid,req_title,req_scheduledate,req_scheduletime) VALUES (?,?,?,?,?,?,?,?,?)";
            $stmt3 = $database->prepare($sql3);
            $nullSchedule = NULL;
            $status = 'pending';
            $stmt3->bind_param("iisssisss", $userid, $apponum, $nullSchedule, $today, $status, $docid, $title, $scheduledate, $scheduletime);
            $stmt3->execute();

            // privacy-scoped notifications: doctor, patient, admin only
            $docRes = $database->prepare("select docemail from doctor where docid=?");
            $docRes->bind_param("i", $docid);
            $docRes->execute();
            $docR = $docRes->get_result();
            $docEmail = '';
            if($docR && $docR->num_rows>0) $docEmail = $docR->fetch_assoc()['docemail'];
            $notifDoc = "Permintaan janji (#".$apponum.") dari ".$username." untuk ".$scheduledate." @ ".$scheduletime;
            if($docEmail!=""){
                notify_recipients($database, [$docEmail], $notifDoc, "/sskinv0.4.3/doctor/appointment.php");
            }
            $notifPatient = "Reservasi telah diajukan ke dokter, silakan tunggu konfirmasi dari dokter";
            notify_recipients($database, [$useremail], $notifPatient, "/sskinv0.4.3/patient/appointment.php");
            // send templated email to patient
            $templateData = [
                'nama_pelanggan' => $username,
                'kode_reservasi' => $apponum,
                'nama_layanan'   => $title,
                'tanggal_reservasi' => $scheduledate,
                'waktu_reservasi'   => $scheduletime
            ];
            if(function_exists('send_templated_emails_to_list')){
                send_templated_emails_to_list([$useremail], 'pending', $templateData);
            } else if(function_exists('send_emails_to_list')){
                // fallback
                $emailSubject = "Reservasi Dikirim";
                $emailBody = "Halo $username,\n\nReservasi Anda (#$apponum) telah diajukan ke dokter. Silakan tunggu konfirmasi dari dokter.\n\nTerima kasih.";
                send_emails_to_list([$useremail], $emailSubject, $emailBody);
            }
            $notifAdmin = "Permintaan janji baru (#".$apponum.") oleh ".$username." untuk dokter ID ".$docid.".";
            notify_recipients($database, get_admin_emails($database), $notifAdmin, "/sskinv0.4.3/admin/appointment.php");

            header("Location: appointment.php?action=requested&id=".$apponum);
            exit;
        }
    }
}
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
    <script src="../scripts/prevent-double-submit.js"></script>
    <title>Ajukan Janji Temu</title>
    <style>
        .shift-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .shift-buttons input[type="radio"] {
            display: none;
        }
        .shift-label {
            padding: 8px 12px;
            border: 1px solid #dcdcdc;
            border-radius: 4px;
            background-color: #f8f9fa;
            cursor: pointer;
            text-align: center;
            font-size: 14px;
        }
        .shift-buttons input[type="radio"]:checked + .shift-label {
            background-color: #0062cc;
            color: white;
            border-color: #005cbf;
        }
        .shift-buttons input[type="radio"]:disabled + .shift-label {
            background: #e0e0e0;
            color: #999;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .searchable-input {
            margin-bottom: 8px;
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
                        <a href="index.php" class="non-style-link-menu" style="display:block;width:100%;height:100%"><div><p class="menu-text">Beranda</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-dashboard">
                        <a href="services.php" class="non-style-link-menu" style="display:block;width:100%;height:100%"><div><p class="menu-text">Harga Layanan</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-doctor">
                        <a href="doctors.php" class="non-style-link-menu" style="display:block;width:100%;height:100%"><div><p class="menu-text">Semua Dokter</p></div></a>
                    </td>
                </tr>
                
                <tr class="menu-row" >
                    <td class="menu-btn menu-icon-appoinment menu-active menu-icon-appoinment-active">
                        <a href="appointment.php" class="non-style-link-menu non-style-link-menu-active" style="display:block;width:100%;height:100%"><div><p class="menu-text">Janji Temu Saya</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-history">
                        <a href="history.php" class="non-style-link-menu" style="display:block;width:100%;height:100%"><div><p class="menu-text">Riwayat</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row" >
                    <td class="menu-btn menu-icon-settings">
                        <a href="settings.php" class="non-style-link-menu" style="display:block;width:100%;height:100%"><div><p class="menu-text">Pengaturan</p></div></a>
                    </td>
                </tr>
                
            </table>
        </div>
        <div class="dash-body">
            <table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;margin-top:25px; ">
                <tr>
                    <td width="13%">
                        <a href="appointment.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">Kembali</button></a>
                    </td>
                    <td>
                        <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Request an Appointment</p>
                    </td>
                </tr>
                <tr>
                    <td colspan="4">
                        <center>
                        <div class="abc">
                        <table width="60%" class="sub-table scrolldown add-doc-form-container" border="0">
                            <?php if(isset($error)) echo '<tr><td colspan="2" style="color:red">'.$error.'</td></tr>'; ?>
                            <form method="post">
                            <tr>
                                <td class="label-td">Layanan</td>
                                <td>
                                    <select name="service_id" id="service_id" class="box" required>
                                        <option value="" disabled <?php echo ($preselect_service==0)?'selected':''; ?> hidden>Pilih layanan...</option>
                                        <?php
                                            $serviceList = $database->query("SELECT id, sname FROM specialties ORDER BY sname ASC");
                                            if($serviceList && $serviceList->num_rows > 0) {
                                                while($s = $serviceList->fetch_assoc()){
                                                    $selected = ($preselect_service == $s['id']) ? 'selected' : '';
                                                    echo '<option value="'.$s['id'].'" '.$selected.'>'.htmlspecialchars($s['sname']).'</option>';
                                                }
                                            }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td">Pilih Dokter</td>
                                <td>
                                    <select name="docid" id="docid" class="box searchable-select" required>
                                        <option value="" disabled selected hidden>Pilih dokter yang tersedia...</option>
                                    </select>
                                    <div id="doctor-loading" style="display:none; margin-top:8px; color:#666; font-size:13px;">
                                        Memuat dokter tersedia...
                                    </div>
                                    <div id="doctor-empty" style="display:none; margin-top:8px; color:#dc3545; font-size:13px;">
                                        Tidak ada dokter tersedia untuk layanan ini.
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td">Tanggal</td>
                                <td><input type="date" name="date" class="input-text" min="<?php echo $today; ?>" required></td>
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
                            <tr style="display:none;">
                                <td class="label-td">Judul Sesi (opsional)</td>
                                <td><input type="text" name="title" id="title_field" class="input-text" placeholder="Reason / Title" value=""></td>
                            </tr>
                            <tr>
                                <td class="label-td">Number of Patients</td>
                                <td><input type="number" name="nop" class="input-text" min="1" value="1"></td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding-top:15px;">
                                    <div style="background-color:#fff3cd;border:1px solid #ffc107;padding:12px;border-radius:5px;font-size:14px;color:#856404;margin-bottom:15px;">
                                        <b>⚠️ Ketentuan Pembatalan:</b><br>
                                        Pembatalan janji temu harus dilakukan minimal <b>1 jam sebelum</b> waktu sesi dimulai.
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <input type="reset" value="Setel Ulang" class="login-btn btn-primary-soft btn">
                                    &nbsp;&nbsp;
                                    <input type="submit" name="request_submit" value="Ajukan Janji Temu" class="login-btn btn-primary btn">
                                </td>
                            </tr>
                            </form>
                        </table>
                        </div>
                        </center>
                    </td>
                </tr>
            </table>
    </div>
</div>
<style>
    .searchable-select-wrapper{
        position:relative;
        width:100%;
    }
    .searchable-select-wrapper .searchable-input{
        width:100%;
        box-sizing:border-box;
        padding:10px;
        border:1px solid #dcdcdc;
        border-radius:8px;
        font-size:14px;
        color:#555;
        caret-color:#555;
    }
    .searchable-select-wrapper .searchable-input::placeholder{
        color:#b3b3b3;
        font-style:italic;
    }
    .searchable-select-wrapper.active .searchable-input{
        border-color:#a0c4ff;
        box-shadow:0 0 0 2px rgba(64,123,255,0.15);
    }
    .searchable-options{
        position:absolute;
        top:100%;
        left:0;
        right:0;
        max-height:180px;
        overflow-y:auto;
        background:#fff;
        border:1px solid #dcdcdc;
        border-radius:8px;
        box-shadow:0 4px 10px rgba(0,0,0,0.08);
        z-index:20;
        display:none;
    }
    .searchable-option{
        padding:8px 10px;
        cursor:pointer;
    }
    .searchable-option:hover{
        background:#f2f6ff;
    }
</style>
<script>
(function() {
    function enhanceSelect(selectEl){
        if(!selectEl || selectEl.dataset.searchAttached){return;}
        selectEl.dataset.searchAttached="1";
        selectEl.style.display="none";

        var placeholder="";
        var phOpt=selectEl.querySelector("option[disabled][hidden]");
        if(phOpt && phOpt.textContent.trim()){ placeholder=phOpt.textContent.trim(); }
        if(!placeholder){ placeholder="Pilih..."; }

        var wrapper=document.createElement("div");
        wrapper.className="searchable-select-wrapper";

        var input=document.createElement("input");
        input.type="text";
        input.className="searchable-input";
        input.placeholder=placeholder;
        input.autocomplete="off";

        var dropdown=document.createElement("div");
        dropdown.className="searchable-options";

        var opts=Array.from(selectEl.options);
        opts.forEach(function(opt){
            var optDiv=document.createElement("div");
            optDiv.className="searchable-option";
            optDiv.textContent=opt.textContent;
            optDiv.dataset.value=opt.value;
            if(opt.disabled && opt.hidden){ optDiv.style.display="none"; }
            optDiv.addEventListener("click", function(){
                selectEl.value=opt.value;
                input.value=opt.textContent;
                dropdown.style.display="none";
                selectEl.dispatchEvent(new Event("change"));
            });
            dropdown.appendChild(optDiv);
            if(opt.selected){ input.value=opt.textContent; }
        });

        input.addEventListener("focus", function(){
            dropdown.style.display="block";
            wrapper.classList.add("active");
        });
        input.addEventListener("click", function(){
            dropdown.style.display="block";
            wrapper.classList.add("active");
        });
        input.addEventListener("input", function(){
            var term=this.value.toLowerCase();
            dropdown.style.display="block";
            Array.from(dropdown.children).forEach(function(el){
                var match = el.textContent.toLowerCase().indexOf(term)!==-1;
                el.style.display = match ? "block" : "none";
            });
        });

        document.addEventListener("click", function(e){
            if(!wrapper.contains(e.target)){
                dropdown.style.display="none";
                wrapper.classList.remove("active");
            }
        });

        wrapper.addEventListener("click", function(){
            input.focus();
        });

        wrapper.appendChild(input);
        wrapper.appendChild(dropdown);
        selectEl.parentNode.insertBefore(wrapper, selectEl);
    }
    document.addEventListener("DOMContentLoaded", function(){
        document.querySelectorAll("select.searchable-select").forEach(enhanceSelect);
        
        // Fungsi untuk disable shift yang sudah lewat
        var dateInput = document.querySelector('input[name="date"]');
        var shiftInputs = document.querySelectorAll('.shift-buttons input[type="radio"]');
        
        function updateShiftAvailability(){
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
        
        // Update saat halaman load dan saat tanggal berubah
        if(dateInput){
            dateInput.addEventListener('change', updateShiftAvailability);
            // Cek shift availability saat halaman load
            updateShiftAvailability();
        }
    });
})();
</script>

<script>
// Load doctors when service is selected
document.addEventListener('DOMContentLoaded', function() {
    const serviceSelect = document.getElementById('service_id');
    const doctorSelect = document.getElementById('docid');
    const doctorLoading = document.getElementById('doctor-loading');
    const doctorEmpty = document.getElementById('doctor-empty');
    const titleField = document.getElementById('title_field');
    
    // Auto-fill title field with service name
    serviceSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const serviceName = selectedOption ? selectedOption.text : '';
        if(titleField) {
            titleField.value = serviceName;
        }
        
        // Load doctors for selected service
        loadDoctors(this.value);
    });
    
    function loadDoctors(serviceId) {
        if(!serviceId) {
            doctorSelect.innerHTML = '<option value="" disabled selected hidden>Pilih dokter yang tersedia...</option>';
            return;
        }
        
        // Show loading
        doctorLoading.style.display = 'block';
        doctorEmpty.style.display = 'none';
        doctorSelect.innerHTML = '<option value="" disabled selected hidden>Memuat...</option>';
        doctorSelect.disabled = true;
        
        // Fetch doctors with this service/specialty
        fetch('get_doctors_by_service.php?service_id=' + serviceId)
            .then(response => response.json())
            .then(data => {
                doctorLoading.style.display = 'none';
                doctorSelect.disabled = false;
                
                if(data.success && data.doctors && data.doctors.length > 0) {
                    let html = '<option value="" disabled selected hidden>Pilih dokter yang tersedia...</option>';
                    
                    // Group doctors by availability
                    const available = data.doctors.filter(d => d.available);
                    const unavailable = data.doctors.filter(d => !d.available);
                    
                    if(available.length > 0) {
                        available.forEach(doc => {
                            html += '<option value="' + doc.docid + '">' + 
                                    htmlEscape(doc.docname) + ' (' + htmlEscape(doc.docemail) + ') ✓ Tersedia</option>';
                        });
                    }
                    
                    if(unavailable.length > 0) {
                        html += '<optgroup label="Dokter Lainnya (Tidak Hadir Hari Ini)">';
                        unavailable.forEach(doc => {
                            html += '<option value="' + doc.docid + '">' + 
                                    htmlEscape(doc.docname) + ' (' + htmlEscape(doc.docemail) + ')</option>';
                        });
                        html += '</optgroup>';
                    }
                    
                    doctorSelect.innerHTML = html;
                } else {
                    doctorSelect.innerHTML = '<option value="" disabled selected>Tidak ada dokter tersedia</option>';
                    doctorEmpty.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error loading doctors:', error);
                doctorLoading.style.display = 'none';
                doctorSelect.disabled = false;
                doctorSelect.innerHTML = '<option value="" disabled selected>Error loading doctors</option>';
            });
    }
    
    function htmlEscape(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }
    
    // Trigger load if service is preselected
    if(serviceSelect.value) {
        loadDoctors(serviceSelect.value);
    }
});
</script>

</body>
</html>
