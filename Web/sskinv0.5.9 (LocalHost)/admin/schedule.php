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
        
    <title>Schedule</title>
    <style>
        .popup{
            animation: transitionIn-Y-bottom 0.5s;
        }
        .sub-table{
            animation: transitionIn-Y-bottom 0.5s;
        }
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
                    <td class="menu-btn menu-icon-session menu-active menu-icon-session-active">
                        <a href="schedule.php" class="non-style-link-menu non-style-link-menu-active"><div><p class="menu-text">Jadwal Sesi</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-session">
                        <a href="attendance.php" class="non-style-link-menu"><div><p class="menu-text">Kehadiran</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-appoinment">
                        <a href="appointment.php" class="non-style-link-menu"><div><p class="menu-text">Janji Temu</p></div></a>
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
                    <td width="13%" >
                    <a href="schedule.php" ><button  class="login-btn btn-primary-soft btn btn-icon-back"  style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Kembali</font></button></a>
                    </td>
                    <td>
                        <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Manajemen Jadwal</p>
                                           
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

                        $list110 = $database->query("select  * from  schedule WHERE schedule.scheduleid IN (SELECT DISTINCT scheduleid FROM appointment WHERE IFNULL(status,'')='confirmed' AND scheduleid IS NOT NULL)");

                        ?>
                        </p>
                    </td>
                    <td width="10%">
                        <button  class="btn-label"  style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
                    </td>


                </tr>
               
                <tr>
                    <td colspan="4" >
                        <div style="display: flex;margin-top: 40px;">
                        <div class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49);margin-top: 5px;">Jadwalkan Sesi</div>
                        <a href="?action=add-session&id=none&error=0" class="non-style-link">
                            <button class="login-btn btn-primary btn button-icon" style="display: flex;justify-content: center;align-items: center; gap:8px; border-radius:24px; padding:8px 32px; background: linear-gradient(135deg, #0ea5e9 0%, #22d3ee 50%, #0ea5e9 100%); box-shadow: none; border: none; overflow: hidden; margin-left:25px;">
                                <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" style="display:inline-block;vertical-align:middle;background:none;"><line x1="10" y1="4" x2="10" y2="16"/><line x1="4" y1="10" x2="16" y2="10"/></svg>
                                <span style="background:none;">Tambahkan Sesi</span>
                            </button>
                        </a>
                        </a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="4" style="padding-top:10px;width: 100%;" >
                    
                        <p class="heading-main12" style="margin-left: 45px;font-size:18px;color:rgb(49, 49, 49)">Semua Sesi (<?php echo $list110->num_rows; ?>)</p>
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
                        <select name="docid" id="" class="box filter-container-items" style="width:90% ;height: 37px;margin: 0;" >
                            <option value="" disabled selected hidden>Pilih Nama Dokter dari daftar</option><br/>
                                
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
                            $sqlpt1=" schedule.scheduledate='$sheduledate' ";
                        }


                        $sqlpt2="";
                        if(!empty($_POST["docid"])){
                            $docid=$_POST["docid"];
                            $sqlpt2=" doctor.docid=$docid ";
                        }
                        //echo $sqlpt2;
                        //echo $sqlpt1;
                        $sqlmain= "select schedule.scheduleid,schedule.title,doctor.docname,schedule.scheduledate,schedule.scheduletime,schedule.nop from schedule inner join doctor on schedule.docid=doctor.docid ";
                        $sqllist=array($sqlpt1,$sqlpt2);
                        $sqlkeywords=array(" where "," and ");
                        $key2=0;
                        $hasFilter=false;
                        foreach($sqllist as $key){

                            if(!empty($key)){
                                $sqlmain.=$sqlkeywords[$key2].$key;
                                $key2++;
                                $hasFilter=true;
                            };
                        };
                        // Only show sessions with confirmed appointments (exclude completed in history)
                        $statusFilter = " schedule.scheduleid IN (SELECT DISTINCT scheduleid FROM appointment WHERE IFNULL(status,'')='confirmed' AND scheduleid IS NOT NULL) ";
                        if($hasFilter){
                            $sqlmain.=" AND ".$statusFilter;
                        } else {
                            $sqlmain.=" WHERE ".$statusFilter;
                        }
                        //echo $sqlmain;

                        
                        
                        //
                    }else{
                        $sqlmain= "select schedule.scheduleid,schedule.title,doctor.docname,schedule.scheduledate,schedule.scheduletime,schedule.nop from schedule inner join doctor on schedule.docid=doctor.docid WHERE schedule.scheduleid IN (SELECT DISTINCT scheduleid FROM appointment WHERE IFNULL(status,'')='confirmed' AND scheduleid IS NOT NULL) order by schedule.scheduledate desc";

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
                                    
                                
                                Judul Sesi
                                
                                </th>
                                
                                <th class="table-headin">
                                    Dokter
                                </th>
                                <th class="table-headin">
                                    
                                    Tanggal & Waktu Terjadwal
                                    
                                </th>
                                <th class="table-headin">
                                    
                                Jumlah Maks yang Dapat Dipesan
                                    
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
                                    <td colspan="4">
                                    <br><br><br><br>
                                    <center>
                                    <img src="../img/notfound.svg" width="25%">
                                    
                                    <br>
                                    <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">We  couldnt find anything related to your keywords !</p>
                                    <a class="non-style-link" href="schedule.php"><button class="btn-primary-soft btn">Tampilkan semua Sesi</button></a>
                                    </center>
                                    <br><br><br><br>
                                    </td>
                                    </tr>';
                                    
                                }
                                else{
                                for ( $x=0; $x<$result->num_rows;$x++){
                                    $row=$result->fetch_assoc();
                                    $scheduleid=$row["scheduleid"];
                                    $title=$row["title"];
                                    $docname=$row["docname"];
                                    $scheduledate=$row["scheduledate"];
                                    $scheduletime=$row["scheduletime"];
                                    $nop=$row["nop"];


                                    // Always show Mark as Selesai button (per user request)

                                    echo '<tr>
                                        <td> &nbsp;'.
                                        substr($title ?? '',0,30)
                                        .'</td>
                                        <td>
                                        '.substr($docname ?? '',0,20).'
                                        </td>
                                        <td style="text-align:center;">
                                            '.substr($scheduledate ?? '',0,10).' '.substr($scheduletime ?? '',0,5).'
                                        </td>
                                        <td style="text-align:center;">
                                            '.$nop.'
                                        </td>

                                        <td>
                                        <div style="display:flex;justify-content: center;align-items:center;gap:8px;">
                                        <a href="?action=view&id='.$scheduleid.'" class="non-style-link"><button  class="btn-primary-soft btn button-icon btn-view"  style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">View</font></button></a>
                                       &nbsp;&nbsp;&nbsp;
                                       <a href="?action=drop&id='.$scheduleid.'&name='.$title.'" class="non-style-link"><button  class="btn-primary-soft btn button-icon btn-delete"  style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">Batal</font></button></a>
                                       ';
                                    // Show Mark as Selesai button if there are incomplete appointments
                                    echo '&nbsp;&nbsp;
                                        <form method="post" action="mark_session_completed.php" style="display:inline;">
                                            <input type="hidden" name="scheduleid" value="'.$scheduleid.'">
                                            <button type="submit" class="btn-primary btn" style="margin-top:10px;padding:8px 18px;">Mark as Selesai</button>
                                        </form>';
                                    echo '</div>
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
                                    <input type="text" name="title" class="input-text" placeholder="Nama Sesi Ini" required><br>
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
                                    <option value="" disabled selected hidden>Pilih dokter yang tersedia...</option><br/>';
                                        
                                        date_default_timezone_set('Asia/Jakarta');
                                        $today = date('Y-m-d');
                                        
                                        // Menampilkan dokter yang tersedia hari ini (ada schedule dengan attendance_status='present')
                                        $list11 = $database->query("
                                            SELECT DISTINCT doctor.* 
                                            FROM doctor 
                                            INNER JOIN schedule ON doctor.docid = schedule.docid 
                                            WHERE schedule.scheduledate = '$today' 
                                            AND schedule.attendance_status = 'present'
                                            ORDER BY doctor.docname ASC
                                        ");
        
                                        if($list11 && $list11->num_rows > 0) {
                                            echo '<optgroup label="Dokter Tersedia Hari Ini">';
                                            for ($y=0; $y<$list11->num_rows; $y++){
                                                $row00=$list11->fetch_assoc();
                                                $sn=$row00["docname"];
                                                $id00=$row00["docid"];
                                                echo "<option value=".$id00.">$sn ✓ Tersedia</option><br/>";
                                            }
                                            echo '</optgroup>';
                                        }
                                        
                                        // Tampilkan juga dokter lain yang tidak hadir hari ini
                                        echo '<optgroup label="Dokter Lainnya (Tidak Hadir Hari Ini)">';
                                        $listOther = $database->query("
                                            SELECT * FROM doctor 
                                            WHERE docid NOT IN (
                                                SELECT DISTINCT docid FROM schedule 
                                                WHERE scheduledate = '$today' 
                                                AND attendance_status = 'present'
                                            )
                                            ORDER BY docname ASC
                                        ");
                                        if($listOther && $listOther->num_rows > 0) {
                                            for ($y=0; $y<$listOther->num_rows; $y++){
                                                $row00=$listOther->fetch_assoc();
                                                $sn=$row00["docname"];
                                                $id00=$row00["docid"];
                                                echo "<option value=".$id00.">$sn</option><br/>";
                                            }
                                        }
                                        echo '</optgroup>';



                                        
                        echo     '       </select><br><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="patient_id" class="form-label">Assign Patient (optional): </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <select name="patient_id" class="box searchable-select">
                                        <option value="" disabled selected hidden></option>
                                        <option value="">Skip assigning patient</option><br/>';

                                        $patientList = $database->query("select * from patient order by pname asc;");

                                        while($patientList && $patRow = $patientList->fetch_assoc()){
                                            $pname = $patRow["pname"];
                                            $pid = $patRow["pid"];
                                            $pemail = $patRow["pemail"];
                                            echo "<option value=".$pid.">".$pname." (".$pemail.")</option><br/>";
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
                                    <input type="number" name="nop" class="input-text" min="1" max="1" value="1" readonly required><br>
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
                                <td colspan="2">
                                    <input type="reset" value="Setel Ulang" class="login-btn btn-primary-soft btn" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                
                                    <input type="submit" value="Place this Session" class="login-btn btn-primary btn" name="shedulesubmit">
                                </td>
                
                            </tr>
                           
                            </form>
                            </tr>
                        </table>
                        </div>
                        </div>
                    </center>
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
                                    // prefill display if already selected
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
        }elseif($action=='drop'){
            $nameget=$_GET["name"];
            echo '
            <div id="popup1" class="overlay">
                    <div class="popup">
                    <center>
                        <h2>Apakah Anda yakin?</h2>
                        <a class="close" href="schedule.php">&times;</a>
                        <div class="content">
                            Anda yakin ingin membatalkan sesi ini?<br><b>('.substr($nameget ?? '',0,40).')</b>
                            
                        </div>
                        <div style="display: flex;justify-content: center;">
                        <form method="post" action="delete-session.php?id='.$id.'" style="display: flex; flex-direction: column; align-items: center;">
                            <label for="cancel_reason"><b>Alasan pembatalan sesi:</b></label>
                            <textarea name="cancel_reason" id="cancel_reason" rows="3" cols="40" required placeholder="Tulis alasan pembatalan..."></textarea>
                            <div style="display: flex;justify-content: center; margin-top: 10px;">
                                <button type="submit" class="btn-primary btn" style="margin:10px;padding:10px;">&nbsp;Ya, Batalkan&nbsp;</button>&nbsp;&nbsp;&nbsp;
                                <a href="schedule.php" class="non-style-link"><button type="button" class="btn-primary btn" style="margin:10px;padding:10px;">&nbsp;&nbsp;Tidak&nbsp;&nbsp;</button></a>
                            </div>
                        </form>
                        </div>
                    </center>
            </div>
            </div>
            '; 
        }elseif($action=='view'){
            $sqlmain= "select schedule.scheduleid,schedule.title,doctor.docname,schedule.scheduledate,schedule.scheduletime,schedule.nop from schedule inner join doctor on schedule.docid=doctor.docid  where  schedule.scheduleid=$id";
            $result= $database->query($sqlmain);
            $row=$result->fetch_assoc();
            $docname=$row["docname"];
            $scheduleid=$row["scheduleid"];
            $title=$row["title"];
            $scheduledate=$row["scheduledate"];
            $scheduletime=$row["scheduletime"];
            
           
            $nop=$row['nop'];


            $sqlmain12= "select * from appointment inner join patient on patient.pid=appointment.pid inner join schedule on schedule.scheduleid=appointment.scheduleid where schedule.scheduleid=$id;";
            $result12= $database->query($sqlmain12);
            echo '
            <div id="popup1" class="overlay">
                    <div class="popup" style="width: 70%;">
                    <center>
                        <h2>Detail Sesi</h2>
                        <a class="close" href="schedule.php">&times;</a>
                        <div class="abc scroll" style="display: flex;justify-content: center;">
                        <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                            
                            <tr>
                                
                                <td class="label-td" colspan="2">
                                    <label for="name" class="form-label">Judul Sesi: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    '.$title.'<br><br>
                                </td>
                                
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="Email" class="form-label">Dokter pada sesi ini: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                '.$docname.'<br><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="nic" class="form-label">Scheduled Date: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                '.$scheduledate.'<br><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="Tele" class="form-label">Scheduled Time: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                '.$scheduletime.'<br><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="spec" class="form-label"><b>Patients that Already registerd for this session:</b> ('.$result12->num_rows."/".$nop.')</label>
                                    <br><br>
                                </td>
                            </tr>

                            
                            <tr>
                            <td colspan="4">
                                <center>
                                 <div class="abc scroll">
                                 <table width="100%" class="sub-table scrolldown" border="0">
                                 <thead>
                                 <tr>   
                                        <th class="table-headin">
                                             Patient ID
                                         </th>
                                         <th class="table-headin">
                                             Nama Pasien
                                         </th>
                                         <th class="table-headin">
                                             
                                             Nomor janji temu
                                             
                                         </th>
                                        
                                         
                                         <th class="table-headin">
                                             Patient Telephone
                                         </th>
                                         
                                 </thead>
                                 <tbody>';
                                 
                
                
                                         
                                         $result= $database->query($sqlmain12);
                
                                         if($result->num_rows==0){
                                             echo '<tr>
                                             <td colspan="7">
                                             <br><br><br><br>
                                             <center>
                                             <img src="../img/notfound.svg" width="25%">
                                             
                                             <br>
                                             <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">We  couldnt find anything related to your keywords !</p>
                                             <a class="non-style-link" href="appointment.php"><button class="btn-primary-soft btn">Show all Appointments</button></a>
                                             </center>
                                             <br><br><br><br>
                                             </td>
                                             </tr>';
                                             
                                         }
                                         else{
                                         for ( $x=0; $x<$result->num_rows;$x++){
                                             $row=$result->fetch_assoc();
                                             $apponum=$row["apponum"];
                                             $pid=$row["pid"];
                                             $pname=$row["pname"];
                                             $ptel=$row["ptel"];
                                             
                                             echo '<tr style="text-align:center;">
                                                <td>
                                                '.substr($pid ?? '',0,15).'
                                                </td>
                                                 <td style="font-weight:600;padding:25px">'.
                                                 
                                                 substr($pname ?? '',0,25)
                                                 .'</td >
                                                 <td style="text-align:center;font-size:23px;font-weight:500; color: var(--btnnicetext);">
                                                 '.$apponum.'
                                                 
                                                 </td>
                                                 <td>
                                                 '.substr($ptel ?? '',0,25).'
                                                 </td>
                                                 
                                                 
                
                                                 
                                             </tr>';
                                             
                                         }
                                     }
                                          
                                     
                
                                    echo '</tbody>
                
                                 </table>
                                 </div>
                                 </center>
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
(function() {
    function attachSearch(selectEl){
        if(!selectEl || selectEl.dataset.searchAttached){return;}
        selectEl.dataset.searchAttached="1";
        var searchInput=document.createElement('input');
        searchInput.type='text';
        searchInput.placeholder='Cari / Search...';
        searchInput.className='input-text';
        searchInput.style.marginBottom='8px';
        selectEl.parentNode.insertBefore(searchInput, selectEl);
        var opts=Array.from(selectEl.options);
        searchInput.addEventListener('input', function(){
            var term=this.value.toLowerCase();
            opts.forEach(function(opt){
                if(opt.value===""){return;}
                opt.hidden = term && opt.text.toLowerCase().indexOf(term)===-1;
            });
        });
    }
    document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('select.searchable-select').forEach(attachSearch);
    });
})();
</script>
</body>
</html>
