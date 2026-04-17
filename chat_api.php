<?php
session_start();
include 'config/db.php';
header('Content-Type: application/json; charset=utf-8');

$uid = (int)($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0);
if (!$uid) {
    echo json_encode(['success' => false, 'need_login' => true]);
    exit;
}

$action = $_GET['action'] ?? '';

// 1. Lấy danh sách tin nhắn
if ($action === 'fetch') {
    $rs = $conn->query("SELECT nguoi_gui, noi_dung, DATE_FORMAT(thoi_gian, '%H:%i') as gio FROM tin_nhan WHERE id_khach_hang = $uid ORDER BY thoi_gian ASC");
    $msgs = [];
    while ($r = $rs->fetch_assoc()) { $msgs[] = $r; }
    echo json_encode(['success' => true, 'messages' => $msgs]);
    exit;
}

// 2. Gửi tin nhắn và AI tự động trả lời
if ($action === 'send') {
    $data = json_decode(file_get_contents('php://input'), true);
    $msg = trim($data['message'] ?? '');
    
    if ($msg === '') { echo json_encode(['success' => false]); exit; }
    
    // Lưu tin nhắn của khách
    $stmt = $conn->prepare("INSERT INTO tin_nhan (id_khach_hang, nguoi_gui, noi_dung) VALUES (?, 'khach', ?)");
    $stmt->bind_param("is", $uid, $msg);
    $stmt->execute();

    // ==========================================
    // LOGIC AI TỰ ĐỘNG TRẢ LỜI TỪ KHÓA
    // ==========================================
    $msg_lower = mb_strtolower($msg, 'UTF-8');
    $ai_reply = "";

    if (str_contains($msg_lower, 'giá') || str_contains($msg_lower, 'bao nhiêu')) {
        $ai_reply = "Dạ giá các sản phẩm Việt Cổ Phục đều được niêm yết công khai trên website. Bạn có thể vào mục 'Bộ Sưu Tập' để xem chi tiết nhé!";
    } elseif (str_contains($msg_lower, 'size') || str_contains($msg_lower, 'kích thước') || str_contains($msg_lower, 'số đo')) {
        $ai_reply = "Dạ Vân Y Các có bảng size chuẩn từ S đến 2XL. Bạn có thể vào xem chi tiết sản phẩm hoặc vào phần 'Tư Vấn AI' để hệ thống đo size chuẩn cho bạn nhé!";
    } elseif (str_contains($msg_lower, 'ship') || str_contains($msg_lower, 'giao hàng')) {
        $ai_reply = "Dạ Vân Y Các miễn phí vận chuyển toàn quốc cho đơn hàng từ 1 triệu ạ. Thời gian giao hàng khoảng 2-4 ngày tùy khu vực.";
    } elseif (str_contains($msg_lower, 'địa chỉ') || str_contains($msg_lower, 'cửa hàng')) {
        $ai_reply = "Dạ hiện tại Vân Y Các đang hoạt động trực tuyến. Chúng mình giao hàng hỏa tốc toàn quốc và bạn được kiểm tra hàng trước khi thanh toán ạ!";
    } elseif (str_contains($msg_lower, 'nhân viên') || str_contains($msg_lower, 'admin') || str_contains($msg_lower, 'người thật')) {
        $ai_reply = "Dạ em là trợ lý ảo. Em đã gửi thông báo đến các bạn chuyên viên tư vấn, bạn đợi một lát nhân viên sẽ vào hỗ trợ trực tiếp cho bạn nhé!";
    } else {
        // Kiểm tra xem admin đã trả lời khách gần đây chưa, nếu chưa thì AI mới rep mặc định
        $check = $conn->query("SELECT nguoi_gui FROM tin_nhan WHERE id_khach_hang=$uid ORDER BY id DESC LIMIT 2")->fetch_all(MYSQLI_ASSOC);
        if (count($check) < 2 || $check[1]['nguoi_gui'] !== 'admin') {
             $ai_reply = "Dạ Vân Y Các xin chào! Đây là trợ lý ảo tự động. Bạn cần em hỗ trợ về size, giá cả, hay phí ship ạ? (Nhắn 'nhân viên' để gặp trực tiếp Admin nhé)";
        }
    }

    // Lưu tin nhắn của AI (nếu có)
    if ($ai_reply !== "") {
        // Đợi 1 giây cho thật (giả lập AI đang gõ)
        sleep(1);
        $stmt_ai = $conn->prepare("INSERT INTO tin_nhan (id_khach_hang, nguoi_gui, noi_dung) VALUES (?, 'ai', ?)");
        $stmt_ai->bind_param("is", $uid, $ai_reply);
        $stmt_ai->execute();
    }

    echo json_encode(['success' => true]);
    exit;
}
?>