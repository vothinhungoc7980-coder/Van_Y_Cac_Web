<?php
session_start();
include 'config/db.php';
require_once("vnpay_config.php");

$vnp_SecureHash = $_GET['vnp_SecureHash'];
$inputData = array();
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}

unset($inputData['vnp_SecureHash']);
ksort($inputData);
$i = 0;
$hashData = "";
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}

$secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

// Kiểm tra chữ ký an toàn
if ($secureHash == $vnp_SecureHash) {
    $ma_don_hang = $conn->real_escape_string($_GET['vnp_TxnRef']);
    
    if ($_GET['vnp_ResponseCode'] == '00') {
        // Giao dịch thành công -> Cập nhật DB
        $conn->query("UPDATE don_hang SET trang_thai_tt = 'Đã thanh toán' WHERE ma_don_hang = '$ma_don_hang'");
        echo "<script>alert('Thanh toán VNPAY thành công!'); window.location.href='trangcanhan.php?tab=orders';</script>";
    } else {
        // Giao dịch thất bại / Bị hủy
        echo "<script>alert('Giao dịch VNPAY thất bại hoặc bị hủy.'); window.location.href='trangcanhan.php?tab=orders';</script>";
    }
} else {
    echo "Chữ ký không hợp lệ.";
}
?>