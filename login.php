<?php
session_start();
header('Content-Type: application/json');
require 'db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);
$username = $data['username'];
$password = $data['password'];

$sql = "SELECT * FROM users WHERE username = '$username'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    // Kiểm tra mật khẩu đã mã hóa
    if (password_verify($password, $row['password'])) {
        // Lưu session
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['avatar'] = $row['avatar'];
        
        echo json_encode([
            "success" => true, 
            "user" => ["username" => $row['username'], "avatar" => $row['avatar']]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Sai mật khẩu!"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Tài khoản không tồn tại!"]);
}
?>