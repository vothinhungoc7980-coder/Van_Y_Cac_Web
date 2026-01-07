<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "van_y_cac"; // Tên database chuẩn của bạn

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra
if ($conn->connect_error) {
  die("Kết nối thất bại: " . $conn->connect_error);
}
// Set font tiếng Việt
mysqli_set_charset($conn, 'UTF8');
?>

