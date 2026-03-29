<?php
/**
 * api/review.php — Gửi đánh giá sản phẩm
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
include '../config/db.php';

if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    echo json_encode(['success'=>false,'message'=>'Cần đăng nhập để đánh giá','need_login'=>true]);
    exit;
}

$raw = file_get_contents('php://input');
$b   = json_decode($raw, true) ?? [];

$sp_id    = (int)($b['id_san_pham'] ?? 0);
$so_sao   = max(1, min(5, (int)($b['so_sao'] ?? 5)));
$noi_dung = trim($b['noi_dung'] ?? '');
$user_id = (int)($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0);

if (!$sp_id) { echo json_encode(['success'=>false,'message'=>'Sản phẩm không hợp lệ']); exit; }

// Lấy tên người dùng
$kh = $conn->query("SELECT HoVaTen FROM khachhang WHERE idKhachHang=$user_id LIMIT 1")->fetch_assoc();
$ho_ten = $conn->real_escape_string($kh['HoVaTen'] ?? 'Khách hàng');
$nd_esc = $conn->real_escape_string($noi_dung);

$conn->query("INSERT INTO danh_gia(id_san_pham,id_khach_hang,ho_ten,so_sao,noi_dung,trang_thai)
    VALUES($sp_id,$user_id,'$ho_ten',$so_sao,'$nd_esc','Chờ duyệt')");

echo json_encode(['success'=>true,'message'=>'Đánh giá đã được gửi và đang chờ duyệt.']);
