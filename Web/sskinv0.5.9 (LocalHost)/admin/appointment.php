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
        
    <title>Appointments</title>
    <style>
        .popup{
            animation: transitionIn-Y-bottom 0.5s;
        }
        .sub-table{
            animation: transitionIn-Y-bottom 0.5s;
        }
</style>
</head>
<body>
    <?php

    //learn from w3schools.com

    session_start();

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='a'){
            header("location: ../login.php");
        }

    }else{
        header("location: ../login.php");
    }
    
    

    //import database
    include("../connection.php");

    
    ?>
    <div class="container">
        <div class="menu">
            <table class="menu-container" border="0">
                <tr>
                    <td style="padding:6px" colspan="2">
                        <table border="0" class="profile-container">
                            <tr>
                                <td width="30%" style="padding-left:20px" >
                                    <img src="../img/user.png" alt="" width="100%" style="border-radius:50%">
                                </td>
                                <td style="padding:0px;margin:0px;">
                                    <p class="profile-title">Administrator</p>
                                    <p class="profile-subtitle">admin@edoc.com</p>
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
                    <td class="menu-btn menu-icon-dashbord" >
                        <a href="index.php" class="non-style-link-menu"><div><p class="menu-text">Dasbor</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-doctor ">
                        <a href="doctors.php" class="non-style-link-menu "><div><p class="menu-text">Dokter</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-session">
                        <a href="schedule.php" class="non-style-link-menu"><div><p class="menu-text">Jadwal Sesi</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-session">
                        <a href="attendance.php" class="non-style-link-menu"><div><p class="menu-text">Kehadiran</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-appoinment menu-active menu-icon-appoinment-active">
                        <a href="appointment.php" class="non-style-link-menu non-style-link-menu-active"><div><p class="menu-text">Janji Temu</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row" >
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
            <table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;margin-top:25px; ">
                <tr >
                    <td width="13%">
                    <a href="appointment.php" ><button  class="login-btn btn-primary-soft btn btn-icon-back"  style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Kembali</font></button></a>
                    </td>
                    <td>
                        <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Manajemen Janji Temu</p>
                                           
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

                        $list110 = $database->query("select  * from  appointment WHERE (IFNULL(status,'') = '' OR IFNULL(status,'') = 'pending')");

                        ?>
                        </p>
                    </td>
                    <td width="10%">
                        <button  class="btn-label"  style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
                    </td>


                </tr>
               
                <!-- <tr>
                    <td colspan="4" >
                        <div style="display: flex;margin-top: 40px;">
                        <div class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49);margin-top: 5px;">Jadwalkan Sesi</div>
                        <a href="?action=add-session&id=none&error=0" class="non-style-link"><button  class="login-btn btn-primary btn button-icon"  style="margin-left:25px;background-image: url('../img/icons/add.svg');">Tambahkan Sesi</font></button>
                        </a>
                        </div>
                    </td>
                </tr> -->
                <tr>
                    <td colspan="4" style="padding-top:10px;width: 100%;" >
                    
                        <p class="heading-main12" style="margin-left: 45px;font-size:18px;color:rgb(49, 49, 49)">Semua Janji Temu (<?php echo $list110->num_rows; ?>)</p>
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
                        <td width="5%" style="text-align: center;">
                        Doctor:
                        </td>
                        <td width="30%">
                        <select name="docid" id="" class="box filter-container-items searchable-select" style="width:90% ;height: 37px;margin: 0;" >
                            <option value="" disabled selected hidden></option><br/>
                                
                            <?php 
                             
                                $list11 = $database->query("select  * from  doctor order by docname asc;");

                                for ($y=0;$y<$list11->num_rows;$y++){
                                    $row00=$list11->fetch_assoc();
                                    $sn=$row00["docname"];
                                    $id00=$row00["docid"];
                                    echo "<option value=".$id00.">$sn</option><br/>";
                                };


                                ?>

                        </select>
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
                
                <?php
                    if($_POST){
                        //print_r($_POST);
                        $sqlpt1="";
                        if(!empty($_POST["sheduledate"])){
                            $sheduledate=$_POST["sheduledate"];
                            $sqlpt1=" COALESCE(schedule.scheduledate, appointment.req_scheduledate)='$sheduledate' ";
                        }


                        $sqlpt2="";
                        if(!empty($_POST["docid"])){
                            $docid=$_POST["docid"];
                            $sqlpt2=" COALESCE(schedule.docid, appointment.req_docid)=$docid ";
                        }
                        //echo $sqlpt2;
                        //echo $sqlpt1;
                        $sqlmain= "select appointment.appoid,appointment.scheduleid,COALESCE(schedule.title, appointment.req_title) AS title,COALESCE(doctor.docname,'') AS docname,patient.pname,COALESCE(schedule.scheduledate, appointment.req_scheduledate) AS scheduledate,COALESCE(schedule.scheduletime, appointment.req_scheduletime) AS scheduletime,appointment.apponum,appointment.appodate,IFNULL(appointment.status,'') as status from appointment LEFT JOIN schedule on appointment.scheduleid=schedule.scheduleid LEFT JOIN patient on patient.pid=appointment.pid LEFT JOIN doctor on doctor.docid=COALESCE(schedule.docid, appointment.req_docid)";
                        $sqllist=array($sqlpt1,$sqlpt2);
                        $sqlkeywords=array(" where "," and ");
                        $key2=0;
                        foreach($sqllist as $key){

                            if(!empty($key)){
                                $sqlmain.=$sqlkeywords[$key2].$key;
                                $key2++;
                            };
                        };
                        // Add filter to show only pending appointments
                        if($key2==0){
                            $sqlmain.=" WHERE (IFNULL(appointment.status,'') = '' OR IFNULL(appointment.status,'') = 'pending') ";
                        } else {
                            $sqlmain.=" AND (IFNULL(appointment.status,'') = '' OR IFNULL(appointment.status,'') = 'pending') ";
                        }
                        //echo $sqlmain;

                        
                        
                        //
                    }else{
                        $sqlmain= "select appointment.appoid,appointment.scheduleid,COALESCE(schedule.title, appointment.req_title) AS title,COALESCE(doctor.docname,'') AS docname,patient.pname,COALESCE(schedule.scheduledate, appointment.req_scheduledate) AS scheduledate,COALESCE(schedule.scheduletime, appointment.req_scheduletime) AS scheduletime,appointment.apponum,appointment.appodate,IFNULL(appointment.status,'') as status from appointment LEFT JOIN schedule on appointment.scheduleid=schedule.scheduleid LEFT JOIN patient on patient.pid=appointment.pid LEFT JOIN doctor on doctor.docid=COALESCE(schedule.docid, appointment.req_docid) WHERE (IFNULL(appointment.status,'') = '' OR IFNULL(appointment.status,'') = 'pending') order by appointment.appodate desc";

                    }



                ?>
                  
                <tr>
                   <td colspan="4">
                       <center>
                        <div class="abc scroll">
                        <table width="93%" class="sub-table scrolldown" border="0">
                        <thead>
                        <tr>
                                <th class="table-headin">
                                    Nama Pasien
                                </th>
                                <th class="table-headin">
                                    
                                    Nomor Janji Temu
                                    
                                </th>
                               
                                
                                <th class="table-headin">
                                    Dokter
                                </th>
                                <th class="table-headin">
                                    
                                
                                    Judul Sesi
                                    
                                    </th>
                                
                                <th class="table-headin" style="font-size:10px">
                                    
                                    Tanggal & Waktu Sesi
                                    
                                </th>
                                
                                <th class="table-headin">
                                    
                                    Tanggal Janji Temu
                                    
                                </th>
                                
                                <th class="table-headin">
                                    
                                    Aksi
                                    
                                </tr>
                        </thead>
                        <tbody>
                        
                            <?php

                                
                                $result= $database->query($sqlmain);

                                if($result->num_rows==0){
                                    echo '<tr>
                                    <td colspan="7">
                                    <br><br><br><br>
                                    <center>
                                    <img src="../img/notfound.svg" width="25%">
                                    <br>
                                    <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Belum ada appointment yang terdaftar atau data tidak ditemukan!</p>
                                    </center>
                                    <br><br><br><br>
                                    </td>
                                    </tr>';
                                } else {
                                    for ( $x=0; $x<$result->num_rows;$x++){
                                        $row=$result->fetch_assoc();
                                        $appoid=$row["appoid"];
                                        $scheduleid=$row["scheduleid"];
                                        $title=$row["title"];
                                        $docname=$row["docname"];
                                        $scheduledate=$row["scheduledate"];
                                        $scheduletime=$row["scheduletime"];
                                        $pname=$row["pname"];
                                        $apponum=$row["apponum"];
                                        $appodate=$row["appodate"];
                                        $status=$row["status"];
                                        echo '<tr >
                                            <td style="font-weight:600;"> &nbsp;'.
                                            substr($pname ?? '',0,25)
                                            .'</td >
                                            <td style="text-align:center;font-size:23px;font-weight:500; color: var(--btnnicetext);">
                                            '.$apponum.'
                                            </td>
                                            <td>
                                            '.substr($docname ?? '',0,25).'
                                            </td>
                                            <td>
                                            '.substr($title ?? '',0,15).'
                                            </td>
                                            <td style="text-align:center;font-size:12px;">
                                                '.substr($scheduledate ?? '',0,10).' <br>'.substr($scheduletime ?? '',0,5).'
                                            </td>
                                            <td style="text-align:center;">
                                                '.$appodate.'
                                            </td>
                                            <td>
                                            <div style="display:flex;justify-content: center;flex-wrap:wrap;gap:5px;">
                                                <a href="?action=view&id='.$appoid.'" class="non-style-link"><button  class="btn-primary-soft btn button-icon btn-view"  style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">Lihat</font></button></a>
                                                <a href="confirm-appointment.php?action=confirm&id='.$appoid.'" class="non-style-link"><button class="login-btn btn-primary btn" style="padding:10px 20px;margin-top: 10px;">Konfirmasi</button></a>
                                                <a href="confirm-appointment.php?action=reject&id='.$appoid.'" class="non-style-link"><button class="login-btn btn" style="padding:10px 20px;margin-top: 10px;background:#d32f2f;">Tolak</button></a>
                                            </div>
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
    <?php
    
    if($_GET){
        $id=$_GET["id"];
        $action=$_GET["action"];
        if($action=='add-session'){

            echo '
            <div id="popup1" class="overlay">
                    <div class="popup">
                    <center>
                    
                    
                        <a class="close" href="schedule.php">&times;</a> 
                        <div style="display: flex;justify-content: center;">
                        <div class="abc">
                        <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                        <tr>
                                <td class="label-td" colspan="2">'.
                                   ""
                                
                                .'</td>
                            </tr>

                            <tr>
                                <td>
                                    <p style="padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 500;">Tambah Sesi Baru.</p><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                <form action="add-session.php" method="POST" class="add-new-form">
                                    <label for="title" class="form-label">Judul Sesi : </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <input type="text" name="title" class="input-text" placeholder="Nama Sesi ini" required><br>
                                </td>
                            </tr>
                            <tr>
                                
                                <td class="label-td" colspan="2">
                                    <label for="docid" class="form-label">Pilih Dokter: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <select name="docid" id="" class="box searchable-select" >
                                    <option value="" disabled selected hidden></option><br/>';
                                        
        
                                        $list11 = $database->query("select  * from  doctor;");
        
                                        for ($y=0;$y<$list11->num_rows;$y++){
                                            $row00=$list11->fetch_assoc();
                                            $sn=$row00["docname"];
                                            $id00=$row00["docid"];
                                            echo "<option value=".$id00.">$sn</option><br/>";
                                        };
        
        
        
                                        
                        echo     '       </select><br><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="nop" class="form-label">Jumlah Pasien/Nomor Janji Temu : </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <input type="number" name="nop" class="input-text" min="0"  placeholder="Nomor janji temu akhir untuk sesi ini tergantung pada nomor ini" required><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="date" class="form-label">Tanggal Sesi: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <input type="date" name="date" class="input-text" min="'.date('Y-m-d').'" required><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="time" class="form-label">Waktu Jadwal: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <input type="time" name="time" class="input-text" placeholder="Waktu" required><br>
                                </td>
                            </tr>
                           
                            <tr>
                                <td colspan="2">
                                    <input type="reset" value="Setel Ulang" class="login-btn btn-primary-soft btn" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                
                                    <input type="submit" value="Tempatkan Sesi ini" class="login-btn btn-primary btn" name="shedulesubmit">
                                </td>
                
                            </tr>
                           
                            </form>
                            </tr>
                        </table>
                        </div>
                        </div>
                    </center>
                    <br><br>
            </div>
            </div>
            ';
        }elseif($action=='session-added'){
            $titleget=$_GET["title"];
            echo '
            <div id="popup1" class="overlay">
                    <div class="popup">
                    <center>
                    <br><br>
                        <h2>Session Placed.</h2>
                        <a class="close" href="schedule.php">&times;</a>
                        <div class="content">
                        '.substr($titleget ?? '',0,40).' was scheduled.<br><br>
                            
                        </div>
                        <div style="display: flex;justify-content: center;">
                        
                        <a href="schedule.php" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font></button></a>
                        <br><br><br><br>
                        </div>
                    </center>
            </div>
            </div>
            ';
        }elseif($action=='appointment-confirmed'){
            $apponum=$_GET["apponum"] ?? '';
            echo '
            <div id="popup1" class="overlay">
                    <div class="popup">
                    <center>
                    <br><br>
                        <h2>✓ Janji Temu Dikonfirmasi</h2>
                        <a class="close" href="appointment.php">&times;</a>
                        <div class="content">
                            Janji temu #'.htmlspecialchars($apponum).' telah berhasil dikonfirmasi.<br>
                            Pasien akan menerima notifikasi dan email konfirmasi.<br><br>
                        </div>
                        <div style="display: flex;justify-content: center;">
                            <a href="appointment.php" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font></button></a>
                        </div>
                    </center>
            </div>
            </div>
            ';
        }elseif($action=='appointment-rejected'){
            echo '
            <div id="popup1" class="overlay">
                    <div class="popup">
                    <center>
                    <br><br>
                        <h2>Janji Temu Ditolak</h2>
                        <a class="close" href="appointment.php">&times;</a>
                        <div class="content">
                            Janji temu telah ditolak.<br>
                            Pasien akan menerima notifikasi dan email.<br><br>
                        </div>
                        <div style="display: flex;justify-content: center;">
                            <a href="appointment.php" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font></button></a>
                        </div>
                    </center>
            </div>
            </div>
            ';
        }elseif($action=='appointment-rejected-full'){
            echo '
            <div id="popup1" class="overlay">
                    <div class="popup">
                    <center>
                    <br><br>
                        <h2>Janji Temu Ditolak</h2>
                        <a class="close" href="appointment.php">&times;</a>
                        <div class="content">
                            Janji temu ditolak karena sesi sudah penuh.<br>
                            Pasien akan menerima notifikasi dan email.<br><br>
                        </div>
                        <div style="display: flex;justify-content: center;">
                            <a href="appointment.php" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font></button></a>
                        </div>
                    </center>
            </div>
            </div>
            ';
        }elseif($action=='view'){
            // First try to show appointment details (if id is an appointment id)
            $appoid = $id;
            $appt_res = $database->query("SELECT appointment.appoid, appointment.apponum, appointment.appodate, appointment.req_title, patient.pname, patient.pemail, patient.ptel, COALESCE(schedule.title, appointment.req_title) AS title, COALESCE(schedule.scheduledate, appointment.appodate) AS scheduledate, COALESCE(schedule.scheduletime, '') AS scheduletime, COALESCE(doctor.docname, req_doctor.docname) AS docname, COALESCE(doctor.docemail, req_doctor.docemail) AS docemail FROM appointment LEFT JOIN patient ON appointment.pid=patient.pid LEFT JOIN schedule ON appointment.scheduleid=schedule.scheduleid LEFT JOIN doctor ON schedule.docid=doctor.docid LEFT JOIN doctor AS req_doctor ON appointment.req_docid=req_doctor.docid WHERE appointment.appoid='$appoid'");
            if($appt_res && $appt_res->num_rows>0){
                $a = $appt_res->fetch_assoc();
                $pname = $a['pname']??'Unknown';
                $pemail = $a['pemail']??'';
                $ptel = $a['ptel']??'';
                $apponum = $a['apponum']??'';
                $appodate = $a['appodate']??'';
                $title = $a['title']??'';
                $docname = $a['docname']??'Unknown';
                $scheddate = $a['scheduledate']??'';
                $schedtime = $a['scheduletime']??'';
                echo '
                <div id="popup1" class="overlay">
                        <div class="popup">
                        <center>
                            <h2>Detail Janji Temu</h2>
                            <a class="close" href="appointment.php">&times;</a>
                            <div style="display: flex;justify-content: center;">
                            <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                                <tr><td class="label-td" colspan="2"><label class="form-label">Nama Pasien: </label></td></tr>
                                <tr><td class="label-td" colspan="2">'.htmlspecialchars($pname).'<br><br></td></tr>
                                <tr><td class="label-td" colspan="2"><label class="form-label">Email Pasien: </label></td></tr>
                                <tr><td class="label-td" colspan="2">'.htmlspecialchars($pemail).'<br><br></td></tr>
                                <tr><td class="label-td" colspan="2"><label class="form-label">Telepon: </label></td></tr>
                                <tr><td class="label-td" colspan="2">'.htmlspecialchars($ptel).'<br><br></td></tr>
                                <tr><td class="label-td" colspan="2"><label class="form-label">Nomor Janji Temu: </label></td></tr>
                                <tr><td class="label-td" colspan="2">'.htmlspecialchars($apponum).'<br><br></td></tr>
                                <tr><td class="label-td" colspan="2"><label class="form-label">Tanggal Janji Temu: </label></td></tr>
                                <tr><td class="label-td" colspan="2">'.htmlspecialchars($appodate).'<br><br></td></tr>
                                <tr><td class="label-td" colspan="2"><label class="form-label">Judul Sesi: </label></td></tr>
                                <tr><td class="label-td" colspan="2">'.htmlspecialchars($title).'<br><br></td></tr>
                                <tr><td class="label-td" colspan="2"><label class="form-label">Dokter: </label></td></tr>
                                <tr><td class="label-td" colspan="2">'.htmlspecialchars($docname).' '.( $a['docemail']? '<br>'.htmlspecialchars($a['docemail']):'' ).'<br><br></td></tr>
                                <tr><td colspan="2"><a href="appointment.php"><input type="button" value="OK" class="login-btn btn-primary-soft btn" ></a></td></tr>
                            </table>
                            </div>
                        </center>
                        <br><br>
                </div>
                </div>
                ';
            } else {
                // fallback to previous doctor view if appointment not found
                $sqlmain= "select * from doctor where docid='$id'";
                $result= $database->query($sqlmain);
                $row=$result->fetch_assoc();
                $name=$row["docname"];
                $email=$row["docemail"];
                $spe=$row["specialties"];
                
                $spcil_res= $database->query("select sname from specialties where id='$spe'");
                $spcil_array= $spcil_res->fetch_assoc();
                $spcil_name=$spcil_array["sname"];
                $nic=$row['docnic'];
                $tele=$row['doctel'];
                echo '
                <div id="popup1" class="overlay">
                        <div class="popup">
                        <center>
                            <h2>Detail Dokter</h2>
                            <a class="close" href="doctors.php">&times;</a>
                            <div style="display: flex;justify-content: center;">
                            <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                                
                                <tr>
                                    
                                    <td class="label-td" colspan="2">
                                        <label for="name" class="form-label">Nama: </label>
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
                                        <label for="nic" class="form-label">NIC: </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                    '.$nic.'<br><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label for="Tele" class="form-label">Telephone: </label>
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
}

    ?>
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
    });
})();
</script>
</body>
</html>
