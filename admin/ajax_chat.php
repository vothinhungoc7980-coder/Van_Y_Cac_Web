<?php
session_start();
include '../config/db.php'; // Đảm bảo đường dẫn đến db.php là đúng
header('Content-Type: application/json; charset=utf-8');

// Kiểm tra quyền Admin
$role = $_SESSION['vai_tro'] ?? $_SESSION['user']['role'] ?? '';
if ($role !== 'Quản trị viên') {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

$action = $_GET['action'] ?? '';

// Lấy danh sách khách hàng đã nhắn tin
if ($action === 'get_users') {
    $sql = "SELECT kh.idKhachHang, kh.HoVaTen, kh.TaiKhoan, MAX(tn.thoi_gian) as last_time
            FROM tin_nhan tn
            JOIN khachhang kh ON tn.id_khach_hang = kh.idKhachHang
            GROUP BY kh.idKhachHang
            ORDER BY last_time DESC";
    $rs = $conn->query($sql);
    $users = [];
    while($r = $rs->fetch_assoc()) $users[] = $r;
    echo json_encode(['success' => true, 'users' => $users]);
    exit;
}

// Lấy tin nhắn của 1 khách hàng cụ thể
if ($action === 'get_messages') {
    $uid = (int)($_GET['uid'] ?? 0);
    
    // CẬP NHẬT: Đánh dấu tin nhắn của khách này là "Đã đọc" để tắt chuông
    $conn->query("UPDATE tin_nhan SET da_doc = 1 WHERE id_khach_hang = $uid AND nguoi_gui = 'khach'");

    $rs = $conn->query("SELECT nguoi_gui, noi_dung, DATE_FORMAT(thoi_gian, '%H:%i %d/%m') as gio FROM tin_nhan WHERE id_khach_hang = $uid ORDER BY thoi_gian ASC");
    $msgs = [];
    while($r = $rs->fetch_assoc()) $msgs[] = $r;
    echo json_encode(['success' => true, 'messages' => $msgs]);
    exit;
}

// Admin gửi tin nhắn cho khách
if ($action === 'send') {
    $data = json_decode(file_get_contents('php://input'), true);
    $uid = (int)($data['uid'] ?? 0);
    $msg = trim($data['message'] ?? '');

    if ($uid && $msg !== '') {
        $stmt = $conn->prepare("INSERT INTO tin_nhan (id_khach_hang, nguoi_gui, noi_dung) VALUES (?, 'admin', ?)");
        $stmt->bind_param("is", $uid, $msg);
        $stmt->execute();
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}
?>