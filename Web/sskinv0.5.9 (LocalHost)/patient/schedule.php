<?php
// Nonaktifkan halaman Scheduled Sessions dan arahkan ke My Bookings
header("Location: appointment.php?notice=schedule-disabled");
exit;
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
        
    <title>Sessions</title>
    <style>
        .popup{
            animation: transitionIn-Y-bottom 0.5s;
        }
        .sub-table{
            animation: transitionIn-Y-bottom 0.5s;
        }
        .request-fab{
            position:fixed;
            bottom:24px;
            right:24px;
            z-index:40;
            text-decoration:none;
        }
        .request-fab .login-btn{
            padding-left:18px;
            padding-right:18px;
            border-radius:12px;
            box-shadow:0 12px 32px rgba(0,0,0,0.18);
        }
        /* Revert header layout to original working style */
        .request-fixed-top{
            position:absolute;
            top:20px;
            right:24px;
            z-index:40;
            white-space:nowrap;
        }
        .request-fixed-top .login-btn{
            padding-left:18px;
            padding-right:18px;
            border-radius:10px;
        }
        .top-bar{
            display:flex;
            align-items:center;
            gap:12px;
            padding:20px 20px 10px 20px;
            flex-wrap:wrap;
        }
        .top-bar .back-btn{
            min-width:125px;
        }
        .top-bar .search-wrapper{
            flex:1;
            min-width:320px;
            max-width:680px;
        }
        .top-bar .header-search{
            width:100%;
            justify-content:flex-start;
            gap:10px;
        }
        .top-bar .header-searchbar{
            width:100%;
        }
        .top-bar .date-block{
            text-align:right;
            min-width:140px;
        }
        .top-bar .calendar-btn{
            display:flex;
            justify-content:center;
            align-items:center;
            min-width:46px;
        }
        .top-bar .calendar-btn .btn-label{
            width:46px;
        }
        /* Keep header items on one line and limit search width */
        .top-bar{
            flex-wrap:nowrap;
        }
        .top-bar .search-wrapper{
            flex:1 1 auto;
            max-width:720px;
            min-width:320px;
        }
        .top-bar .header-search{
            width:100%;
            gap:10px;
        }
        .top-bar .header-searchbar{
            width:100%;
        }
        .top-bar .date-block{
            min-width:160px;
        }
        .top-bar .calendar-btn{
            min-width:46px;
        }
        .top-bar .date-block{
            min-width:160px;
            text-align:right;
        }
        .top-bar .calendar-btn{
            min-width:46px;
            display:flex;
            justify-content:center;
            align-items:center;
        }
