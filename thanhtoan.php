<?php
session_start();
include 'config/db.php';
// KỂM TRA TÀI KHOẢN BỊ VÔ HIỆU HÓA KHI THANH TOÁN
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $check_user = $conn->query("SELECT TrangThai FROM khachhang WHERE idKhachHang = $uid LIMIT 1")->fetch_assoc();
    
    if ($check_user && $check_user['TrangThai'] === 'Vô hiệu hóa') {
        die('<div style="font-family: Arial, sans-serif; text-align: center; padding: 100px 20px; background: #FAF6EE; height: 100vh; box-sizing: border-box;">
                <div style="max-width: 400px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(139,0,0,0.1); border: 1px solid #E8E1D5;">
                    <div style="font-size: 40px; color: #8B0000; margin-bottom: 15px;">🔒</div>
                    <h2 style="color: #8B0000; margin-top: 0;">Tài khoản bị khóa</h2>
                    <p style="color: #555; line-height: 1.5; margin-bottom: 25px;">Tài khoản của bạn đã bị vô hiệu hóa. Bạn không thể thực hiện thanh toán lúc này.</p>
                    <a href="trangcanhan.php" style="display: inline-block; padding: 10px 25px; background: #8B0000; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">Quay lại</a>
                </div>
             </div>');
    }
}
// Đồng bộ session
if (isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $_SESSION['user']['id'];
    $_SESSION['ho_ten']  = $_SESSION['user']['fullname'];
    $_SESSION['vai_tro'] = $_SESSION['user']['role'];
}
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }

$uid = (int)$_SESSION['user_id'];

// 1. KIỂM TRA XEM CÓ PHẢI LÀ KHÁCH THANH TOÁN LẠI ĐƠN HÀNG CŨ KHÔNG
$repay_don_id = isset($_GET['don']) ? (int)$_GET['don'] : 0;

