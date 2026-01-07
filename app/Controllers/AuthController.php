<?php

class AuthController {
    private $conn;

    public function __construct() {
        $this->conn = require_once __DIR__ . '/../../config/db.php';
    }

    public function register($data) {
        $username = $data['username'];
        // Mã hóa mật khẩu
        $password = password_hash($data['password'], PASSWORD_DEFAULT);

        // Kiểm tra user tồn tại
        $check = $this->conn->query("SELECT id FROM users WHERE username = '$username'");
        if ($check->num_rows > 0) {
            return ["success" => false, "message" => "Tên tài khoản đã tồn tại!"];
        }

        $sql = "INSERT INTO users (username, password) VALUES ('$username', '$password')";
        if ($this->conn->query($sql)) {
            return ["success" => true, "message" => "Đăng ký thành công!"];
        }
        return ["success" => false, "message" => "Lỗi hệ thống!"];
    }

    public function login($data) {
        $username = $data['username'];
        $sql = "SELECT * FROM users WHERE username = '$username'";
        $result = $this->conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($data['password'], $row['password'])) {
                if (session_status() === PHP_SESSION_NONE) session_start();
                $_SESSION['user'] = ["username" => $row['username']];
                return ["success" => true, "user" => $_SESSION['user']];
            }
        }
        return ["success" => false, "message" => "Sai tài khoản hoặc mật khẩu!"];
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_destroy();
        return ["success" => true];
    }

    public function checkSession() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['user'])) {
            return ["loggedIn" => true, "user" => $_SESSION['user']];
        }
        return ["loggedIn" => false];
    }
}