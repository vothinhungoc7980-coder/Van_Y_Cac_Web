<?php
/**
 * review.php — Gửi đánh giá sản phẩm (Hiện ngay không cần duyệt)
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
include 'config/db.php'; // Sửa lại đường dẫn chuẩn

if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    echo json_encode(['success'=>false,'message'=>'Cần đăng nhập để đánh giá','need_login'=>true]);
    exit;
}

$b        = json_decode(file_get_contents('php://input'), true) ?? [];
$sp_id    = (int)($b['id_san_pham'] ?? 0);
$so_sao   = max(1, min(5, (int)($b['so_sao'] ?? 5)));
$noi_dung = trim($b['noi_dung'] ?? '');
$uid      = (int)($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0);

if (!$sp_id) { echo json_encode(['success'=>false,'message'=>'Sản phẩm không hợp lệ']); exit; }

// Kiểm tra đã đánh giá sản phẩm này chưa
$check_q = "SELECT id FROM danh_gia WHERE id_san_pham=$sp_id AND id_khach_hang=$uid LIMIT 1";
$exists = $conn->query($check_q)->fetch_assoc();
if ($exists) {
    echo json_encode(['success'=>false,'message'=>'Bạn đã đánh giá sản phẩm này rồi.']);
    exit;
}

$kh     = $conn->query("SELECT HoVaTen FROM khachhang WHERE idKhachHang=$uid LIMIT 1")->fetch_assoc();
$ho_ten = $conn->real_escape_string($kh['HoVaTen'] ?? 'Khách hàng');
$nd_esc = $conn->real_escape_string($noi_dung);

// Mặc định trạng thái là 'Đã duyệt' để hiện luôn ra web
$conn->query("INSERT INTO danh_gia(id_san_pham, id_khach_hang, ho_ten, so_sao, noi_dung, trang_thai, ngay_tao)
    VALUES($sp_id, $uid, '$ho_ten', $so_sao, '$nd_esc', 'Đã duyệt', NOW())");

echo json_encode(['success'=>true,'message'=>'Đánh giá của bạn đã được đăng!']);