<?php
// Start session before any output
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

//import database
include("../connection.php");
$sqlmain= "select * from patient where pemail=?";
$stmt = $database->prepare($sqlmain);
$stmt->bind_param("s",$useremail);
$stmt->execute();
$userrow = $stmt->get_result();
$userfetch=$userrow->fetch_assoc();
if(!$userfetch){
    header("location: ../logout.php");
    exit;
}
$userid= $userfetch["pid"];
$username=$userfetch["pname"];
$doctorList = $database->query("SELECT docid, docname, docemail FROM doctor");
$doctorOptions = [];
if($doctorList){
    while($d = $doctorList->fetch_assoc()){
        $doctorOptions[] = $d;
    }
}
date_default_timezone_set('Asia/Jakarta');
$today = date('Y-m-d');

// include both scheduled appointments and patient-created requests (no schedule yet)
// Show pending (waiting approval) and confirmed (running) appointments
$sqlmain= "SELECT appointment.appoid, appointment.scheduleid, COALESCE(schedule.title, appointment.req_title) AS title, COALESCE(doctor.docname, '') AS docname, patient.pname, COALESCE(schedule.scheduledate, appointment.req_scheduledate) AS scheduledate, COALESCE(schedule.scheduletime, appointment.req_scheduletime) AS scheduletime, appointment.apponum, appointment.appodate, IFNULL(appointment.status,'') as status FROM appointment LEFT JOIN schedule ON schedule.scheduleid=appointment.scheduleid LEFT JOIN patient ON patient.pid=appointment.pid LEFT JOIN doctor ON doctor.docid = COALESCE(schedule.docid, appointment.req_docid) WHERE appointment.pid=$userid AND (IFNULL(appointment.status,'') IN ('','pending','confirmed')) ";

if($_POST){
    
    
    
    if(!empty($_POST["sheduledate"])){
        $sheduledate=$_POST["sheduledate"];
        // filter by either scheduled date or requested date
        $sqlmain.=" and (COALESCE(schedule.scheduledate, appointment.req_scheduledate)='$sheduledate') ";
    };



}

