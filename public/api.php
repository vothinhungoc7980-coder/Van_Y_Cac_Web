<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 1. KẾT NỐI DATABASE
$conn = new mysqli("localhost", "root", "", "vanycac_db");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Lỗi kết nối DB"]));
}

// 2. NHẬN DỮ LIỆU
$action = $_GET['action'] ?? ''; 
$data = json_decode(file_get_contents("php://input"), true);

// 3. XỬ LÝ LOGIC
switch ($action) {
    case 'register':
        $username = $data['username'];
        $password = password_hash($data['password'], PASSWORD_DEFAULT); // Mã hóa mật khẩu
        
        // Kiểm tra xem tên đã tồn tại chưa
        $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
        if ($check->num_rows > 0) {
            echo json_encode(["success" => false, "message" => "Tên tài khoản đã tồn tại!"]);
        } else {
            $conn->query("INSERT INTO users (username, password) VALUES ('$username', '$password')");
            echo json_encode(["success" => true, "message" => "Đăng ký thành công! Vui lòng đăng nhập."]);
        }
        break;

    case 'login':
        $username = $data['username'];
        $sql = "SELECT * FROM users WHERE username = '$username'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Kiểm tra mật khẩu
            if (password_verify($data['password'], $row['password'])) {
                $_SESSION['user'] = ["username" => $row['username']];
                echo json_encode(["success" => true, "user" => $_SESSION['user']]);
            } else {
                echo json_encode(["success" => false, "message" => "Sai mật khẩu!"]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Tài khoản không tồn tại!"]);
        }
        break;

    case 'check_session':
        if (isset($_SESSION['user'])) {
            echo json_encode(["loggedIn" => true, "user" => $_SESSION['user']]);
        } else {
            echo json_encode(["loggedIn" => false]);
        }
        break;

    case 'logout':
        session_destroy();
        echo json_encode(["success" => true]);
        break;

    default:
        echo json_encode(["message" => "Yêu cầu không hợp lệ"]);
        break;
}
?>