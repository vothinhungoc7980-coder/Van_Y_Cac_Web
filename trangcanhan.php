<?php
session_start();
include 'config/db.php';

// Đồng bộ session
if (isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $_SESSION['user']['id'];
    $_SESSION['ho_ten']  = $_SESSION['user']['fullname'];
    $_SESSION['vai_tro'] = $_SESSION['user']['role'];
}
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }

$uid  = (int)$_SESSION['user_id'];
$user = $conn->query("SELECT * FROM khachhang WHERE idKhachHang=$uid LIMIT 1")->fetch_assoc();
if (!$user) { session_destroy(); header('Location: index.php'); exit; }

$ok = $err = '';
$tab        = $_GET['tab']       ?? 'profile';
$view_order = isset($_GET['don']) ? (int)$_GET['don'] : 0;
$new_order  = isset($_GET['new_order']) ? (int)$_GET['new_order'] : 0; // Đơn vừa đặt

// CẬP NHẬT THÔNG TIN
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_profile'])) {
    $hoten = trim($_POST['ho_va_ten'] ?? '');
    $sdt   = trim($_POST['so_dien_thoai'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if (!$hoten) { $err = 'Họ tên không được để trống.'; }
    elseif ($sdt && !preg_match('/^[0-9]{10}$/',$sdt)) { $err = 'Số điện thoại không hợp lệ (10 chữ số).'; }
    else {
        $ht=$conn->real_escape_string($hoten); $sd=$conn->real_escape_string($sdt); $em=$conn->real_escape_string($email);
        $conn->query("UPDATE khachhang SET HoVaTen='$ht',SoDienThoai='$sd',Email='$em' WHERE idKhachHang=$uid");
        $_SESSION['ho_ten']=$hoten;
        $user['HoVaTen']=$hoten; $user['SoDienThoai']=$sdt; $user['Email']=$email;
        $ok='Đã cập nhật thông tin thành công!';
    }
}

// ĐỔI MẬT KHẨU
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['change_password'])) {
    $mk_cu=$_POST['mat_khau_cu']??''; $mk_moi=$_POST['mat_khau_moi']??''; $mk_xn=$_POST['mat_khau_xn']??'';
    if (!password_verify($mk_cu,$user['MatKhau'])) { $err='Mật khẩu hiện tại không đúng.'; }
    elseif (strlen($mk_moi)<6) { $err='Mật khẩu mới phải ít nhất 6 ký tự.'; }
    elseif ($mk_moi!==$mk_xn) { $err='Mật khẩu xác nhận không khớp.'; }
    else {
        $hash=password_hash($mk_moi,PASSWORD_DEFAULT);
        $conn->query("UPDATE khachhang SET MatKhau='$hash' WHERE idKhachHang=$uid");
        $ok='Đã đổi mật khẩu thành công!';
    }
}

// DỮ LIỆU
$don_hang   = $conn->query("SELECT * FROM don_hang WHERE id_khach_hang=$uid ORDER BY ngay_tao DESC");
$tong_don   = (int)$conn->query("SELECT COUNT(*) c FROM don_hang WHERE id_khach_hang=$uid")->fetch_assoc()['c'];
$tong_chi   = (float)$conn->query("SELECT COALESCE(SUM(thanh_tien),0) c FROM don_hang WHERE id_khach_hang=$uid AND trang_thai_dh='Hoàn thành'")->fetch_assoc()['c'];
$don_cho    = (int)$conn->query("SELECT COUNT(*) c FROM don_hang WHERE id_khach_hang=$uid AND trang_thai_dh='Chờ xác nhận'")->fetch_assoc()['c'];
$cart_count = (int)$conn->query("SELECT COALESCE(SUM(so_luong),0) c FROM gio_hang WHERE id_khach_hang=$uid")->fetch_assoc()['c'];

// Chi tiết đơn hàng
$order_detail = null; $order_items = [];
if ($view_order) {
    $order_detail = $conn->query("SELECT * FROM don_hang WHERE id=$view_order AND id_khach_hang=$uid LIMIT 1")->fetch_assoc();
    if ($order_detail) {
        $rs_it = $conn->query("SELECT * FROM chi_tiet_don_hang WHERE id_don_hang=$view_order");
        while ($it=$rs_it->fetch_assoc()) $order_items[]=$it;
        $tab='orders';
    }
}

