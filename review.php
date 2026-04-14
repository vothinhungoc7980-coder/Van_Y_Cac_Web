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

// 1. Đếm số lần khách đã mua thành công sản phẩm này
$q_mua = "SELECT COUNT(DISTINCT dh.id) as total_bought 
          FROM chi_tiet_don_hang ct 
          JOIN don_hang dh ON ct.id_don_hang = dh.id 
          WHERE ct.id_san_pham = $sp_id 
            AND dh.id_khach_hang = $uid 
            AND dh.trang_thai_dh = 'Hoàn thành'";
$row_mua = $conn->query($q_mua)->fetch_assoc();
$total_bought = (int)($row_mua['total_bought'] ?? 0);

// Nếu chưa mua hoặc đơn chưa giao thành công
if ($total_bought === 0) {
    echo json_encode(['success'=>false,'message'=>'Bạn cần mua và nhận hàng thành công sản phẩm này trước khi đánh giá!']);
    exit;
}

// 2. Đếm số lần khách đã đánh giá sản phẩm này
$q_dg = "SELECT COUNT(id) as total_reviewed 
         FROM danh_gia 
         WHERE id_san_pham = $sp_id AND id_khach_hang = $uid";
$row_dg = $conn->query($q_dg)->fetch_assoc();
$total_reviewed = (int)($row_dg['total_reviewed'] ?? 0);

// Nếu số lượt đánh giá đã bằng hoặc vượt quá số đơn hàng thành công
if ($total_reviewed >= $total_bought) {
    echo json_encode(['success'=>false,'message'=>'Hãy mua hàng để đánh giá tiếp nhé!']);
    exit;
}

$kh     = $conn->query("SELECT HoVaTen FROM khachhang WHERE idKhachHang=$uid LIMIT 1")->fetch_assoc();
$ho_ten = $conn->real_escape_string($kh['HoVaTen'] ?? 'Khách hàng');
$nd_esc = $conn->real_escape_string($noi_dung);

// Mặc định trạng thái là 'Đã duyệt' để hiện luôn ra web
$conn->query("INSERT INTO danh_gia(id_san_pham, id_khach_hang, ho_ten, so_sao, noi_dung, trang_thai, ngay_tao)
    VALUES($sp_id, $uid, '$ho_ten', $so_sao, '$nd_esc', 'Chưa trả lời', NOW())");

echo json_encode(['success'=>true,'message'=>'Đánh giá của bạn đã được đăng!']);