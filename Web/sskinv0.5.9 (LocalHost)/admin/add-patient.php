<?php

    session_start();

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='a'){
            header("location: ../login.php");
        }

    }else{
        header("location: ../login.php");
    }

    include("../connection.php");


    if($_POST){
        $result= $database->query("select * from webuser");
        $name=$_POST['name'];
        $nic=$_POST['nic'];
        $email=$_POST['email'];
        $tele=$_POST['Tele'];
        $address=$_POST['address'];
        $dob=$_POST['dob'];
        $password=$_POST['password'];
        $cpassword=$_POST['cpassword'];
        
        if ($password==$cpassword){
            $error='3';
            $result= $database->query("select * from webuser where email='$email';");
            if($result->num_rows==1){
                $error='1';
            }else{

                $sql1="insert into patient(pemail,pname,ppassword,paddress,pnic,pdob,ptel) values('$email','$name','$password','$address','$nic','$dob','$tele');";
                $sql2="insert into webuser values('$email','p')";
                $database->query($sql1);
                $database->query($sql2);

                $error= '4';
                
            }
            
        }else{
            $error='2';
        }
    
    
        
        
    }else{
        $error='3';
    }
    

    header("location: patient.php?action=add&error=".$error);
    
   

?>