include 'resources/views/layouts/header.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Trang Cá Nhân — Vân Y Các</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=EB+Garamond:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{--cr:#8B0000;--cr2:#5C0000;--go:#C9A84C;--pa:#FAF6EE;--ink:#1A0A0A;--mu:#6B6B6B;--bd:#E8E1D5;--fd:'Cormorant Garamond',Georgia,serif;--fb:'EB Garamond',Georgia,serif;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--fb);background:var(--pa);color:var(--ink);font-size:15px}
/* HERO */
.ph{background:linear-gradient(135deg,#1A0A0A,#3D0000);padding:32px 0}
.ph-inner{max-width:1100px;margin:0 auto;padding:0 20px;display:flex;align-items:center;gap:18px;flex-wrap:wrap}
.ph-av{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--go),var(--cr));display:flex;align-items:center;justify-content:center;font-family:var(--fd);font-size:1.8rem;font-weight:700;color:#fff;flex-shrink:0;border:3px solid rgba(201,168,76,.35)}
.ph-info h2{font-family:var(--fd);font-size:1.5rem;font-weight:700;color:var(--go);margin:0}
.ph-info p{color:rgba(255,255,255,.55);font-size:.82rem;margin:2px 0 0}
.ph-badge{display:inline-flex;align-items:center;gap:5px;background:rgba(201,168,76,.12);border:1px solid rgba(201,168,76,.3);color:var(--go);font-size:.66rem;font-weight:700;padding:3px 10px;border-radius:20px;margin-top:6px;letter-spacing:.5px}
/* BC */
.bc{background:#F0E8D8;border-bottom:1px solid var(--bd);padding:9px 0;font-size:.78rem}
.bc-inner{max-width:1100px;margin:0 auto;padding:0 20px;display:flex;gap:6px;align-items:center}
.bc a{color:var(--cr);text-decoration:none}.bc .sep{color:#ccc}
/* LAYOUT */
.main{max-width:1100px;margin:0 auto;padding:24px 20px 60px}
.layout{display:grid;grid-template-columns:220px 1fr;gap:20px;align-items:start}
/* SIDEBAR */
.sidebar{position:sticky;top:85px}
.sb-card{background:#fff;border:1px solid var(--bd);border-radius:8px;overflow:hidden}
.sb-top{padding:20px 14px;text-align:center;background:linear-gradient(135deg,#FAF6EE,#F0E8D8);border-bottom:1px solid var(--bd)}
.sb-av{width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,var(--go),var(--cr));display:flex;align-items:center;justify-content:center;font-family:var(--fd);font-size:1.3rem;font-weight:700;color:#fff;margin:0 auto 8px}
.sb-name{font-family:var(--fd);font-size:.95rem;font-weight:700;color:var(--cr2)}
.sb-role{font-size:.7rem;color:var(--mu);margin-top:1px}
.sb-nav a{display:flex;align-items:center;gap:9px;padding:11px 15px;font-size:.85rem;color:var(--mu);text-decoration:none;border-bottom:1px solid var(--bd);transition:all .15s;font-family:var(--fb)}
.sb-nav a:last-child{border-bottom:none}
.sb-nav a:hover{background:#FAF6EE;color:var(--cr)}
.sb-nav a.active{background:linear-gradient(to right,#FFF8EE,#fff);color:var(--cr);font-weight:700;border-left:3px solid var(--cr)}
.sb-nav a i{width:16px;text-align:center;flex-shrink:0}
.sb-num{margin-left:auto;background:var(--cr);color:#FFD700;font-size:.58rem;font-weight:800;padding:2px 6px;border-radius:10px}
/* STATS */
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px}
.stat{background:#fff;border:1px solid var(--bd);border-radius:8px;padding:14px;text-align:center}
.stat i{font-size:1.1rem;color:var(--go);display:block;margin-bottom:5px}
.stat-v{font-family:var(--fd);font-size:1.3rem;font-weight:700;color:var(--cr)}
.stat-l{font-size:.68rem;color:var(--mu);margin-top:1px}
/* CARD */
.card{background:#fff;border:1px solid var(--bd);border-radius:8px;overflow:hidden;margin-bottom:14px}
.card-hd{padding:13px 17px;border-bottom:1px solid var(--bd);display:flex;align-items:center;justify-content:space-between}
.card-title{font-family:var(--fd);font-size:1rem;font-weight:700;color:var(--cr2);display:flex;align-items:center;gap:7px}
.card-title i{color:var(--go)}
.card-body{padding:20px}
/* FORM */
.frow{display:grid;grid-template-columns:1fr 1fr;gap:13px;margin-bottom:13px}
.fg{margin-bottom:13px}
.fl{display:block;font-size:.7rem;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--cr2);margin-bottom:5px;font-family:var(--fb)}
.fc{width:100%;padding:9px 12px;border:1.5px solid var(--bd);border-radius:4px;font-family:var(--fb);font-size:.9rem;background:#FAF6EE;outline:none;transition:border-color .2s;color:var(--ink)}
.fc:focus{border-color:var(--cr);background:#fff}
.fc[readonly]{background:#F5F0E8;cursor:default;color:var(--mu)}
.btn-save{padding:10px 24px;background:var(--cr);color:#fff;border:none;border-radius:4px;font-family:var(--fd);font-size:.92rem;font-weight:700;cursor:pointer;transition:background .2s;display:inline-flex;align-items:center;gap:7px}
.btn-save:hover{background:var(--cr2)}
/* ALERT */
.alert-box{padding:10px 14px;border-radius:4px;font-size:.85rem;margin-bottom:14px;display:flex;align-items:center;gap:7px;font-family:var(--fb)}
.alert-ok{background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0}
.alert-err{background:#FEE2E2;color:#991B1B;border:1px solid #FECACA}
.alert-new{background:#FEF3C7;color:#92400E;border:1px solid #FDE68A}
/* ORDER TABLE */
.otbl{width:100%;border-collapse:collapse;font-size:.82rem;font-family:var(--fb)}
.otbl th{background:#FAF6EE;padding:9px 12px;text-align:left;font-size:.68rem;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--cr2);border-bottom:2px solid var(--bd)}
.otbl td{padding:9px 12px;border-bottom:1px solid var(--bd);vertical-align:middle;font-family:var(--fb)}
.otbl tr:hover td{background:#FDFAF5}
.otbl tr:last-child td{border-bottom:none}
.badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:20px;font-size:.64rem;font-weight:700;letter-spacing:.3px;white-space:nowrap}
.badge-success{background:#D1FAE5;color:#065F46}
.badge-warning{background:#FEF3C7;color:#92400E}
.badge-danger{background:#FEE2E2;color:#991B1B}
.badge-info{background:#DBEAFE;color:#1E40AF}
.badge-secondary{background:#F3F4F6;color:#374151}
.badge-purple{background:#EDE9FE;color:#5B21B6}
.badge-new{background:#FEF9C3;color:#854D0E;border:1.5px solid #FCD34D;animation:pulse .8s infinite alternate}
@keyframes pulse{from{opacity:.7}to{opacity:1}}
/* ORDER DETAIL */
.order-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px}
.oig-box{background:#FAF6EE;border:1px solid var(--bd);border-radius:6px;padding:13px}
.oig-title{font-size:.68rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--mu);margin-bottom:8px;font-family:var(--fb)}
.oig-row{display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:5px;font-family:var(--fb)}
.oig-row:last-child{margin-bottom:0}
.oig-lbl{color:var(--mu)}.oig-val{font-weight:600;color:var(--ink);text-align:right;max-width:60%}
.item-row{display:flex;gap:12px;align-items:center;padding:11px 0;border-bottom:1px solid var(--bd)}
.item-row:last-child{border-bottom:none}
.item-img{width:60px;height:75px;object-fit:cover;border-radius:4px;background:#F5F0E8;border:1px solid var(--bd);flex-shrink:0}
.item-info{flex:1}
.item-name{font-weight:700;font-size:.88rem;color:var(--ink);margin-bottom:2px;font-family:var(--fd)}
.item-meta{font-size:.75rem;color:var(--mu);font-family:var(--fb)}
.item-price{font-family:var(--fd);font-size:.98rem;font-weight:700;color:var(--cr);white-space:nowrap}
/* STEP BAR */
.step-bar{display:flex;align-items:center;margin-bottom:20px}
.step-dot{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;flex-shrink:0;border:2px solid #E8E1D5;background:#fff;color:#ccc;transition:all .3s}
.step-dot.done{background:var(--go);border-color:var(--go);color:#fff}
.step-dot.current{background:var(--cr);border-color:var(--cr);color:#fff}
.step-label{font-size:.63rem;font-weight:700;color:var(--mu);letter-spacing:.3px;white-space:nowrap;margin-top:4px;text-align:center;font-family:var(--fb)}
.step-line{flex:1;height:2px;background:#E8E1D5;margin:0 4px;margin-bottom:18px}
.step-line.done{background:var(--go)}
.order-filter{display:flex;gap:7px;flex-wrap:wrap;margin-bottom:14px}
.of-btn{padding:5px 13px;border-radius:20px;font-size:.76rem;font-weight:700;border:1.5px solid var(--bd);background:#fff;color:var(--mu);cursor:pointer;transition:all .2s;font-family:var(--fb)}
.of-btn:hover,.of-btn.active{background:var(--cr);border-color:var(--cr);color:#fff}
.back-btn{display:inline-flex;align-items:center;gap:6px;font-size:.8rem;color:var(--cr);text-decoration:none;margin-bottom:12px;padding:6px 13px;border:1px solid var(--bd);border-radius:4px;background:#fff;transition:all .2s;font-family:var(--fb)}
.back-btn:hover{background:var(--pa);color:var(--cr2)}
.tab-pane{display:none}.tab-pane.active{display:block}
@media(max-width:900px){.layout{grid-template-columns:1fr}.sidebar{position:static}.frow{grid-template-columns:1fr}.stats{grid-template-columns:1fr 1fr}.order-info-grid{grid-template-columns:1fr}}
</style>
</head>
<body>

<div class="ph"><div class="ph-inner">
  <div class="ph-av"><?= strtoupper(mb_substr($user['HoVaTen']??$user['TaiKhoan'],0,1)) ?></div>
  <div class="ph-info">
    <h2><?= htmlspecialchars($user['HoVaTen']??$user['TaiKhoan']) ?></h2>
    <p>@<?= htmlspecialchars($user['TaiKhoan']) ?> &middot; Thành viên từ <?= date('m/Y',strtotime($user['NgayTao'])) ?></p>
    <span class="ph-badge"><i class="fas fa-user-check"></i> Khách Hàng Vân Y Các</span>
  </div>
</div></div>

<div class="bc"><div class="bc-inner">
  <a href="index.php">Trang chủ</a><span class="sep">/</span>
  <a href="trangcanhan.php">Trang cá nhân</a>
  <?php if ($view_order && $order_detail): ?>
  <span class="sep">/</span><span>Đơn #<?= htmlspecialchars($order_detail['ma_don_hang']) ?></span>
  <?php endif; ?>
</div></div>

<div class="main"><div class="layout">

  <!-- SIDEBAR -->
  <div class="sidebar"><div class="sb-card">
    <div class="sb-top">
      <div class="sb-av"><?= strtoupper(mb_substr($user['HoVaTen']??$user['TaiKhoan'],0,1)) ?></div>
      <div class="sb-name"><?= htmlspecialchars($user['HoVaTen']??$user['TaiKhoan']) ?></div>
      <div class="sb-role"><?= htmlspecialchars($user['TaiKhoan']) ?></div>
    </div>
    <div class="sb-nav">
      <a href="?tab=profile" class="<?= $tab==='profile'&&!$view_order?'active':'' ?>"><i class="fas fa-user"></i> Thông Tin Cá Nhân</a>
      <a href="?tab=orders" class="<?= $tab==='orders'?'active':'' ?>">
        <i class="fas fa-box"></i> Đơn Hàng
        <?php if ($don_cho>0): ?><span class="sb-num"><?= $don_cho ?></span><?php endif; ?>
      </a>
      <a href="?tab=password" class="<?= $tab==='password'&&!$view_order?'active':'' ?>"><i class="fas fa-lock"></i> Đổi Mật Khẩu</a>
      <a href="giohang.php"><i class="fas fa-shopping-bag"></i> Giỏ Hàng
        <?php if ($cart_count>0): ?><span class="sb-num"><?= $cart_count ?></span><?php endif; ?>
      </a>
      <a href="logout.php" onclick="return confirm('Đăng xuất?')" style="color:#dc2626"><i class="fas fa-sign-out-alt"></i> Đăng Xuất</a>
    </div>
  </div></div>

  <!-- CONTENT -->
  <div>
    <?php if ($ok): ?><div class="alert-box alert-ok"><i class="fas fa-check-circle"></i><?= htmlspecialchars($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert-box alert-err"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <?php if ($new_order && $tab==='orders'): ?>
    <div class="alert-box alert-new"><i class="fas fa-party-horn"></i>🎉 Đặt hàng thành công! Đơn hàng của bạn đang được xử lý.</div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="stats">
      <div class="stat"><i class="fas fa-box"></i><div class="stat-v"><?= $tong_don ?></div><div class="stat-l">Tổng Đơn Hàng</div></div>
      <div class="stat"><i class="fas fa-coins"></i><div class="stat-v"><?= number_format($tong_chi/1000000,1) ?>M</div><div class="stat-l">Đã Chi (₫)</div></div>
      <div class="stat"><i class="fas fa-shopping-bag"></i><div class="stat-v"><?= $cart_count ?></div><div class="stat-l">Trong Giỏ</div></div>
    </div>

    <!-- TAB PROFILE -->
    <div class="tab-pane <?= $tab==='profile'&&!$view_order?'active':'' ?>">
      <div class="card">
        <div class="card-hd"><div class="card-title"><i class="fas fa-user"></i>Thông Tin Cá Nhân</div></div>
        <div class="card-body">
          <form method="POST">
            <div class="frow">
              <div class="fg"><label class="fl">Họ Và Tên *</label><input type="text" name="ho_va_ten" class="fc" value="<?= htmlspecialchars($user['HoVaTen']??'') ?>" required placeholder="Nguyễn Thị A"></div>
              <div class="fg"><label class="fl">Tài Khoản</label><input type="text" class="fc" value="<?= htmlspecialchars($user['TaiKhoan']) ?>" readonly></div>
            </div>
            <div class="frow">
              <div class="fg"><label class="fl">Số Điện Thoại</label><input type="tel" name="so_dien_thoai" class="fc" value="<?= htmlspecialchars($user['SoDienThoai']??'') ?>" pattern="[0-9]{10}" placeholder="0xxxxxxxxx"></div>
              <div class="fg"><label class="fl">Email</label><input type="email" name="email" class="fc" value="<?= htmlspecialchars($user['Email']??'') ?>" placeholder="example@email.com"></div>
            </div>
            <div class="fg"><label class="fl">Ngày Tham Gia</label><input type="text" class="fc" value="<?= date('d/m/Y H:i',strtotime($user['NgayTao'])) ?>" readonly></div>
            <button type="submit" name="update_profile" class="btn-save"><i class="fas fa-save"></i> Lưu Thay Đổi</button>
          </form>
        </div>
      </div>
    </div>

    <!-- TAB ORDERS -->
    <div class="tab-pane <?= $tab==='orders'?'active':'' ?>">
      <?php
      $bdg_map=['Chờ xác nhận'=>'badge-warning','Đã xác nhận'=>'badge-info','Đang giao'=>'badge-purple','Hoàn thành'=>'badge-success','Đã hủy'=>'badge-danger'];

      if ($view_order && $order_detail):
        $cls=$bdg_map[$order_detail['trang_thai_dh']]??'badge-secondary';
        $statuses_arr=['Chờ xác nhận','Đã xác nhận','Đang giao','Hoàn thành'];
        $cur_idx=array_search($order_detail['trang_thai_dh'],$statuses_arr);
      ?>
      <a href="?tab=orders" class="back-btn"><i class="fas fa-arrow-left"></i> Quay lại danh sách</a>
      <div class="card">
        <div class="card-hd">
          <div class="card-title"><i class="fas fa-receipt"></i>Đơn: <?= htmlspecialchars($order_detail['ma_don_hang']) ?></div>
          <span class="badge <?= $cls ?>"><?= htmlspecialchars($order_detail['trang_thai_dh']) ?></span>
        </div>
        <div class="card-body">
          <!-- Step bar -->
          <?php if ($order_detail['trang_thai_dh']!=='Đã hủy'): ?>
          <div class="step-bar">
            <?php
            $steps=[['fas fa-clock','Chờ XN'],['fas fa-check','Xác Nhận'],['fas fa-truck','Đang Giao'],['fas fa-box-open','Hoàn Thành']];
            foreach ($steps as $si=>[$icon,$label]):
              $done=$cur_idx!==false&&$si<$cur_idx;
              $current=$cur_idx!==false&&$si===$cur_idx;
            ?>
            <div style="display:flex;flex-direction:column;align-items:center">
              <div class="step-dot <?= $done?'done':($current?'current':'') ?>"><i class="<?= $icon ?>"></i></div>
              <div class="step-label"><?= $label ?></div>
            </div>
            <?php if ($si<count($steps)-1): ?>
            <div class="step-line <?= $done?'done':'' ?>"></div>
            <?php endif; endforeach; ?>
          </div>
          <?php endif; ?>

          <!-- Info grid -->
          <div class="order-info-grid">
            <div class="oig-box">
              <div class="oig-title"><i class="fas fa-info-circle me-1"></i>Thông Tin Đơn</div>
              <div class="oig-row"><span class="oig-lbl">Mã đơn</span><span class="oig-val" style="color:var(--cr);font-weight:800"><?= htmlspecialchars($order_detail['ma_don_hang']) ?></span></div>
              <div class="oig-row"><span class="oig-lbl">Ngày đặt</span><span class="oig-val"><?= date('d/m/Y H:i',strtotime($order_detail['ngay_tao'])) ?></span></div>
              <div class="oig-row"><span class="oig-lbl">Thanh toán</span><span class="oig-val"><?= htmlspecialchars($order_detail['phuong_thuc_tt']??'COD') ?></span></div>
              <div class="oig-row"><span class="oig-lbl">TT thanh toán</span><span class="oig-val"><span class="badge <?= $order_detail['trang_thai_tt']==='Đã thanh toán'?'badge-success':'badge-warning' ?>"><?= htmlspecialchars($order_detail['trang_thai_tt']??'') ?></span></span></div>
            </div>
            <div class="oig-box">
              <div class="oig-title"><i class="fas fa-map-marker-alt me-1"></i>Địa Chỉ Giao</div>
              <div class="oig-row"><span class="oig-lbl">Người nhận</span><span class="oig-val"><?= htmlspecialchars($order_detail['ho_ten']??'') ?></span></div>
              <div class="oig-row"><span class="oig-lbl">Điện thoại</span><span class="oig-val"><?= htmlspecialchars($order_detail['so_dien_thoai']??'') ?></span></div>
              <div class="oig-row"><span class="oig-lbl">Địa chỉ</span><span class="oig-val"><?= htmlspecialchars($order_detail['dia_chi']??'') ?></span></div>
              <div class="oig-row"><span class="oig-lbl">Tỉnh/Thành</span><span class="oig-val"><?= htmlspecialchars($order_detail['tinh_thanh']??'') ?></span></div>
              <?php if ($order_detail['ghi_chu']): ?>
              <div class="oig-row"><span class="oig-lbl">Ghi chú</span><span class="oig-val"><?= htmlspecialchars($order_detail['ghi_chu']) ?></span></div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Sản phẩm -->
          <div style="font-size:.7rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--mu);margin-bottom:9px;font-family:var(--fb)"><i class="fas fa-box me-1"></i>Sản Phẩm Đã Đặt</div>
          <?php foreach ($order_items as $oi): ?>
          <div class="item-row">
            <img src="image/<?= htmlspecialchars($oi['hinh_anh']??'no-image.jpg') ?>"
                 onerror="this.src='https://placehold.co/60x75/FAF6EE/8B0000?text=SP'"
                 class="item-img" alt="<?= htmlspecialchars($oi['ten_san_pham']) ?>">
            <div class="item-info">
              <div class="item-name">
    <?= htmlspecialchars($oi['ten_san_pham']) ?>
    <?php if ($order_detail['trang_thai_dh'] === 'Hoàn thành'): ?>
        <a href="sanpham.php?id=<?= $oi['id_san_pham'] ?>&review=1" class="badge badge-success ms-2 text-decoration-none"><i class="fas fa-pen"></i> Viết đánh giá</a>
    <?php endif; ?>
</div>
              <div class="item-meta">Số lượng: <?= $oi['so_luong'] ?> &middot; <?= number_format($oi['gia_ban'],0,',','.')?>₫/cái</div>
            </div>
            <div class="item-price"><?= number_format($oi['thanh_tien'],0,',','.')?>₫</div>
          </div>
          <?php endforeach; ?>

          <!-- Tổng -->
          <div style="background:#FAF6EE;border:1px solid var(--bd);border-radius:6px;padding:13px;margin-top:13px">
            <?php $tien_hang=array_sum(array_column($order_items,'thanh_tien')); $phi=($order_detail['phi_van_chuyen']??0); ?>
            <div style="display:flex;justify-content:space-between;font-size:.83rem;margin-bottom:5px;font-family:var(--fb)"><span style="color:var(--mu)">Tổng tiền hàng</span><span><?= number_format($tien_hang,0,',','.')?>₫</span></div>
            <div style="display:flex;justify-content:space-between;font-size:.83rem;margin-bottom:9px;font-family:var(--fb)"><span style="color:var(--mu)">Phí vận chuyển</span><span><?= $phi>0?number_format($phi,0,',','.').'₫':'<span style="color:#16a34a">Miễn phí</span>' ?></span></div>
            <div style="display:flex;justify-content:space-between;border-top:2px solid var(--bd);padding-top:9px"><span style="font-weight:700;font-family:var(--fd)">Tổng thanh toán</span><span style="font-family:var(--fd);font-size:1.2rem;font-weight:700;color:var(--cr)"><?= number_format($order_detail['thanh_tien'],0,',','.')?>₫</span></div>
          </div>

       <?php if ($order_detail['trang_thai_dh'] === 'Chờ xác nhận'): ?>
          <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap">
            
            <?php 
            // Kiểm tra xem có phải phương thức thanh toán online và chưa thanh toán không
            $pt_tt = mb_strtolower($order_detail['phuong_thuc_tt'] ?? '', 'UTF-8');
            $tt_tt = mb_strtolower($order_detail['trang_thai_tt'] ?? '', 'UTF-8');
            
            // Nếu không phải là thanh toán khi nhận hàng (COD) và trạng thái là chưa/chờ thanh toán
            $is_online = (strpos($pt_tt, 'nhận hàng') === false && strpos($pt_tt, 'cod') === false);
            $is_unpaid = (strpos($tt_tt, 'chưa') !== false || strpos($tt_tt, 'chờ') !== false);
            
            if ($is_online && $is_unpaid): 
            ?>
            <a href="thanhtoan.php?don=<?= $order_detail['id'] ?>" 
               style="padding:8px 18px;background:#059669;color:#fff;border-radius:4px;text-decoration:none;font-size:.82rem;font-weight:700;display:inline-flex;align-items:center;gap:6px;font-family:var(--fb);box-shadow:0 2px 4px rgba(5,150,105,0.2);">
               <i class="fas fa-qrcode"></i> Thanh Toán Ngay
            </a>
            <?php endif; ?>

            <button onclick="openCancelModal(<?= $order_detail['id'] ?>, '<?= htmlspecialchars($order_detail['ma_don_hang']) ?>')"
               style="padding:8px 18px;background:#FEE2E2;color:#991B1B;border:1px solid #FECACA;border-radius:4px;cursor:pointer;font-size:.82rem;font-weight:700;display:inline-flex;align-items:center;gap:6px;font-family:var(--fb)"><i class="fas fa-times-circle"></i> Hủy Đơn Hàng</button>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <?php else: ?>
      <!-- DANH SÁCH -->
      <div class="card">
        <div class="card-hd"><div class="card-title"><i class="fas fa-box"></i>Lịch Sử Đơn Hàng (<?= $tong_don ?>)</div></div>
        <?php if ($tong_don>0): ?>
        <div style="padding:11px 15px;border-bottom:1px solid var(--bd)">
          <div class="order-filter" id="orderFilter">
            <button class="of-btn active" onclick="filterOrders('all',this)">Tất Cả (<?= $tong_don ?>)</button>
            <?php
            $sl=['Chờ xác nhận','Đã xác nhận','Đang giao','Hoàn thành','Đã hủy'];
            foreach ($sl as $sv) {
              $sc=(int)$conn->query("SELECT COUNT(*) c FROM don_hang WHERE id_khach_hang=$uid AND trang_thai_dh='".$conn->real_escape_string($sv)."'")->fetch_assoc()['c'];
              if ($sc>0) echo "<button class='of-btn' onclick=\"filterOrders('".htmlspecialchars($sv,ENT_QUOTES)."',this)\">$sv ($sc)</button>";
            }
            ?>
          </div>
        </div>
        <?php endif; ?>
        <div style="overflow-x:auto">
          <?php if ($tong_don===0): ?>
          <div style="text-align:center;padding:52px 20px;color:var(--mu)">
            <i class="fas fa-box-open" style="font-size:2.5rem;display:block;margin-bottom:12px;color:#d0c4b0"></i>
            <p style="margin-bottom:13px;font-family:var(--fb)">Bạn chưa có đơn hàng nào.</p>
            <a href="bosuutap.php" style="padding:8px 20px;background:var(--cr);color:#fff;border-radius:4px;text-decoration:none;font-family:var(--fd);font-weight:700;font-size:.9rem">Khám Phá Ngay →</a>
          </div>
          <?php else: ?>
          <table class="otbl" id="orderTable">
            <thead><tr><th></th><th>Mã Đơn</th><th>Ngày Đặt</th><th>Sản Phẩm</th><th>Tổng Tiền</th><th>TT Thanh Toán</th><th>Trạng Thái</th><th></th></tr></thead>
            <tbody>
            <?php $don_hang->data_seek(0); while ($dh=$don_hang->fetch_assoc()):
              $cls2=$bdg_map[$dh['trang_thai_dh']]??'badge-secondary';
             $sp_first=$conn->query("SELECT id_san_pham, ten_san_pham, hinh_anh FROM chi_tiet_don_hang WHERE id_don_hang={$dh['id']} LIMIT 1")->fetch_assoc();
              $sp_count=(int)$conn->query("SELECT COUNT(*) c FROM chi_tiet_don_hang WHERE id_don_hang={$dh['id']}")->fetch_assoc()['c'];
              $is_new = ($new_order && $dh['id']==$new_order);
            ?>
            <tr data-status="<?= htmlspecialchars($dh['trang_thai_dh']) ?>" <?= $is_new?'style="background:#FFFBEB"':'' ?>>
              <td style="width:52px">
                <?php if ($sp_first && $sp_first['hinh_anh']): ?>
                <img src="image/<?= htmlspecialchars($sp_first['hinh_anh']) ?>"
                     onerror="this.src='https://placehold.co/44x54/FAF6EE/8B0000?text=SP'"
                     style="width:44px;height:54px;object-fit:cover;border-radius:4px;border:1px solid var(--bd)">
                <?php else: ?>
                <div style="width:44px;height:54px;background:#F5F0E8;border-radius:4px;display:flex;align-items:center;justify-content:center;border:1px solid var(--bd)"><i class="fas fa-tshirt" style="color:var(--mu);font-size:.9rem"></i></div>
                <?php endif; ?>
              </td>
              <td><strong style="color:var(--cr);font-size:.8rem"><?= htmlspecialchars($dh['ma_don_hang']) ?></strong>
                <?php if ($is_new): ?><span class="badge badge-new ms-1">Mới</span><?php endif; ?>
              </td>
              <td style="font-size:.77rem;color:var(--mu);white-space:nowrap"><?= date('d/m/Y H:i',strtotime($dh['ngay_tao'])) ?></td>
              <td style="font-size:.8rem"><?php if ($sp_first): ?><?= htmlspecialchars(mb_substr($sp_first['ten_san_pham'],0,26)) ?><?= $sp_count>1?"<span style='color:var(--mu)'> +".($sp_count-1)." sp</span>":'' ?><?php endif; ?></td>
              <td style="font-weight:700;white-space:nowrap;font-family:var(--fd)"><?= number_format($dh['thanh_tien'],0,',','.')?>₫</td>
              <td><span class="badge <?= $dh['trang_thai_tt']==='Đã thanh toán'?'badge-success':'badge-warning' ?>"><?= htmlspecialchars($dh['trang_thai_tt']??'') ?></span></td>
              <td><span class="badge <?= $cls2 ?>"><?= htmlspecialchars($dh['trang_thai_dh']) ?></span></td>
              <td style="white-space:nowrap">
                <a href="?tab=orders&don=<?= $dh['id'] ?>" style="font-size:.75rem;color:var(--cr);text-decoration:none;font-weight:700;padding:4px 10px;border:1px solid var(--bd);border-radius:3px;display:inline-block;font-family:var(--fb)">Chi tiết</a>
             <?php if ($dh['trang_thai_dh']==='Hoàn thành' && $sp_first): ?>
<a href="sanpham.php?id=<?= $sp_first['id_san_pham'] ?>&review=1" style="font-size:.75rem;color:#059669;text-decoration:none;margin-left:5px;font-family:var(--fb);font-weight:700;padding:4px 8px;border:1px solid #A7F3D0;border-radius:3px;display:inline-block">
  <i class="fas fa-star"></i> Đánh giá
</a>
<?php endif; ?>
                <?php if ($dh['trang_thai_dh']==='Chờ xác nhận'): ?>
                <button onclick="openCancelModal(<?= $dh['id'] ?>, '<?= htmlspecialchars($dh['ma_don_hang']) ?>')" style="font-size:.75rem;color:#dc2626;background:none;border:none;cursor:pointer;margin-left:5px;font-family:var(--fb);font-weight:700;padding:4px 0">Hủy</button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- TAB PASSWORD -->
    <div class="tab-pane <?= $tab==='password'&&!$view_order?'active':'' ?>">
      <div class="card">
        <div class="card-hd"><div class="card-title"><i class="fas fa-lock"></i>Đổi Mật Khẩu</div></div>
        <div class="card-body" style="max-width:440px">
          <form method="POST">
            <div class="fg"><label class="fl">Mật Khẩu Hiện Tại *</label><input type="password" name="mat_khau_cu" class="fc" required placeholder="••••••••"></div>
            <div class="fg"><label class="fl">Mật Khẩu Mới * (ít nhất 6 ký tự)</label><input type="password" name="mat_khau_moi" class="fc" required minlength="6" placeholder="••••••••"></div>
            <div class="fg"><label class="fl">Xác Nhận Mật Khẩu Mới *</label><input type="password" name="mat_khau_xn" class="fc" required placeholder="••••••••"></div>
            <button type="submit" name="change_password" class="btn-save"><i class="fas fa-key"></i> Đổi Mật Khẩu</button>
          </form>
        </div>
      </div>
    </div>

  </div>
</div></div>

<?php include 'resources/views/layouts/footer.php'; ?>

<!-- ====== MODAL HỦY ĐƠN HÀNG ====== -->
<div id="cancelModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.52);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;width:90%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.28);overflow:hidden;font-family:'EB Garamond',Georgia,serif;animation:fadeUp .25s ease">
    <style>@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}</style>
    <div style="background:linear-gradient(135deg,#FEE2E2,#FCA5A5);padding:18px 22px;display:flex;align-items:center;gap:12px">
      <div style="width:44px;height:44px;background:rgba(255,255,255,.6);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i class="fas fa-exclamation-triangle" style="color:#991B1B;font-size:1.2rem"></i>
      </div>
      <div>
        <div style="font-family:'Cormorant Garamond',serif;font-size:1.15rem;font-weight:700;color:#7F1D1D">Hủy Đơn Hàng</div>
        <div id="cancelModalCode" style="font-size:.74rem;color:#991B1B;font-weight:600;margin-top:2px"></div>
      </div>
      <button onclick="closeCancelModal()" style="margin-left:auto;background:none;border:none;cursor:pointer;font-size:1.2rem;color:#991B1B;opacity:.6;line-height:1">&times;</button>
    </div>
    <div style="padding:22px">
      <p style="font-size:.9rem;color:#555;margin-bottom:16px;line-height:1.65">Bạn có chắc chắn muốn <strong style="color:#991B1B">hủy đơn hàng</strong> này không? Hàng sẽ được hoàn kho và đơn không thể khôi phục.</p>
      <div style="margin-bottom:14px">
        <label style="display:block;font-size:.68rem;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:#5C0000;margin-bottom:5px">Lý Do Hủy</label>
        <select id="cancelReason" style="width:100%;padding:9px 12px;border:1.5px solid #E8E1D5;border-radius:4px;font-family:'EB Garamond',serif;font-size:.88rem;background:#FAF6EE;outline:none;color:#1A0A0A;transition:border-color .2s">
          <option value="">-- Chọn lý do (tuỳ chọn) --</option>
          <option value="Đặt nhầm sản phẩm">Đặt nhầm sản phẩm</option>
          <option value="Muốn thay đổi địa chỉ giao hàng">Muốn thay đổi địa chỉ giao hàng</option>
          <option value="Tìm được sản phẩm tốt hơn">Tìm được sản phẩm tốt hơn</option>
          <option value="Không còn nhu cầu">Không còn nhu cầu</option>
          <option value="Khác">Lý do khác</option>
        </select>
      </div>
      <div id="cancelOtherWrap" style="display:none;margin-bottom:14px">
        <textarea id="cancelOtherText" placeholder="Nhập lý do của bạn..." rows="2"
          style="width:100%;padding:9px 12px;border:1.5px solid #E8E1D5;border-radius:4px;font-family:'EB Garamond',serif;font-size:.88rem;background:#FAF6EE;outline:none;resize:none;color:#1A0A0A"></textarea>
      </div>
      <div id="cancelMsg" style="display:none;padding:9px 13px;border-radius:4px;font-size:.84rem;margin-bottom:14px;align-items:center;gap:8px"></div>
      <div style="display:flex;gap:10px">
        <button onclick="closeCancelModal()" id="cancelBtnNo"
          style="flex:1;padding:11px;border:1.5px solid #E8E1D5;background:#fff;border-radius:4px;font-family:'EB Garamond',serif;font-size:.9rem;cursor:pointer;color:#555;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:6px"
          onmouseover="this.style.background='#FAF6EE';this.style.borderColor='#C9A84C'"
          onmouseout="this.style.background='#fff';this.style.borderColor='#E8E1D5'">
          <i class="fas fa-times"></i> Giữ Đơn Hàng
        </button>
        <button onclick="confirmCancel()" id="cancelBtnYes"
          style="flex:1;padding:11px;background:linear-gradient(135deg,#7F1D1D,#DC2626);color:#fff;border:none;border-radius:4px;font-family:'EB Garamond',serif;font-size:.9rem;font-weight:700;cursor:pointer;transition:opacity .2s;display:flex;align-items:center;justify-content:center;gap:6px">
          <i class="fas fa-ban"></i> Xác Nhận Hủy
        </button>
      </div>
    </div>
  </div>
</div>

<script>
let _cancelId = null;

function openCancelModal(orderId, orderCode) {
    _cancelId = orderId;
    document.getElementById('cancelModalCode').textContent = 'Mã đơn: ' + orderCode;
    document.getElementById('cancelReason').value = '';
    document.getElementById('cancelOtherWrap').style.display = 'none';
    document.getElementById('cancelOtherText').value = '';
    const msg = document.getElementById('cancelMsg');
    msg.style.display = 'none';
    const btnYes = document.getElementById('cancelBtnYes');
    const btnNo  = document.getElementById('cancelBtnNo');
    btnYes.disabled = false;
    btnYes.innerHTML = '<i class="fas fa-ban"></i> Xác Nhận Hủy';
    btnNo.style.display = 'flex';
    document.getElementById('cancelModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeCancelModal() {
    document.getElementById('cancelModal').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('cancelReason').addEventListener('change', function() {
    document.getElementById('cancelOtherWrap').style.display = this.value === 'Khác' ? 'block' : 'none';
});
document.getElementById('cancelModal').addEventListener('click', function(e) {
    if (e.target === this) closeCancelModal();
});

async function confirmCancel() {
    if (!_cancelId) return;
    const sel    = document.getElementById('cancelReason').value;
    const other  = document.getElementById('cancelOtherText').value.trim();
    const ly_do  = sel === 'Khác' ? (other || 'Khác') : sel;
    const btnYes = document.getElementById('cancelBtnYes');
    const msg    = document.getElementById('cancelMsg');

    btnYes.disabled = true;
    btnYes.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
    msg.style.display = 'none';

    try {
        const res  = await fetch('public/api.php?action=cancel_order', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'id=' + encodeURIComponent(_cancelId) + '&ly_do=' + encodeURIComponent(ly_do)
        });
        const data = await res.json();
        msg.style.display = 'flex';
        if (data.success) {
            msg.style.cssText = 'display:flex;padding:9px 13px;border-radius:4px;font-size:.84rem;margin-bottom:14px;align-items:center;gap:8px;background:#ECFDF5;border:1px solid #A7F3D0;color:#065F46';
            msg.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message + ' Đang tải lại...';
            document.getElementById('cancelBtnNo').style.display = 'none';
            btnYes.innerHTML = '<i class="fas fa-redo"></i> Tải Lại';
            btnYes.disabled  = false;
            btnYes.onclick   = () => location.reload();
            setTimeout(() => location.reload(), 2000);
        } else {
            msg.style.cssText = 'display:flex;padding:9px 13px;border-radius:4px;font-size:.84rem;margin-bottom:14px;align-items:center;gap:8px;background:#FEF2F2;border:1px solid #FECACA;color:#991B1B';
            msg.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
            btnYes.disabled = false;
            btnYes.innerHTML = '<i class="fas fa-ban"></i> Xác Nhận Hủy';
        }
    } catch(e) {
        msg.style.cssText = 'display:flex;padding:9px 13px;border-radius:4px;font-size:.84rem;margin-bottom:14px;align-items:center;gap:8px;background:#FEF2F2;border:1px solid #FECACA;color:#991B1B';
        msg.innerHTML = '<i class="fas fa-exclamation-circle"></i> Lỗi kết nối. Vui lòng thử lại.';
        btnYes.disabled = false;
        btnYes.innerHTML = '<i class="fas fa-ban"></i> Xác Nhận Hủy';
    }
}

function filterOrders(status, btn) {
    document.querySelectorAll('#orderFilter .of-btn').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('#orderTable tbody tr').forEach(tr=>{
        tr.style.display = (status==='all' || tr.dataset.status===status) ? '' : 'none';
    });
}
<?php if ($new_order && !$view_order): ?>
document.addEventListener('DOMContentLoaded',()=>{
    const rows = document.querySelectorAll('#orderTable tbody tr');
    if (rows.length) rows[0].scrollIntoView({behavior:'smooth',block:'center'});
});
<?php endif; ?>
</script>
</body>
</html>