if ($repay_don_id > 0) {
    // Lấy thông tin đơn hàng cũ
    $old_order = $conn->query("SELECT * FROM don_hang WHERE id = $repay_don_id AND id_khach_hang = $uid AND trang_thai_dh = 'Chờ xác nhận' LIMIT 1")->fetch_assoc();
    
    if (!$old_order) { header('Location: trangcanhan.php?tab=orders'); exit; }

    // XỬ LÝ KHI KHÁCH CHỌN PHƯƠNG THỨC MỚI VÀ BẤM XÁC NHẬN
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dat_hang'])) {
        $pt_moi = $_POST['phuong_thuc_tt'] ?? 'Chuyển khoản';
        
        // Cập nhật phương thức mới vào đơn hàng cũ
        $conn->query("UPDATE don_hang SET phuong_thuc_tt = '$pt_moi' WHERE id = $repay_don_id");

        if ($pt_moi === 'VNPAY') {
            header("Location: vnpay_create.php?id=$repay_don_id");
            exit;
        } else {
            // MB Bank / Chuyển khoản -> Thiết lập để hiện màn hình QR 30s
            $show_qr_order = $old_order['ma_don_hang'];
            $qr_amount = $old_order['thanh_tien'];
            $inserted_don_id = $old_order['id'];
        }
    }

    // Lấy dữ liệu để hiển thị bảng Tóm tắt đơn hàng bên phải
    $tong_tien = $old_order['tong_tien'];
    $phi_ship = $old_order['phi_van_chuyen'];
    $thanh_tien = $old_order['thanh_tien'];
    
    $rs_it = $conn->query("SELECT * FROM chi_tiet_don_hang WHERE id_don_hang = $repay_don_id");
    $order_items = [];
    while($it = $rs_it->fetch_assoc()) {
        $it['ten_vi'] = $it['ten_san_pham'];
        $it['duong_dan'] = $it['hinh_anh'];
        $it['tien_dong'] = $it['thanh_tien'];
        $order_items[] = $it;
    }
} else {
    // 2. NẾU LÀ ĐẶT HÀNG MỚI TỪ GIỎ HÀNG (Giữ nguyên logic cũ)
    
    // Lấy danh sách gh_id từ URL
    $items_param = $_GET['items'] ?? '';
    $gh_ids = array_filter(array_map('intval', explode(',', $items_param)));
    if (empty($gh_ids)) { header('Location: giohang.php'); exit; }

    $in_ids = implode(',', $gh_ids);

// Lấy sản phẩm từ giỏ
    $rs = $conn->query("
        SELECT gh.id AS gh_id, gh.so_luong, gh.size, gh.thong_so_rieng,
               sp.id AS sp_id, sp.ten_vi, sp.gia_ban, sp.gia_goc, sp.duong_dan, sp.so_luong_ton
        FROM gio_hang gh
        JOIN san_pham sp ON gh.id_san_pham = sp.id
        WHERE gh.id IN ($in_ids) AND gh.id_khach_hang = $uid AND sp.trang_thai = 1
    ");
    $order_items = [];
    $tong_tien   = 0;
    while ($r = $rs->fetch_assoc()) {
        $r['tien_dong'] = $r['gia_ban'] * $r['so_luong'];
        $tong_tien     += $r['tien_dong'];
        $order_items[]  = $r;
    }
    if (empty($order_items)) { header('Location: giohang.php'); exit; }

    $phi_ship   = $tong_tien >= 1000000 ? 0 : 30000;
    $thanh_tien = $tong_tien + $phi_ship;
}

// Lấy thông tin user
$user_info = $conn->query("SELECT HoVaTen, SoDienThoai, Email FROM khachhang WHERE idKhachHang=$uid LIMIT 1")->fetch_assoc();

$order_error = '';

// XỬ LÝ ĐẶT HÀNG
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dat_hang'])) {
    $ho_ten   = trim($_POST['ho_ten'] ?? '');
    $sdt      = trim($_POST['so_dien_thoai'] ?? '');
    $dia_chi  = trim($_POST['dia_chi'] ?? '');
    $tinh     = trim($_POST['tinh_thanh'] ?? '');
    $ghi_chu  = trim($_POST['ghi_chu'] ?? '');
    $pttt     = $_POST['phuong_thuc_tt'] ?? 'COD';
    $items_post = $_POST['items'] ?? $items_param;

    if (!$ho_ten || !$sdt || !$dia_chi || !$tinh) {
        $order_error = 'Vui lòng điền đầy đủ thông tin giao hàng.';
    } else {
        $ht_e = $conn->real_escape_string($ho_ten);
        $sd_e = $conn->real_escape_string($sdt);
        $dc_e = $conn->real_escape_string($dia_chi);
        $tc_e = $conn->real_escape_string($tinh);
        $gc_e = $conn->real_escape_string($ghi_chu);
        $pt_e = $conn->real_escape_string($pttt);
        $ma_dh = 'VYC-' . strtoupper(substr(md5(uniqid()), 0, 8));

        $sql = "INSERT INTO don_hang
            (ma_don_hang, id_khach_hang, ho_ten, so_dien_thoai, dia_chi, tinh_thanh,
             ghi_chu, tong_tien, phi_van_chuyen, thanh_tien, phuong_thuc_tt,
             trang_thai_tt, trang_thai_dh)
            VALUES
            ('$ma_dh', $uid, '$ht_e', '$sd_e', '$dc_e', '$tc_e',
             '$gc_e', $tong_tien, $phi_ship, $thanh_tien, '$pt_e',
             'Chờ thanh toán', 'Chờ xác nhận')";

        if ($conn->query($sql)) {
            $don_id = $conn->insert_id;
           foreach ($order_items as $oi) {
                $tn_e  = $conn->real_escape_string($oi['ten_vi']);
                $img_e = $conn->real_escape_string($oi['duong_dan'] ?? '');
                
               // Lấy size và thông số riêng từ giỏ hàng và bọc lại an toàn
                $sz_e  = $conn->real_escape_string($oi['size'] ?? ''); 
                $ts_e  = $conn->real_escape_string($oi['thong_so_rieng'] ?? ''); 
                
                // Thêm cột thong_so_rieng vào lệnh INSERT
                $conn->query("INSERT INTO chi_tiet_don_hang
                    (id_don_hang, id_san_pham, ten_san_pham, gia_ban, so_luong, thanh_tien, hinh_anh, size, thong_so_rieng)
                    VALUES ($don_id, {$oi['sp_id']}, '$tn_e', {$oi['gia_ban']}, {$oi['so_luong']}, {$oi['tien_dong']}, '$img_e', '$sz_e', '$ts_e')");
                $conn->query("UPDATE san_pham SET
                    so_luong_ton = GREATEST(0, so_luong_ton - {$oi['so_luong']}),
                    da_ban = da_ban + {$oi['so_luong']}
                    WHERE id = {$oi['sp_id']}");
            }
        // Xóa sản phẩm đã đặt khỏi giỏ
            $conn->query("DELETE FROM gio_hang WHERE id IN ($in_ids) AND id_khach_hang = $uid");
            
            // XỬ LÝ: NẾU LÀ COD THÌ CHUYỂN HƯỚNG, NẾU LÀ CHUYỂN KHOẢN THÌ HIỆN QR
            if ($pt_e === 'COD') {
           header("Location: trangcanhan.php?tab=orders&new_order=$don_id");
                exit;
            } elseif ($pt_e === 'VNPAY') {
                // Chuyển hướng sang file tạo URL VNPAY
                header("Location: vnpay_create.php?id=$don_id");
                exit;
            } else {
                // Xử lý chuyển khoản MB Bank (mã cũ của bạn)
                $show_qr_order = $ma_dh;
                $qr_amount = $thanh_tien;
                $inserted_don_id = $don_id;
            }
        } else {
            $order_error = 'Lỗi: ' . $conn->error . '. Vui lòng thử lại.';
        }
    }
}

include 'resources/views/layouts/header.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Xác Nhận Đặt Hàng — Vân Y Các</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=EB+Garamond:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{--cr:#8B0000;--cr2:#5C0000;--go:#C9A84C;--pa:#FAF6EE;--ink:#1A0A0A;--mu:#6B6B6B;--bd:#E8E1D5;--fd:'Cormorant Garamond',Georgia,serif;--fb:'EB Garamond',Georgia,serif;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--fb);background:var(--pa);color:var(--ink)}

/* HERO */
.page-hero{background:linear-gradient(135deg,#1A0A0A,#3D0000);padding:24px 0}
.page-hero-inner{max-width:1100px;margin:0 auto;padding:0 20px;display:flex;align-items:center;justify-content:space-between}
.page-hero h1{font-family:var(--fd);font-size:1.6rem;font-weight:700;color:var(--go);display:flex;align-items:center;gap:10px}
.page-hero h1 i{color:rgba(201,168,76,.6)}
.btn-back{color:rgba(255,255,255,.7);text-decoration:none;font-size:.82rem;display:flex;align-items:center;gap:6px;transition:color .2s}
.btn-back:hover{color:var(--go)}
/* BC */
.bc{background:#F0E8D8;border-bottom:1px solid var(--bd);padding:9px 0;font-size:.78rem}
.bc-inner{max-width:1100px;margin:0 auto;padding:0 20px;display:flex;gap:6px;align-items:center}
.bc a{color:var(--cr);text-decoration:none}.bc .sep{color:#ccc}
/* MAIN */
.main{max-width:1100px;margin:0 auto;padding:24px 20px 60px}
.layout{display:grid;grid-template-columns:1fr 380px;gap:22px;align-items:start}
/* STEPS */
.steps{display:flex;align-items:center;justify-content:center;gap:0;margin-bottom:28px}
.step-item{display:flex;flex-direction:column;align-items:center;position:relative}
.step-circle{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;border:2px solid var(--bd);background:#fff;color:#ccc;transition:all .3s}
.step-circle.done{background:var(--go);border-color:var(--go);color:#fff}
.step-circle.active{background:var(--cr);border-color:var(--cr);color:#fff}
.step-label{font-size:.68rem;font-weight:700;color:var(--mu);margin-top:5px;white-space:nowrap}
.step-label.active{color:var(--cr)}
.step-line{width:80px;height:2px;background:var(--bd);margin-bottom:22px}
.step-line.done{background:var(--go)}
/* CARD */
.card{background:#fff;border:1px solid var(--bd);border-radius:8px;overflow:hidden;margin-bottom:16px}
.card-hd{padding:14px 18px;border-bottom:1px solid var(--bd);font-family:var(--fd);font-size:1rem;font-weight:700;color:var(--cr2);display:flex;align-items:center;gap:7px}
.card-hd i{color:var(--go)}
.card-body{padding:20px}
/* FORM */
.frow{display:grid;grid-template-columns:1fr 1fr;gap:13px;margin-bottom:13px}
.fg{margin-bottom:13px}
.fl{display:block;font-size:.7rem;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--cr2);margin-bottom:5px;font-family:var(--fb)}
.fc{width:100%;padding:10px 13px;border:1.5px solid var(--bd);border-radius:4px;font-family:var(--fb);font-size:.9rem;background:#FAF6EE;outline:none;transition:border-color .2s;color:var(--ink)}
.fc:focus{border-color:var(--cr);background:#fff}
/* PAYMENT */
.pt-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.pt-item{border:2px solid var(--bd);border-radius:6px;padding:12px 8px;text-align:center;cursor:pointer;transition:all .2s;background:#fff}
.pt-item:hover{border-color:var(--cr);background:#FFF8EE}
.pt-item.active{border-color:var(--cr);background:#FFF8EE}
.pt-item i{font-size:1.4rem;color:var(--go);display:block;margin-bottom:5px}
.pt-item span{font-size:.78rem;font-weight:700;color:var(--ink)}
/* ORDER SUMMARY */
.sum-card{background:#fff;border:1px solid var(--bd);border-radius:8px;overflow:hidden;position:sticky;top:85px}
.sum-hd{background:linear-gradient(135deg,var(--cr2),var(--cr));padding:14px 18px}
.sum-hd h3{font-family:var(--fd);font-size:1rem;font-weight:700;color:var(--go);display:flex;align-items:center;gap:7px}
.sum-body{padding:16px}
.item-row{display:flex;gap:10px;align-items:center;padding:10px 0;border-bottom:1px solid var(--bd)}
.item-row:last-child{border-bottom:none}
.item-img{width:56px;height:70px;object-fit:cover;border-radius:4px;background:#F5F0E8;border:1px solid var(--bd);flex-shrink:0}
.item-info{flex:1}
.item-name{font-family:var(--fd);font-size:.88rem;font-weight:700;color:var(--ink);line-height:1.3;margin-bottom:2px}
.item-meta{font-size:.73rem;color:var(--mu)}
.item-price{font-family:var(--fd);font-size:.95rem;font-weight:700;color:var(--cr);white-space:nowrap}
.sum-row{display:flex;justify-content:space-between;font-size:.86rem;margin-bottom:8px}
.sum-lbl{color:var(--mu)}.sum-val{font-weight:600}
.sum-divider{height:1px;background:var(--bd);margin:10px 0}
.sum-total{display:flex;justify-content:space-between;align-items:baseline}
.sum-total-lbl{font-family:var(--fd);font-size:1rem;font-weight:700;color:var(--cr2)}
.sum-total-val{font-family:var(--fd);font-size:1.4rem;font-weight:700;color:var(--cr)}
.ship-free{background:#D1FAE5;color:#065F46;font-size:.7rem;font-weight:700;padding:2px 9px;border-radius:10px}
/* BUTTON */
.btn-order{width:100%;padding:14px;background:linear-gradient(135deg,var(--cr2),var(--cr));color:var(--go);border:none;border-radius:4px;font-family:var(--fd);font-size:1.05rem;font-weight:700;letter-spacing:.5px;cursor:pointer;transition:opacity .2s;display:flex;align-items:center;justify-content:center;gap:10px;margin-top:14px}
.btn-order:hover{opacity:.88}
.error-box{background:#FEE2E2;color:#991B1B;border:1px solid #FECACA;border-radius:4px;padding:10px 14px;font-size:.84rem;margin-bottom:14px;display:flex;align-items:center;gap:7px}
@media(max-width:900px){.layout{grid-template-columns:1fr}.sum-card{position:static}.frow{grid-template-columns:1fr}.step-line{width:40px}}
</style>
</head>
<body>
<?php if (isset($show_qr_order)): ?>
<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #FAF6EE; padding: 20px;">
    <div style="background: #fff; padding: 40px; border-radius: 16px; box-shadow: 0 15px 40px rgba(139,0,0,0.1); text-align: center; max-width: 450px; width: 100%; border: 1px solid #E8E1D5;">
        <h2 style="color: #8B0000; font-family: 'Cormorant Garamond', serif; font-weight: bold; margin-bottom: 5px;">Thanh Toán Đơn Hàng</h2>
        <p style="color: #6B6B6B; margin-bottom: 25px;">Quét mã qua ứng dụng Ngân hàng hoặc MoMo</p>

<div style="background: #FFF8EE; padding: 20px; border-radius: 12px; margin-bottom: 20px; border: 2px dashed #C9A84C; display: inline-block;">
            <img src="https://img.vietqr.io/image/MB-0326513356-compact2.png?amount=<?=$qr_amount?>&addInfo=<?=$show_qr_order?>&accountName=VO%20THI%20NHU%20NGOC" alt="Mã QR Thanh Toán" style="max-width: 100%; border-radius: 8px;">
        </div>

        <div style="font-size: 1.4rem; font-weight: bold; color: #8B0000; margin-bottom: 5px;">
            <?=number_format($qr_amount,0,',','.')?> ₫
        </div>
        <div style="font-size: 0.9rem; color: #1A0A0A; margin-bottom: 25px;">
            Nội dung: <strong style="color: #8B0000;"><?=$show_qr_order?></strong>
        </div>
<div id="qr-status" style="margin-bottom: 20px; font-weight: bold; padding: 12px; background: #f8f9fa; border-radius: 8px;">
            <span id="qr-status-text" style="color: #8B0000;"><i class="fas fa-qrcode me-2"></i>Vui lòng quét mã để thanh toán</span>
            <div style="font-size: 0.85rem; color: #6b6b6b; margin-top: 5px; font-weight: normal;">
                Thời gian chờ: <strong id="qr-timer" style="color: #8B0000; font-size: 1.1rem;">30</strong> giây
            </div>
        </div>

        <a href="trangcanhan.php?tab=orders&new_order=<?=$inserted_don_id?>" style="display: inline-block; background: #E8E1D5; color: #1A0A0A; text-decoration: none; padding: 14px 30px; border-radius: 30px; font-weight: bold; transition: 0.3s; width: 100%;">
            Quay lại xem đơn hàng
        </a>
    </div>
</div>

<script>
    // 1. LINK GOOGLE APPS SCRIPT CỦA BẠN:
    const GOOGLE_SHEET_API = 'https://script.google.com/macros/s/AKfycbzK83LD5s7svzfXbr9p-bMxToEPbQb4uQ2hxiGDm4Hd-hr3S_Hn0vXpGbTGyU0h9J--/exec'; 
    
    // 2. Dữ liệu đơn hàng
    const maDon = '<?=$show_qr_order?>';
    const soTien = <?=$qr_amount?>;
    const idDonHang = <?=$inserted_don_id?>;

    let timeLeft = 30;
    let isPaid = false;

    // Vòng lặp: Đếm ngược mỗi 1 giây
    let countdownInterval = setInterval(async function() {
        timeLeft--;
        const timerEl = document.getElementById('qr-timer');
        if(timerEl) timerEl.innerText = timeLeft;

        // Cứ mỗi 3 giây thì "hỏi thăm" Google Sheet 1 lần để xem có tiền vào chưa
        if (timeLeft % 3 === 0 && timeLeft > 0) {
            try {
                let response = await fetch(`${GOOGLE_SHEET_API}?ma_don=${maDon}&so_tien=${soTien}`);
                let data = await response.json();

                // Nếu Sheet báo TÌM THẤY TIỀN (paid = true)
                if (data.paid === true) {
                    isPaid = true;
                    clearInterval(countdownInterval); // Dừng đếm ngược ngay lập tức

                    // Báo cho Database cập nhật thành "Đã thanh toán"
                    await fetch(`xac_nhan_tt.php?id=${idDonHang}`);

                    // HIỆN TÍCH XANH THÀNH CÔNG
                    const statusText = document.getElementById('qr-status-text');
                    statusText.innerHTML = '<i class="fas fa-check-circle me-2" style="font-size: 1.2rem;"></i>Thanh toán thành công! Đang chuyển hướng...';
                    statusText.style.color = '#046C4E';
                    
                    const statusBox = document.getElementById('qr-status');
                    statusBox.style.background = '#DEF7EC';
                    statusBox.style.border = '1px solid #31C48D';
                    
                    timerEl.parentElement.style.display = 'none'; // Ẩn dòng chữ thời gian chờ

                    // Chuyển về trang lịch sử đơn hàng
                    setTimeout(() => {
                        window.location.href = `trangcanhan.php?tab=orders&new_order=${idDonHang}`;
                    }, 1500);
                }
            } catch (error) {
                console.log("Đang kiểm tra Google Sheet...");
            }
        }

        // NẾU HẾT 30 GIÂY MÀ CHƯA CÓ TIỀN
        if (timeLeft <= 0 && !isPaid) {
            clearInterval(countdownInterval); // Dừng quét
            
            // HIỆN LỖI THẤT BẠI MÀU ĐỎ
            const statusText = document.getElementById('qr-status-text');
            statusText.innerHTML = '<i class="fas fa-times-circle me-2" style="font-size: 1.2rem;"></i>Thanh toán thất bại / Hết thời gian';
            statusText.style.color = '#991B1B';
            
            const statusBox = document.getElementById('qr-status');
            statusBox.style.background = '#FEE2E2';
            statusBox.style.border = '1px solid #FCA5A5';
            
            timerEl.parentElement.innerHTML = 'Hệ thống không nhận được khoản thanh toán của bạn.';
        }

    }, 1000); // 1000ms = 1 giây
</script>
<?php else: ?>
<div class="page-hero">
  <div class="page-hero-inner">
    <h1><i class="fas fa-clipboard-list"></i> Xác Nhận Đặt Hàng</h1>
    <a href="giohang.php" class="btn-back"><i class="fas fa-arrow-left"></i> Quay lại giỏ hàng</a>
  </div>
</div>
<div class="bc"><div class="bc-inner">
  <a href="index.php">Trang chủ</a><span class="sep">/</span>
  <a href="giohang.php">Giỏ hàng</a><span class="sep">/</span>
  <span>Xác Nhận Đặt Hàng</span>
</div></div>

<div class="main">
  <!-- STEPS -->
  <div class="steps">
    <div class="step-item">
      <div class="step-circle done"><i class="fas fa-shopping-bag"></i></div>
      <div class="step-label">Giỏ Hàng</div>
    </div>
    <div class="step-line done"></div>
    <div class="step-item">
      <div class="step-circle active"><i class="fas fa-map-marker-alt"></i></div>
      <div class="step-label active">Thông Tin</div>
    </div>
    <div class="step-line"></div>
    <div class="step-item">
      <div class="step-circle"><i class="fas fa-check"></i></div>
      <div class="step-label">Hoàn Tất</div>
    </div>
  </div>

  <div class="layout">
    <!-- LEFT: Form -->
    <div>
      <?php if ($order_error): ?>
      <div class="error-box"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($order_error) ?></div>
      <?php endif; ?>

      <form method="POST" id="orderForm">
        <input type="hidden" name="dat_hang" value="1">
        <input type="hidden" name="items" value="<?= htmlspecialchars($items_param) ?>">

        <!-- Địa chỉ giao hàng -->
       <?php if ($repay_don_id == 0): ?>
        <div class="card">
          <div class="card-hd"><i class="fas fa-map-marker-alt"></i>Địa Chỉ Nhận Hàng</div>
          <div class="card-body">
            <div class="frow">
              <div class="fg">
                <label class="fl">Họ Và Tên *</label>
                <input type="text" name="ho_ten" class="fc" required placeholder="Nguyễn Văn A"
                       value="<?= htmlspecialchars($user_info['HoVaTen'] ?? '') ?>">
              </div>
              <div class="fg">
                <label class="fl">Số Điện Thoại *</label>
                <input type="tel" name="so_dien_thoai" class="fc" required placeholder="0xxxxxxxxx" pattern="[0-9]{10}"
                       value="<?= htmlspecialchars($user_info['SoDienThoai'] ?? '') ?>">
              </div>
            </div>
            <div class="fg">
              <label class="fl">Địa Chỉ *</label>
              <input type="text" name="dia_chi" class="fc" required placeholder="Số nhà, tên đường, phường/xã">
            </div>
            <div class="frow">
              <div class="fg">
                <label class="fl">Tỉnh / Thành Phố *</label>
                <select name="tinh_thanh" class="fc" required>
                  <option value="">-- Chọn tỉnh thành --</option>
                  <?php foreach (['Hà Nội','TP. Hồ Chí Minh','Đà Nẵng','Cần Thơ','Hải Phòng','An Giang','Bà Rịa - Vũng Tàu','Bắc Giang','Bắc Kạn','Bạc Liêu','Bắc Ninh','Bến Tre','Bình Định','Bình Dương','Bình Phước','Bình Thuận','Cà Mau','Cao Bằng','Đắk Lắk','Đắk Nông','Điện Biên','Đồng Nai','Đồng Tháp','Gia Lai','Hà Giang','Hà Nam','Hà Tĩnh','Hải Dương','Hậu Giang','Hòa Bình','Hưng Yên','Khánh Hòa','Kiên Giang','Kon Tum','Lai Châu','Lâm Đồng','Lạng Sơn','Lào Cai','Long An','Nam Định','Nghệ An','Ninh Bình','Ninh Thuận','Phú Thọ','Phú Yên','Quảng Bình','Quảng Nam','Quảng Ngãi','Quảng Ninh','Quảng Trị','Sóc Trăng','Sơn La','Tây Ninh','Thái Bình','Thái Nguyên','Thanh Hóa','Thừa Thiên Huế','Tiền Giang','Trà Vinh','Tuyên Quang','Vĩnh Long','Vĩnh Phúc','Yên Bái'] as $t): ?>
                  <option value="<?= $t ?>"><?= $t ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="fg">
                <label class="fl">Ghi Chú</label>
                <input type="text" name="ghi_chu" class="fc" placeholder="Giao giờ hành chính, gọi trước...">
              </div>
            </div>
          </div>
        </div>

        <!-- Phương thức thanh toán -->
       <?php endif; ?>

        <?php if ($repay_don_id > 0): ?>
        <div style="background: #FFFBEB; border: 1px solid #FCD34D; padding: 15px; border-radius: 8px; margin-bottom: 15px; color: #92400E; font-family: var(--fb); font-size: 0.9rem;">
            <i class="fas fa-info-circle me-2"></i> Bạn đang thực hiện thanh toán lại cho đơn hàng <strong>#<?=$old_order['ma_don_hang']?></strong>. Vui lòng chọn phương thức thanh toán bên dưới để tiếp tục.
        </div>
        <?php endif; ?>

        <div class="card">
          <div class="card-hd"><i class="fas fa-credit-card"></i>Phương Thức Thanh Toán</div>
          <div class="card-body">
           <div class="pt-grid">
              <label class="pt-item" id="pt-vnpay">
                <input type="radio" name="phuong_thuc_tt" value="VNPAY" style="display:none">
                <i class="fas fa-credit-card" style="color: #005BAA;"></i>
                <span>Thanh toán qua VNPAY</span>
              </label>
              <label class="pt-item active" id="pt-cod">
                <input type="radio" name="phuong_thuc_tt" value="COD" checked style="display:none">
                <i class="fas fa-money-bill-wave"></i>
                <span>Thanh toán khi nhận (COD)</span>
              </label>
              <label class="pt-item" id="pt-ck">
                <input type="radio" name="phuong_thuc_tt" value="Chuyển khoản" style="display:none">
                <i class="fas fa-university"></i>
                <span>Chuyển khoản ngân hàng</span>
              </label>
            </div>
            <!-- Thông tin chuyển khoản -->
            <div id="bankInfo" style="display:none;margin-top:14px;background:#FFF8EE;border:1px solid var(--bd);border-radius:6px;padding:13px">
              <div style="font-size:.75rem;font-weight:700;color:var(--cr2);margin-bottom:8px;letter-spacing:.5px">THÔNG TIN CHUYỂN KHOẢN</div>
              <div style="font-size:.85rem;line-height:2">
                <div>Ngân hàng: <strong>MB Bank (Ngân hàng  Quân Đội)</strong></div>
                <div>Số TK: <strong>0326513356</strong></div>
                <div>Chủ TK: <strong>Võ Thị Như Ngọc</strong></div>
                <div>Nội dung: <strong style="color:var(--cr)">VYC + SĐT của bạn</strong></div>
              </div>
            </div>
          </div>
        </div>

        <button type="submit" class="btn-order" id="btnOrder">
          <i class="fas fa-check-circle"></i> Xác Nhận Đặt Hàng
        </button>
      </form>
    </div>

    <!-- RIGHT: Tóm tắt đơn hàng -->
    <div>
      <div class="sum-card">
        <div class="sum-hd"><h3><i class="fas fa-receipt"></i> Đơn Hàng Của Bạn</h3></div>
        <div class="sum-body">
          <!-- Danh sách sản phẩm -->
          <?php foreach ($order_items as $item): ?>
          <div class="item-row">
            <img src="image/<?= htmlspecialchars($item['duong_dan'] ?? 'no-image.jpg') ?>"
                 onerror="this.src='https://placehold.co/56x70/FAF6EE/8B0000?text=SP'"
                 class="item-img" alt="<?= htmlspecialchars($item['ten_vi']) ?>">
            <div class="item-info">
              <div class="item-name"><?= htmlspecialchars($item['ten_vi']) ?></div>
              <div class="item-meta">
                <?php if ($item['size']): ?>Size: <?= htmlspecialchars($item['size']) ?> · <?php endif; ?>
                SL: <?= $item['so_luong'] ?>
              </div>
            </div>
            <div class="item-price"><?= number_format($item['tien_dong'],0,',','.')?>₫</div>
          </div>
          <?php endforeach; ?>

          <div class="sum-divider"></div>
          <div class="sum-row"><span class="sum-lbl">Tạm tính</span><span class="sum-val"><?= number_format($tong_tien,0,',','.')?>₫</span></div>
          <div class="sum-row">
            <span class="sum-lbl">Vận chuyển</span>
            <span><?= $phi_ship===0 ? '<span class="ship-free">Miễn phí</span>' : number_format($phi_ship,0,',','.').'₫' ?></span>
          </div>
          <div class="sum-divider"></div>
          <div class="sum-total">
            <span class="sum-total-lbl">Tổng cộng</span>
            <span class="sum-total-val"><?= number_format($thanh_tien,0,',','.')?>₫</span>
          </div>
          <div style="margin-top:12px;font-size:.75rem;color:var(--mu);text-align:center;font-family:var(--fb)">
            <i class="fas fa-shield-alt me-1" style="color:var(--go)"></i>Thanh toán an toàn &amp; bảo mật
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'resources/views/layouts/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
// Phương thức thanh toán
document.querySelectorAll('.pt-item').forEach(lbl => {
    lbl.addEventListener('click', function() {
        document.querySelectorAll('.pt-item').forEach(l => l.classList.remove('active'));
        this.classList.add('active');
        const val = this.querySelector('input').value;
        document.getElementById('bankInfo').style.display = val === 'Chuyển khoản' ? 'block' : 'none';
    });
});

// Submit
document.getElementById('orderForm').addEventListener('submit', function() {
    const btn = document.getElementById('btnOrder');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
});
}); // end DOMContentLoaded
</script>
<?php endif; ?>
</body>
</html>