</style>
</head>
<body>
    <?php

    //learn from w3schools.com

    session_start();

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='p'){
            header("location: ../login.php");
        }else{
            $useremail=$_SESSION["user"];
        }

    }else{
        header("location: ../login.php");
    }
    

    //import database
    include("../connection.php");
    $sqlmain= "select * from patient where pemail=?";
    $stmt = $database->prepare($sqlmain);
    $stmt->bind_param("s",$useremail);
    $stmt->execute();
    $result = $stmt->get_result();
    $userfetch=$result->fetch_assoc();
    $userid= $userfetch["pid"];
    $username=$userfetch["pname"];


    //echo $userid;
    //echo $username;
    
    date_default_timezone_set('Asia/Jakarta');

    $today = date('Y-m-d');


 //echo $userid;
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
                    <td class="menu-btn menu-icon-home " >
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
                    <td class="menu-btn menu-icon-session menu-active menu-icon-session-active">
                        <a href="schedule.php" class="non-style-link-menu non-style-link-menu-active"><div><p class="menu-text">Sesi Terjadwal</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row" >
                    <td class="menu-btn menu-icon-appoinment">
                        <a href="appointment.php" class="non-style-link-menu"><div><p class="menu-text">Janji Temu Saya</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-history">
                        <a href="history.php" class="non-style-link-menu"><div><p class="menu-text">History</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row" >
                    <td class="menu-btn menu-icon-settings">
                        <a href="settings.php" class="non-style-link-menu"><div><p class="menu-text">Pengaturan</p></div></a>
                    </td>
                </tr>
                
            </table>
        </div>
        <?php

                $sqlmain= "select * from schedule inner join doctor on schedule.docid=doctor.docid where schedule.scheduledate>='$today'  order by schedule.scheduledate asc";
                $sqlpt1="";
                $insertkey="";
                $q='';
                $searchtype="All";
                        if($_POST){
                        //print_r($_POST);
                        
                        if(!empty($_POST["search"])){
                            /*TODO: make and understand */
                            $keyword=$_POST["search"];
                            $sqlmain= "select * from schedule inner join doctor on schedule.docid=doctor.docid where schedule.scheduledate>='$today' and (doctor.docname='$keyword' or doctor.docname like '$keyword%' or doctor.docname like '%$keyword' or doctor.docname like '%$keyword%' or schedule.title='$keyword' or schedule.title like '$keyword%' or schedule.title like '%$keyword' or schedule.title like '%$keyword%' or schedule.scheduledate like '$keyword%' or schedule.scheduledate like '%$keyword' or schedule.scheduledate like '%$keyword%' or schedule.scheduledate='$keyword' )  order by schedule.scheduledate asc";
                            //echo $sqlmain;
                            $insertkey=$keyword;
                            $searchtype="Search Result : ";
                            $q='"';
                        }

                    }


                $result= $database->query($sqlmain);
                $resultCount = ($result && $result instanceof mysqli_result) ? $result->num_rows : 0;
                $queryError = ($result) ? "" : $database->error;


                ?>

        <div class="dash-body">
            <div class="request-fixed-top">
                <a href="request-appointment.php" class="non-style-link">
                    <button class="login-btn btn-primary btn">Buat Permintaan</button>
                </a>
            </div>
            <div class="top-bar">
                <a href="schedule.php"><button  class="login-btn btn-primary-soft btn btn-icon-back back-btn"  style="padding-top:11px;padding-bottom:11px;"><font class="tn-in-text">Kembali</font></button></a>
                <div class="search-wrapper">
                    <form action="" method="post" class="header-search">
                        <input type="search" name="search" class="input-text header-searchbar" placeholder="Cari nama dokter, email atau tanggal (YYYY-MM-DD)" list="doctors" value="<?php  echo $insertkey ?>">
                        <?php
                            echo '<datalist id="doctors">';
                            $list11 = $database->query("select DISTINCT * from  doctor;");
                            $list12 = $database->query("select DISTINCT * from  schedule GROUP BY title;");
                            for ($y=0;$y<$list11->num_rows;$y++){
                                $row00=$list11->fetch_assoc();
                                $d=$row00["docname"];
                                echo "<option value='$d'><br/>";
                            };
                            for ($y=0;$y<$list12->num_rows;$y++){
                                $row00=$list12->fetch_assoc();
                                $d=$row00["title"];
                                echo "<option value='$d'><br/>";
                            };
                            echo ' </datalist>';
                        ?>
                        <input type="Submit" value="Cari" class="login-btn btn-primary btn" style="padding-left: 25px;padding-right: 25px;padding-top: 10px;padding-bottom: 10px;">
                    </form>
                </div>
                <div class="date-block">
                    <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">
                        Tanggal Hari Ini
                    </p>
                    <p class="heading-sub12" style="padding: 0;margin: 0;"><?php echo $today; ?></p>
                </div>
                <div class="calendar-btn">
                    <button  class="btn-label"  style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
                </div>
            </div>

            <table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;margin-top:10px; ">
                <tr>
                    <td colspan="4" style="padding-top:10px;width: 100%;" >
                        <p class="heading-main12" style="margin-left: 24px;font-size:18px;color:rgb(49, 49, 49)"><?php echo $searchtype." Sesi"."(".$resultCount.")"; ?> </p>
                        <p class="heading-main12" style="margin-left: 24px;font-size:22px;color:rgb(49, 49, 49)"><?php echo $q.$insertkey.$q ; ?> </p>
                    </td>
                </tr>
                <?php if($queryError!=""){ ?>
                <tr>
                    <td colspan="4" style="padding:0 45px 10px 45px;">
                        <div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:10px;border:1px solid #fecaca;">
                            Terjadi kesalahan saat memuat sesi: <?php echo htmlspecialchars($queryError); ?>
                        </div>
                    </td>
                </tr>
                <?php } ?>
                
                
                <tr>
                   <td colspan="4">
                       <center>
                        <div class="abc scroll">
                        <table width="100%" class="sub-table scrolldown" border="0" style="padding: 50px;border:none">
                            
                        <tbody>
                        
                            <?php

                                
                                

                                if(!$result || $resultCount==0){
                                    echo '<tr>
                                    <td colspan="4">
                                    <br><br><br><br>
                                    <center>
                                        <img src="../img/notfound.svg" width="25%">
                                        
                                        <br>
                                        <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Tidak ditemukan sesi yang cocok dengan pencarian Anda!</p>';
                                        if($queryError!=""){
                                            echo '<p class="heading-sub12" style="color:#c00;">Terjadi kesalahan saat memuat sesi: '.htmlspecialchars($queryError).'</p>';
                                        }
                                        echo '<a class="non-style-link" href="schedule.php"><button  class="login-btn btn-primary-soft btn"  style="display: flex;justify-content: center;align-items: center;margin-left:20px;">&nbsp; Tampilkan semua sesi &nbsp;</font></button>
                                        </a>
                                        <br>
                                        <a class="non-style-link" href="request-appointment.php"><button  class="login-btn btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin-left:20px;">&nbsp; Buat Permintaan &nbsp;</font></button>
                                        </a>
                                        </center>
                                        <br><br><br><br>
                                        </td>
                                        </tr>';
                                    
                                }
                                else{
                                    //echo $result->num_rows;
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

                                        if($scheduleid==""){
                                            break;
                                        }

                                        echo '
                                        <td style="width: 25%;">
                                                <div  class="dashboard-items search-items"  >
                                                
                                                    <div style="width:100%">
                                                            <div class="h1-search">
                                                                '.substr($title ?? '',0,21).'
                                                            </div><br>
                                                            <div class="h3-search">
                                                                '.substr($docname ?? '',0,30).'
                                                            </div>
                                                            <div class="h4-search">
                                                                '.$scheduledate.'<br>Mulai: <b>@'.substr($scheduletime ?? '',0,5).'</b> (24h)
                                                            </div>
                                                            <br>
                                                            <a href="booking.php?id='.$scheduleid.'" ><button  class="login-btn btn-primary-soft btn "  style="padding-top:11px;padding-bottom:11px;width:100%"><font class="tn-in-text">Pesan Sekarang</font></button></a>
                                                    </div>
                                                            
                                                </div>
                                            </td>';

                                    }
                                    echo "</tr>";
                                    
                                    
                                    // echo '<tr>
                                    //     <td> &nbsp;'.
                                    //     substr($title,0,30)
                                    //     .'</td>
                                        
                                    //     <td style="text-align:center;">
                                    //         '.substr($scheduledate,0,10).' '.substr($scheduletime,0,5).'
                                    //     </td>
                                    //     <td style="text-align:center;">
                                    //         '.$nop.'
                                    //     </td>

                                    //     <td>
                                    //     <div style="display:flex;justify-content: center;">
                                        
                                    //     <a href="?action=view&id='.$scheduleid.'" class="non-style-link"><button  class="btn-primary-soft btn button-icon btn-view"  style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">View</font></button></a>
                                    //    &nbsp;&nbsp;&nbsp;
                                    //    <a href="?action=drop&id='.$scheduleid.'&name='.$title.'" class="non-style-link"><button  class="btn-primary-soft btn button-icon btn-delete"  style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">Cancel Session</font></button></a>
                                    //     </div>
                                    //     </td>
                                    // </tr>';
                                    
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

    </div>

    </body>
    </html>
