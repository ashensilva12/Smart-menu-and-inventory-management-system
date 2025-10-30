<?php
  $itemID = $_POST['itemID'] ?? '';
  $itemname = $_POST['itemname'] ?? '';
  $category = $_POST['category'] ?? '';
  $stock = $_POST['stock'] ?? '';

  $cri1 = $itemID != "";
  $cri2 = $itemname != "";
  $cri3 = $category != "";
  $cri4 = $stock != "";

  if (!$cri1 || !$cri2 || !$cri3 || !$cri4) exit();
?>