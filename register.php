<?php
    $Name=$_POST['name']??'';
    $Email=$_POST['email']??'';
    $Password=$_POST['password']??'';

    $Name=trim($Name);
    $Email=trim($Email);
    $Password=trim($Password);

    $cri1=$Name!="";
    $cri2=$Email!="";
    $cri3=$Password!="";

    $lencheck1=strlen($Name)>=3;


    if(!$cri1 || !$cri2 || !$cri3 || !$lencheck1)exit();

    $con =new mysqli('localhost:6368','root','1234','resturent');

    $data="select * from customer where email='$Email'";
    $result=$con->query($data);
    if($result->num_rows >0)exit("<script>alert('Email already exit');window.location.href = 'register.html';</script>");


?>
