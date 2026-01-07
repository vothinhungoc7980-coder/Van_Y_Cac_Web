<?php
header('Content-Type: application/json');
require 'db_connect.php';

// Nhận dữ liệu JSON từ fetch
$data = json_decode(file_get_contents("php://input"), true);
$username = $data['username'];
$password = password_hash($data['password'], PASSWORD_DEFAULT); // Mã hóa pass

// Kiểm tra user tồn tại
$check = $conn->query("SELECT id FROM users WHERE username = '$username'");
if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Tên đăng nhập đã tồn tại!"]);
    exit();
}

// Thêm vào DB
$sql = "INSERT INTO users (username, password) VALUES ('$username', '$password')";
if ($conn->query($sql)) {
    echo json_encode(["success" => true, "message" => "Đăng ký thành công!"]);
} else {
    echo json_encode(["success" => false, "message" => "Lỗi hệ thống!"]);
}
?>