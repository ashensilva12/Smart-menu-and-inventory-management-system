<?php
    $con =new mysqli('localhost:6368','root','1234','resturent');

    $data="TRUNCATE TABLE orders";

    if(isset($_GET['clear'])){
        if($clean=$con->query($data)==true){
            $clean=$con->query($data);
            echo "<script>window.location.href = 'dashboard.html';</script>";
        }
    }
?>