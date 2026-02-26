<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "van_y_cac"; 

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra
if ($conn->connect_error) {
  die("Kết nối thất bại: " . $conn->connect_error);
}

// Set font tiếng Việt
$conn->set_charset("utf8");
?>