$sqlmain.=" order by appointment.appodate  asc";
$result= $database->query($sqlmain);
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
        
    <title>Appointments</title>
    <style>
        .popup{
            animation: transitionIn-Y-bottom 0.5s;
        }
        .sub-table{
            animation: transitionIn-Y-bottom 0.5s;
        }
        .modern-select{position: relative;}
        .modern-select__field{
            display:flex;
            align-items:center;
            border:1px solid #dce3ee;
            border-radius:12px;
            background: linear-gradient(180deg,#fbfdff,#f4f8ff);
            box-shadow:0 4px 12px rgba(0,0,0,0.05);
            transition:border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .modern-select.open .modern-select__field{
            border-color: var(--primarycolor,#1faea8);
            box-shadow: 0 0 0 3px rgba(31,174,168,0.2);
        }
        .modern-select__input{
            flex:1;
            border:0;
            background:transparent;
            padding:12px 14px;
            font-size:14px;
            color:#444;
            outline:none;
        }
        .modern-select__caret{
            width:46px;
            text-align:center;
            padding: 12px 10px;
            background: linear-gradient(180deg,rgba(31,174,168,0.15),rgba(31,174,168,0.28));
            border-left:1px solid #d0dbe8;
            border-radius:0 12px 12px 0;
            color:#234;
            font-weight:700;
            font-size:12px;
        }
        .modern-select__list{
            position:absolute;
            top:calc(100% + 6px);
            left:0;
            right:0;
            background:#fff;
            border:1px solid #e1e7f0;
            border-radius:12px;
            box-shadow:0 10px 30px rgba(0,0,0,0.12);
            max-height:260px;
            overflow-y:auto;
            display:none;
            z-index:2000;
        }
        .modern-select.open .modern-select__list{display:block;}
        .modern-select__option{
            padding:12px 14px;
            cursor:pointer;
            color:#3b4655;
            display:flex;
            flex-direction:column;
            gap:4px;
        }
        .modern-select__option:hover{background:#f1f6ff;}
        .modern-select__option small{color:#6f7b8a;font-size:12px;}
        .modern-select select{position:absolute;opacity:0;pointer-events:none;}
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
                        <a href="index.php" class="non-style-link-menu "><div><p class="menu-text">Beranda</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-dashboard">
                        <a href="services.php" class="non-style-link-menu"><div><p class="menu-text">Harga Layanan</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-doctor">
                        <a href="doctors.php" class="non-style-link-menu"><div><p class="menu-text">Semua Dokter</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row" >
                    <td class="menu-btn menu-icon-appoinment  menu-active menu-icon-appoinment-active">
                        <a href="appointment.php" class="non-style-link-menu non-style-link-menu-active"><div><p class="menu-text">Janji Temu Saya</p></div></a>
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
            <div class="dash-top-bar">
                <a href="appointment.php"><button  class="login-btn btn-primary-soft btn btn-icon-back back-btn"  style="padding-top:11px;padding-bottom:11px;"><font class="tn-in-text">Kembali</font></button></a>
                <div class="title-block">Riwayat Janji Temu Saya</div>
                <div class="date-block">
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
                </div>
                <div class="calendar-btn">
                    <button  class="btn-label"  style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
                </div>
            </div>

            <?php 
            // Tampilkan pesan error jika ada
            if(isset($_GET['error'])) {
                $error = $_GET['error'];
                if($error == 'toolate') {
                    echo '<div style="background-color:#f8d7da;color:#721c24;padding:15px;margin:20px;border:1px solid #f5c6cb;border-radius:5px;text-align:center;">
                        <b>⚠️ Pembatalan Tidak Dapat Dilakukan</b><br>
                        Pembatalan janji temu harus dilakukan minimal <b>1 jam sebelum</b> waktu sesi dimulai.
                    </div>';
                } elseif($error == 'notfound') {
                    echo '<div style="background-color:#f8d7da;color:#721c24;padding:15px;margin:20px;border:1px solid #f5c6cb;border-radius:5px;text-align:center;">
                        Janji temu tidak ditemukan.
                    </div>';
                } elseif($error == 'unauthorized') {
                    echo '<div style="background-color:#f8d7da;color:#721c24;padding:15px;margin:20px;border:1px solid #f5c6cb;border-radius:5px;text-align:center;">
                        Anda tidak memiliki akses untuk membatalkan janji temu ini.
                    </div>';
                }
            }
            ?>

            <table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;">
                <tr>
                    <td colspan="4" style="padding-top:10px;width: 100%;" >
                    
                        <p class="heading-main12" style="margin-left: 24px;font-size:18px;color:rgb(49, 49, 49)">Janji Temu Saya (<?php echo $result->num_rows; ?>)</p>
                    </td>
                    
                </tr>
                <tr>
                    <td colspan="4" style="padding-top:0px;width: 100%;" >
                        <center>
                        <table class="filter-container" border="0" >
                        <tr>
                           <td width="10%">

                           </td> 
                        <td width="5%" style="text-align: center;">
                        Date:
                        </td>
                        <td width="30%">
                        <form action="" method="post">
                            
                            <input type="date" name="sheduledate" id="date" class="input-text filter-container-items" style="margin: 0;width: 95%;">

                        </td>
                        
                    <td width="12%">
                        <input type="submit"  name="filter" value=" Saring" class=" btn-primary-soft btn button-icon btn-filter"  style="padding: 15px; margin :0;width:100%">
                        </form>
                    </td>

                    </tr>
                            </table>

                        </center>
                    </td>
                    
                </tr>
                
               
                  
                <tr>
                   <td colspan="4">
                       <center>
                        <div class="abc scroll">
                        <table width="93%" class="sub-table scrolldown" border="0" style="border:none">
                        
                        <tbody>
                        
                            <?php

                                
                                

                                if($result->num_rows==0){
                                    echo '<tr>
                                    <td colspan="7">
                                    <br><br><br><br>
                                    <center>
                                    <img src="../img/notfound.svg" width="25%">
                                    
                                    <br>
                                    <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Anda belum memiliki janji temu.</p>
                                    <p style="margin-left: 45px;font-size:14px;color:rgb(100, 100, 100)">Kunjungi menu "Harga Layanan" untuk membuat janji baru.</p>
                                    </center>
                                    <br><br><br><br>
                                    </td>
                                    </tr>';
                                }
                                else{

                                    for ( $x=0; $x<($result->num_rows);$x++){
                                        echo "<tr>";
                                        for($q=0;$q<3;$q++){
                                            $row=$result->fetch_assoc();
                                            if (!isset($row)){
                                            break;
                                            };
                                                $scheduleid=$row["scheduleid"];
                                            $title=$row["title"];
                                            $docname=$row["docname"];
                                            $scheduledate=$row["scheduledate"];
                                            $scheduletime=$row["scheduletime"];
                                            $apponum=$row["apponum"];
                                            $appodate=$row["appodate"];
                                            $appoid=$row["appoid"];
    
                                                // Allow showing appointment requests that have no linked schedule (scheduleid may be NULL)
    
                                            echo '
                                            <td style="width: 25%;">
                                                    <div  class="dashboard-items search-items"  >
                                                    
                                                        <div style="width:100%;">
                                                        <div class="h3-search">
                                                                    Tanggal Pemesanan: '.substr($appodate ?? '',0,30).'<br>
                                                                    Reference Number: OC-000-'.$appoid.'
                                                                </div>
                                                                <div class="h1-search">
                                                                    '.substr($title ?? '',0,21).'<br>
                                                                </div>
                                                                <div class="h3-search">
                                                                    Nomor Janji Temu:<div class="h1-search">0'.$apponum.'</div>
                                                                </div>
                                                                <div class="h3-search">
                                                                                        '.substr($docname ?? '',0,30).'
                                                                                    </div>
                                                                
                                                                                    <div style="margin-top:8px;margin-bottom:8px;">
                                                                                        ';
                                            
                                                                // status badge (Indonesian labels)
                                                                $st = trim($row['status']);
                                                                if($st=="pending" || $st=="waiting" || $st==""){
                                                                    echo '<div class="h4-search" style="color:#b35;font-weight:700">Status: Menunggu konfirmasi dari dokter</div>';
                                                                }elseif($st=="confirmed"){
                                                                    echo '<div class="h4-search" style="color:#ff9800;font-weight:700">Status: Berjalan</div>';
                                                                }elseif($st=="rejected"){
                                                                    echo '<div class="h4-search" style="color:red;font-weight:700">Status: Ditolak oleh dokter</div>';
                                                                }else{
                                                                    echo '<div class="h4-search" style="color:gray;font-weight:600">Status: '.htmlspecialchars($row['status']).'</div>';
                                                                }

                                                                echo '

                                                                                    <div class="h4-search">
                                                                                        Tanggal Terjadwal: '.$scheduledate.'<br>Mulai: <b>@'.substr($scheduletime ?? '',0,5).'</b> (24h)
                                                                                    </div>
                                                                <br>
                                                                <div style="font-size:12px;color:#dc3545;margin-bottom:8px;">
                                                                    *Pembatalan hanya bisa dilakukan 1 jam sebelum waktu janji temu
                                                                </div>';
                                                                
                                                                // Cek apakah masih bisa dibatalkan (minimal 1 jam sebelum sesi)
                                                                $canCancel = true;
                                                                if($scheduledate && $scheduletime) {
                                                                    $sessionDateTime = strtotime($scheduledate . ' ' . $scheduletime);
                                                                    $currentDateTime = time();
                                                                    $timeDifference = $sessionDateTime - $currentDateTime;
                                                                    
                                                                    if($timeDifference < 3600 && $timeDifference > 0) {
                                                                        $canCancel = false;
                                                                    }
                                                                }
                                                                
                                                                if($canCancel) {
                                                                    echo '<a href="?action=drop&id='.$appoid.'&title='.$title.'&doc='.$docname.'" ><button  class="login-btn btn-primary-soft btn "  style="padding-top:11px;padding-bottom:11px;width:100%"><font class="tn-in-text">Batalkan Pemesanan</font></button></a>';
                                                                } else {
                                                                    echo '<button class="login-btn btn" disabled style="padding-top:11px;padding-bottom:11px;width:100%;background-color:#ccc;cursor:not-allowed;" title="Pembatalan harus dilakukan minimal 1 jam sebelum sesi"><font class="tn-in-text">Tidak Dapat Dibatalkan</font></button>';
                                                                }
                                                                
                                                                echo '
                                                        </div>
                                                                
                                                    </div>
                                                </td>';
    
                                        }
                                        echo "</tr>";
                           
                                // for ( $x=0; $x<$result->num_rows;$x++){
                                //     $row=$result->fetch_assoc();
                                //     $appoid=$row["appoid"];
                                //     $scheduleid=$row["scheduleid"];
                                //     $title=$row["title"];
                                //     $docname=$row["docname"];
                                //     $scheduledate=$row["scheduledate"];
                                //     $scheduletime=$row["scheduletime"];
                                //     $pname=$row["pname"];
                                //     
                                //     
                                //     echo '<tr >
                                //         <td style="font-weight:600;"> &nbsp;'.
                                        
                                //         substr($pname,0,25)
                                //         .'</td >
                                //         <td style="text-align:center;font-size:23px;font-weight:500; color: var(--btnnicetext);">
                                //         '.$apponum.'
                                        
                                //         </td>
                                //         <td>
                                //         '.substr($title,0,15).'
                                //         </td>
                                //         <td style="text-align:center;;">
                                //             '.substr($scheduledate,0,10).' @'.substr($scheduletime,0,5).'
                                //         </td>
                                        
                                //         <td style="text-align:center;">
                                //             '.$appodate.'
                                //         </td>

                                //         <td>
                                //         <div style="display:flex;justify-content: center;">
                                        
                                //         <!--<a href="?action=view&id='.$appoid.'" class="non-style-link"><button  class="btn-primary-soft btn button-icon btn-view"  style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">View</font></button></a>
                                //        &nbsp;&nbsp;&nbsp;-->
                                //        <a href="?action=drop&id='.$appoid.'&name='.$pname.'&session='.$title.'&apponum='.$apponum.'" class="non-style-link"><button  class="btn-primary-soft btn button-icon btn-delete"  style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">Cancel</font></button></a>
                                //        &nbsp;&nbsp;&nbsp;</div>
                                //         </td>
                                //     </tr>';
                                    
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


        <div class="reserve-card">
            <div class="reserve-header">
                <div>
                    <div style="font-size:18px;font-weight:700;">Buat Reservasi</div>
                    <div style="font-size:12px;opacity:0.9;">Isi form singkat untuk mengirim permintaan janji</div>
                </div>
                <button class="reserve-close" type="button" id="closeReserve" aria-label="Tutup">×</button>
            </div>
            <div class="reserve-body">
                <form class="reserve-form" method="post" action="request-appointment.php">
                    <input type="hidden" name="request_submit" value="1">
                    <table width="100%" class="sub-table scrolldown" border="0">
                        <tr>
                            <td class="label-td">Pilih Dokter</td>
                            <td>
                                <div class="modern-select" id="modernDocSelect">
                                    <input type="hidden" name="docid" id="docidHidden">
                                    <div class="modern-select__field">
                                        <input type="text" class="modern-select__input" id="docidInput" placeholder="Pilih dokter" autocomplete="off">
                                        <span class="modern-select__caret" aria-hidden="true">▾</span>
                                    </div>
                                    <div class="modern-select__list">
                                        <?php
                                            foreach($doctorOptions as $d){
                                                $label = htmlspecialchars($d["docname"]);
                                                $email = htmlspecialchars($d["docemail"]);
                                                $val = intval($d["docid"]);
                                                echo '<div class="modern-select__option" data-value="'.$val.'" data-label="'.$label.'">'.$label.'<small>'.$email.'</small></div>';
                                            }
                                        ?>
                                    </div>
                                    <select aria-hidden="true">
                                        <option value="" selected hidden></option>
                                        <?php
                                            foreach($doctorOptions as $d){
                                                $label = htmlspecialchars($d["docname"]).' ('.htmlspecialchars($d["docemail"]).')';
                                                $val = intval($d["docid"]);
                                                echo '<option value="'.$val.'">'.$label.'</option>';
                                            }
                                        ?>
                                    </select>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td">Judul Sesi</td>
                            <td><input type="text" name="title" class="input-text" placeholder="Alasan / judul" value="Patient Request"></td>
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
    <?php
    
    if($_GET){
        $id=$_GET["id"] ?? null;
        $action=$_GET["action"] ?? null;
        $notice=$_GET["notice"] ?? null;
        if($notice==='schedule-disabled'){
            echo '
            <div id="popup1" class="overlay">
                <div class="popup">
                    <center>
                        <h2>Scheduled Sessions dinonaktifkan</h2>
                        <a class="close" href="appointment.php">&times;</a>
                        <div class="content">
                            Halaman Scheduled Sessions tidak lagi digunakan. Silakan buat permintaan janji langsung dari Janji Temu Saya.
                        </div>
                        <div style="display: flex;justify-content: center;">
                            <a href="appointment.php" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font></button></a>
                        </div>
                    </center>
                </div>
            </div>
            ';
        }elseif($action=='booking-added'){
            
            echo '
            <div id="popup1" class="overlay">
                    <div class="popup">
                    <center>
                    <br><br>
                        <h2>Pemesanan Berhasil.</h2>
                        <a class="close" href="appointment.php">&times;</a>
                        <div class="content">
                        Nomor janji temu Anda adalah '.$id.'.<br><br>
                            
                        </div>
                        <div style="display: flex;justify-content: center;">
                        
                        <a href="appointment.php" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font></button></a>
                        <br><br><br><br>
                        </div>
                    </center>
            </div>
            </div>
            ';
          }elseif($action=='drop'){
            $title=$_GET["title"];
            $docname=$_GET["doc"];
            
            echo '
            <div id="popup1" class="overlay">
                    <div class="popup">
                    <center>
                        <h2>Apakah Anda yakin?</h2>
                        <a class="close" href="appointment.php">&times;</a>
                        <div class="content">
                            Apakah Anda yakin ingin Membatalkan Janji Temu ini?<br><br>
                            Nama Sesi: &nbsp;<b>'.substr($title ?? '',0,40).'</b><br>
                            Nama Dokter&nbsp; : <b>'.substr($docname ?? '',0,40).'</b><br><br>
                            
                        </div>
                        <div style="display: flex;justify-content: center;">
                        <form method="post" action="delete-appointment.php?id='.$id.'" style="display: flex; flex-direction: column; align-items: center;">
                            <label for="cancel_reason"><b>Alasan pembatalan:</b></label>
                            <textarea name="cancel_reason" id="cancel_reason" rows="3" cols="40" required placeholder="Tulis alasan pembatalan..."></textarea>
                            <div style="display: flex;justify-content: center; margin-top: 10px;">
                                <button type="submit" class="btn-primary btn" style="margin:10px;padding:10px;">&nbsp;Ya, Batalkan&nbsp;</button>&nbsp;&nbsp;&nbsp;
                                <a href="appointment.php" class="non-style-link"><button type="button" class="btn-primary btn" style="margin:10px;padding:10px;">&nbsp;&nbsp;Tidak&nbsp;&nbsp;</button></a>
                            </div>
                        </form>
                        </div>
                    </center>
            </div>
            </div>
            '; 
            }elseif($action=='requested'){
                echo '
                <div id="popup1" class="overlay">
                        <div class="popup">
                        <center>
                        <br><br>
                            <h2>Permintaan Terkirim.</h2>
                            <a class="close" href="appointment.php">&times;</a>
                            <div class="content">
                            Nomor permintaan janji temu Anda adalah '.$id.' dan saat ini menunggu konfirmasi.<br><br>
                            </div>
                            <div style="display: flex;justify-content: center;">
                            <a href="appointment.php" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font></button></a>
                            <br><br><br><br>
                            </div>
                        </center>
                </div>
                </div>
                ';
        }elseif($action=='view'){
            $sqlmain= "select * from doctor where docid=?";
            $stmt = $database->prepare($sqlmain);
            $stmt->bind_param("i",$id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row=$result->fetch_assoc();
            $name=$row["docname"];
            $email=$row["docemail"];
            $spe=$row["specialties"];
            
            $sqlmain= "select sname from specialties where id=?";
            $stmt = $database->prepare($sqlmain);
            $stmt->bind_param("s",$spe);
            $stmt->execute();
            $spcil_res = $stmt->get_result();
            $spcil_array= $spcil_res->fetch_assoc();
            $spcil_name=$spcil_array["sname"];
            $nic=$row['docnic'];
            $tele=$row['doctel'];
            echo '
            <div id="popup1" class="overlay">
                    <div class="popup">
                    <center>
                        <h2></h2>
                        <a class="close" href="doctors.php">&times;</a>
                        <div class="content">
                            eDoc Web App<br>
                            
                        </div>
                        <div style="display: flex;justify-content: center;">
                        <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                        
                            <tr>
                                <td>
                                    <p style="padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 500;">Lihat Detail.</p><br><br>
                                </td>
                            </tr>
                            
                            <tr>
                                
                                <td class="label-td" colspan="2">
                                    <label for="name" class="form-label">Name: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    '.$name.'<br><br>
                                </td>
                                
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="Email" class="form-label">Email: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                '.$email.'<br><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="nic" class="form-label">NIK: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                '.$nic.'<br><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="Tele" class="form-label">Telepon: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                '.$tele.'<br><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="spec" class="form-label">Layanan: </label>
                                    
                                </td>
                            </tr>
                            <tr>
                            <td class="label-td" colspan="2">
                            '.$spcil_name.'<br><br>
                            </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <a href="doctors.php"><input type="button" value="OK" class="login-btn btn-primary-soft btn" ></a>
                                
                                    
                                </td>
                
                            </tr>
                           

                        </table>
                        </div>
                    </center>
                    <br><br>
            </div>
            </div>
            ';  
    }
}

    ?>
    </div>
    <script>
        // JavaScript yang tidak diperlukan telah dihapus karena form reservasi sudah tersedia di menu Harga Layanan
    </script>

</body>
</html>
