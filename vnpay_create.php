<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');
include 'config/db.php';
require_once("vnpay_config.php");

if (isset($_GET['id'])) {
    $don_id = (int)$_GET['id'];
    
    // Lấy thông tin đơn hàng từ DB
    $don = $conn->query("SELECT ma_don_hang, thanh_tien FROM don_hang WHERE id = $don_id LIMIT 1")->fetch_assoc();
    
    if($don) {
        $vnp_TxnRef = $don['ma_don_hang']; // Mã đơn hàng
        $vnp_Amount = $don['thanh_tien'] * 100; // VNPAY yêu cầu nhân 100 (VD: 10000 -> 1000000)
        $vnp_Locale = 'vn';
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
        
       // Tạo thời gian bắt đầu và thời gian hết hạn (cộng thêm 1 phút)
        $startTime = date("YmdHis");
        $expireTime = date('YmdHis', strtotime('+2 minutes'));

        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => $startTime,
            "vnp_ExpireDate" => $expireTime, // Thêm dòng này để giới hạn 1 phút
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => "Thanh toan don hang " . $vnp_TxnRef,
            "vnp_OrderType" => "billpayment",
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef
        );

        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash =   hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }
        
        // Chuyển hướng khách hàng sang cổng VNPAY
        header('Location: ' . $vnp_Url);
        exit;
    }
}
echo "Đơn hàng không tồn tại.";
?>