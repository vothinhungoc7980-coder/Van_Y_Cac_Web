<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

/* =========================
   1. KẾT NỐI DATABASE
========================= */
$conn = new mysqli("localhost", "root", "", "van_y_cac");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Lỗi kết nối database"]);
    exit();
}

$conn->set_charset("utf8");


/* =========================
   2. NHẬN REQUEST
========================= */
$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents("php://input"), true);


/* =========================
   3. XỬ LÝ API
========================= */
switch ($action) {

    /* =====================================================
       ĐĂNG KÝ
    ===================================================== */
    case 'register':

    $hoVaTen = trim($data['fullname']);
    $taiKhoan = trim($data['username']);
    $email = trim($data['email']);
    $soDienThoai = trim($data['phone']);
    $matKhau = password_hash($data['password'], PASSWORD_DEFAULT);

    if(empty($hoVaTen) || empty($taiKhoan) || empty($email) || empty($soDienThoai) || empty($data['password'])){
        echo json_encode([
            "success" => false,
            "message" => "Vui lòng nhập đầy đủ thông tin!"
        ]);
        exit();
    }

    // ✅ Kiểm tra trùng username, email hoặc phone
    $stmt_check = $conn->prepare("
        SELECT idKhachHang FROM khachhang 
        WHERE TaiKhoan = ? OR Email = ? OR SoDienThoai = ?
    ");
    $stmt_check->bind_param("sss", $taiKhoan, $email, $soDienThoai);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {

        echo json_encode([
            "success" => false,
            "message" => "Tài khoản, Email hoặc SĐT đã tồn tại!"
        ]);

    } else {

        // ✅ Gán mặc định VaiTro = 'Khách hàng'
        $vaiTro = "Khách hàng";
        $trangThai = "Kích hoạt";

        $stmt_insert = $conn->prepare("
            INSERT INTO khachhang 
            (TaiKhoan, MatKhau, HoVaTen, Email, SoDienThoai, VaiTro, TrangThai) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt_insert->bind_param(
            "sssssss",
            $taiKhoan,
            $matKhau,
            $hoVaTen,
            $email,
            $soDienThoai,
            $vaiTro,
            $trangThai
        );

        if ($stmt_insert->execute()) {
            echo json_encode([
                "success" => true,
                "message" => "Đăng ký thành công! Vui lòng đăng nhập."
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Lỗi lưu dữ liệu!"
            ]);
        }
    }

break;


    /* =====================================================
       ĐĂNG NHẬP
    ===================================================== */
    case 'login':

    $taiKhoan = trim($data['username']);
    $matKhauNhap = $data['password'];

    $stmt = $conn->prepare("
        SELECT * FROM khachhang 
        WHERE (TaiKhoan = ? OR Email = ?) 
        AND TrangThai = 'Kích hoạt'
    ");

    $stmt->bind_param("ss", $taiKhoan, $taiKhoan);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $row = $result->fetch_assoc();

        if (password_verify($matKhauNhap, $row['MatKhau'])) {

            $_SESSION['user'] = [
                "id" => $row['idKhachHang'],
                "username" => $row['TaiKhoan'],
                "fullname" => $row['HoVaTen'],
                "role" => $row['VaiTro']
            ];

            echo json_encode([
                "success" => true,
                "redirect" => "trangcanhan.php"
            ]);

        } else {
            echo json_encode([
                "success" => false,
                "message" => "Sai mật khẩu!"
            ]);
        }

    } else {
        echo json_encode([
            "success" => false,
            "message" => "Tài khoản không tồn tại!"
        ]);
    }

break;

    /* =====================================================
       KIỂM TRA SESSION
    ===================================================== */
    case 'getUser':

        if (isset($_SESSION['user'])) {
            echo json_encode([
                "loggedIn" => true,
                "user" => $_SESSION['user']
            ]);
        } else {
            echo json_encode([
                "loggedIn" => false
            ]);
        }

    break;



    /* =====================================================
       ĐĂNG XUẤT
    ===================================================== */
    case 'logout':

        session_unset();
        session_destroy();

        echo json_encode([
            "success" => true
        ]);

    break;



    /* =====================================================
       DEFAULT
    ===================================================== */
    default:
        echo json_encode([
            "success" => false,
            "message" => "Yêu cầu API không hợp lệ"
        ]);
    break;
}


/* =========================
   4. ĐÓNG KẾT NỐI
========================= */
$conn->close();
?>