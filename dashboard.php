<?php
    $con =new mysqli('localhost:6368','root','1234','resturent');

    $data="TRUNCATE TABLE orders";

    if(isset($_GET['clear'])){
        if($clean=$con->query($data)==true){
            $clean=$con->query($data);
            echo "<script>window.location.href = 'dashboard.html';</script>";
        }
        
        else{
            echo "<script>alert('No any orders available');window.location.href = 'dashboard.html';</script>";
            exit();
        }
        $sql = "SELECT * FROM orders";
        $result = $con->query($sql);
        if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['orderID']}</td>
                <td>{$row['customer']}</td>
                <td>{$row['items']}</td>
                <td>{$row['total']}</td>
            </tr>";
            }
        } 

    }
?>