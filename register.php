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

   
?>
