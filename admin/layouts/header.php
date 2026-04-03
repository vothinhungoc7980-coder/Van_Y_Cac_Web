<?php
require_once __DIR__ . '/../config/auth.php';
requireAdmin();
if (!isset($conn)) require_once __DIR__ . '/../config/db.php';

$depth = $depth ?? 1;
$base  = str_repeat('../', $depth);
$cnt_don = (int)($conn->query("SELECT COUNT(*) c FROM don_hang WHERE trang_thai_dh='Chờ xác nhận'")->fetch_assoc()['c'] ?? 0);

// SỬA: Đếm số đánh giá "Chưa trả lời" (thay vì chờ duyệt)
$cnt_dg  = (int)($conn->query("SELECT COUNT(*) c FROM danh_gia WHERE (phan_hoi_admin IS NULL OR phan_hoi_admin = '')")->fetch_assoc()['c'] ?? 0);

// THÊM: Đếm số sản phẩm đã hết hàng trong kho
$cnt_hethang = (int)($conn->query("SELECT COUNT(*) c FROM san_pham WHERE so_luong_ton <= 0 AND trang_thai=1")->fetch_assoc()['c'] ?? 0);

// Cảnh báo tài khoản hủy đơn >= 3 lần (Bom hàng)
$cnt_warn = (int)($conn->query("
    SELECT COUNT(DISTINCT id_khach_hang) c FROM (
        SELECT id_khach_hang, COUNT(*) cnt FROM don_hang
     WHERE trang_thai_dh='Đã hủy' AND id_khach_hang IS NOT NULL AND (ghi_chu IS NULL OR ghi_chu NOT LIKE '%[Admin hủy]%')
        GROUP BY id_khach_hang HAVING cnt >= 3
    ) t")->fetch_assoc()['c'] ?? 0);
// Đếm số lượng khách hàng đang có tin nhắn chưa đọc
$cnt_chat = (int)($conn->query("SELECT COUNT(DISTINCT id_khach_hang) c FROM tin_nhan WHERE nguoi_gui='khach' AND da_doc=0")->fetch_assoc()['c'] ?? 0);
// Cộng tổng 5 loại thông báo
$cnt_all = $cnt_don + $cnt_dg + $cnt_warn + $cnt_hethang +$cnt_chat;
$admin_name = htmlspecialchars($_SESSION['ho_ten'] ?? 'Admin');
$admin_init = mb_strtoupper(mb_substr($_SESSION['ho_ten'] ?? 'A', 0, 1));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($page_title ?? 'Admin') ?> — Vân Y Các</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=EB+Garamond:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root{--cr:#8B0000;--cr2:#5C0000;--gold:#C9A84C;--sw:250px;--th:60px;
  --bg:#F7F2EC;--white:#fff;--bd:#E8E1D5;--bd2:#F0EAE0;
  --text:#1A0A0A;--mu:#7A6A6A;
  --fd:'Cormorant Garamond',Georgia,serif;--fb:'EB Garamond',Georgia,serif;
  --sh:0 1px 4px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.05);}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body.admin-body{font-family:var(--fb);background:var(--bg);color:var(--text);display:flex;min-height:100vh;font-size:15px;line-height:1.55}
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-thumb{background:rgba(139,0,0,.2);border-radius:3px}

/* SIDEBAR */
.sidebar{width:var(--sw);background:linear-gradient(180deg,#0D0202,#1A0505 40%,#200808 80%,#150303);min-height:100vh;position:fixed;top:0;left:0;z-index:200;display:flex;flex-direction:column;transition:transform .3s ease,width .3s ease;border-right:1px solid rgba(201,168,76,.08);box-shadow:3px 0 16px rgba(0,0,0,.25)}
.sidebar.collapsed{width:60px}
.sidebar.collapsed .sb-txt{display:none}
.sidebar.collapsed .sb-brand{justify-content:center;padding:16px 0}
.sidebar.collapsed .sb-user{justify-content:center;padding:12px 0}
.sidebar.collapsed .sb-link{justify-content:center;padding:12px 0}
.sidebar.collapsed .sb-section{display:none}
.sb-brand{padding:18px 16px 14px;border-bottom:1px solid rgba(201,168,76,.12);display:flex;align-items:center;gap:10px}
.sb-brand-ico{width:36px;height:36px;background:linear-gradient(135deg,var(--gold),var(--cr));border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1rem;flex-shrink:0}
.sb-brand-name{font-family:var(--fd);font-size:1.05rem;font-weight:700;color:var(--gold)}
.sb-brand-tag{font-size:.62rem;color:rgba(201,168,76,.5);letter-spacing:1.5px;text-transform:uppercase}
.sb-user{padding:12px 16px;border-bottom:1px solid rgba(255,255,255,.05);display:flex;align-items:center;gap:10px}
.sb-ava{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--cr));display:flex;align-items:center;justify-content:center;font-family:var(--fd);font-weight:700;font-size:1rem;color:#fff;flex-shrink:0}
.sb-user-name{font-family:var(--fb);font-size:.85rem;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px}
.sb-user-role{font-size:.67rem;color:var(--gold);opacity:.7;margin-top:1px}
.sb-nav{flex:1;overflow-y:auto;padding:8px 0 16px}
.sb-section{font-size:.6rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.25);padding:10px 18px 4px}
.sb-link{display:flex;align-items:center;gap:10px;padding:9px 18px;color:rgba(255,255,255,.65);text-decoration:none;font-family:var(--fb);font-size:.88rem;transition:all .2s;border-left:3px solid transparent;white-space:nowrap}
.sb-link:hover{color:var(--gold);background:rgba(255,255,255,.04);border-left-color:rgba(201,168,76,.3)}
.sb-link.active{color:var(--gold);background:rgba(201,168,76,.08);border-left-color:var(--gold);font-weight:600}
.sb-link i{width:18px;text-align:center;font-size:.9rem;flex-shrink:0}
.sb-link-logout:hover{color:#ff6b6b!important}
.sb-badge{margin-left:auto;background:var(--cr);color:#fff;font-size:.58rem;font-weight:800;padding:2px 6px;border-radius:10px;flex-shrink:0}
.sb-badge.green{background:#059669}

/* MAIN */
.admin-main{flex:1;margin-left:var(--sw);min-height:100vh;display:flex;flex-direction:column;transition:margin-left .3s ease}
.admin-main.expanded{margin-left:60px}
.topbar{height:var(--th);background:var(--white);border-bottom:1px solid var(--bd);display:flex;align-items:center;justify-content:space-between;padding:0 20px;position:sticky;top:0;z-index:100;box-shadow:0 1px 8px rgba(0,0,0,.05)}
.topbar-l{display:flex;align-items:center;gap:12px}
.tb-toggle{width:34px;height:34px;border:none;background:transparent;cursor:pointer;color:var(--mu);display:flex;align-items:center;justify-content:center;border-radius:6px;font-size:1rem;transition:all .2s}
.tb-toggle:hover{background:var(--bd2);color:var(--cr)}
.tb-title{font-family:var(--fd);font-size:1.15rem;font-weight:700;color:var(--cr2)}
.topbar-r{display:flex;align-items:center;gap:10px}

/* Bell + dropdown */
.bell-wrap{position:relative}
.tb-bell{width:34px;height:34px;border:none;background:transparent;cursor:pointer;color:var(--mu);display:flex;align-items:center;justify-content:center;border-radius:6px;font-size:.95rem;transition:all .2s;position:relative}
.tb-bell:hover{background:var(--bd2);color:var(--cr)}
.tb-bell-dot{position:absolute;top:4px;right:4px;min-width:16px;height:16px;background:var(--cr);color:#fff;border-radius:8px;font-size:.55rem;font-weight:800;display:flex;align-items:center;justify-content:center;border:2px solid #fff;padding:0 3px}
.bell-drop{position:absolute;top:calc(100% + 8px);right:0;width:300px;background:#fff;border:1px solid var(--bd);border-radius:8px;box-shadow:0 8px 28px rgba(0,0,0,.12);display:none;z-index:300}
.bell-drop.open{display:block}
.bell-hd{padding:12px 16px;border-bottom:1px solid var(--bd);font-family:var(--fd);font-size:.92rem;font-weight:700;color:var(--cr2);display:flex;align-items:center;justify-content:space-between}
.bell-item{display:flex;align-items:flex-start;gap:10px;padding:11px 16px;border-bottom:1px solid var(--bd2);text-decoration:none;color:var(--text);transition:background .15s}
.bell-item:last-child{border-bottom:none}
.bell-item:hover{background:var(--bg)}
.bell-ico{width:30px;height:30px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0}
.bell-ico.red{background:#FEE2E2;color:var(--cr)}
.bell-ico.green{background:#D1FAE5;color:#059669}
.bell-ico.orange{background:#FEF3C7;color:#92400E}
.bell-txt{font-size:.82rem;font-weight:600;line-height:1.3}
.bell-sub{font-size:.72rem;color:var(--mu);margin-top:2px}
.tb-user{display:flex;align-items:center;gap:8px;font-size:.85rem;font-family:var(--fb)}
.tb-user-ava{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--cr));color:#fff;display:flex;align-items:center;justify-content:center;font-family:var(--fd);font-weight:700;font-size:.85rem}

/* CONTENT */
.cwrap{padding:20px;flex:1}
.card{background:var(--white);border:1px solid var(--bd);border-radius:6px;margin-bottom:18px;box-shadow:var(--sh)}
.card-hd{padding:14px 18px;border-bottom:1px solid var(--bd2);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}
.card-title{font-family:var(--fd);font-size:1rem;font-weight:700;color:var(--cr2)}
.card-sub{font-size:.72rem;color:var(--mu);margin-top:1px}
.card-bd{padding:16px 18px}
.card-bd-flush{overflow-x:auto}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:18px}
.scard{background:var(--white);border:1px solid var(--bd);border-radius:6px;padding:18px;display:flex;align-items:center;gap:14px;box-shadow:var(--sh);transition:transform .2s}
.scard:hover{transform:translateY(-2px)}
.sc-ico{width:46px;height:46px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.15rem;flex-shrink:0}
.i-gold{background:#FEF3C7;color:#B45309}.i-red{background:#FEE2E2;color:var(--cr)}.i-blue{background:#DBEAFE;color:#1D4ED8}.i-green{background:#D1FAE5;color:#047857}
.c-gold{border-top:3px solid var(--gold)}.c-red{border-top:3px solid var(--cr)}.c-blue{border-top:3px solid #3B82F6}.c-green{border-top:3px solid #10B981}
.sc-val{font-family:var(--fd);font-size:1.6rem;font-weight:700;color:var(--text);line-height:1}
.sc-lbl{font-size:.75rem;color:var(--mu);margin-top:4px}
.sc-sub{font-size:.7rem;margin-top:3px;display:flex;align-items:center;gap:4px}
.sc-sub.dn{color:var(--cr)}.sc-sub.up{color:#059669}
.g7-5{display:grid;grid-template-columns:1.4fr 1fr;gap:16px;margin-bottom:18px}
.g5-7{display:grid;grid-template-columns:1fr 1.4fr;gap:16px;margin-bottom:18px}
.g4-8{display:grid;grid-template-columns:2fr 3fr;gap:16px}
.dtable{width:100%;border-collapse:collapse;font-size:.83rem;font-family:var(--fb)}
.dtable th{background:#FAF6EE;padding:9px 14px;text-align:left;font-size:.68rem;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--cr2);border-bottom:2px solid var(--bd);white-space:nowrap}
.dtable td{padding:10px 14px;border-bottom:1px solid var(--bd2);vertical-align:middle}
.dtable tr:last-child td{border-bottom:none}
.dtable tr:hover td{background:#FDFAF5}
.tbl-thumb{width:38px;height:46px;object-fit:cover;border-radius:4px;border:1px solid var(--bd);flex-shrink:0}
.tbl-img-placeholder{width:38px;height:46px;background:var(--bd2);border-radius:4px;display:flex;align-items:center;justify-content:center;color:#ccc;font-size:.8rem}
.text-xs{font-size:.72rem}.text-sm{font-size:.82rem}.text-muted{color:var(--mu)}
.badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:20px;font-size:.64rem;font-weight:700;letter-spacing:.3px;white-space:nowrap}
.b-warning{background:#FEF3C7;color:#92400E}.b-info{background:#DBEAFE;color:#1E40AF}.b-purple{background:#EDE9FE;color:#5B21B6}.b-success{background:#D1FAE5;color:#065F46}.b-danger{background:#FEE2E2;color:#991B1B}.b-gray{background:#F3F4F6;color:#374151}
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:4px;font-family:var(--fb);font-size:.82rem;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:all .2s;white-space:nowrap}
.btn-primary{background:var(--cr);color:var(--gold)}.btn-primary:hover{background:var(--cr2);color:var(--gold)}
.btn-secondary{background:var(--bd2);color:var(--text)}.btn-secondary:hover{background:var(--bd);color:var(--cr)}
.btn-success{background:#059669;color:#fff}.btn-danger{background:#DC2626;color:#fff}.btn-warning{background:#D97706;color:#fff}
.btn-sm{padding:5px 11px;font-size:.76rem}
.ibtn{width:28px;height:28px;border-radius:4px;display:inline-flex;align-items:center;justify-content:center;font-size:.78rem;cursor:pointer;border:none;text-decoration:none;transition:all .2s}
.ib-view{background:#EFF6FF;color:#1D4ED8}.ib-view:hover{background:#DBEAFE}
.ib-edit{background:#FEF3C7;color:#92400E}.ib-edit:hover{background:#FDE68A}
.ib-del{background:#FEE2E2;color:#991B1B}.ib-del:hover{background:#FECACA}
.ib-ok{background:#D1FAE5;color:#065F46}.ib-ok:hover{background:#A7F3D0}
.ib-block{background:#FEE2E2;color:#991B1B}.ib-block:hover{background:#FECACA}
.alert{padding:11px 16px;border-radius:6px;margin-bottom:16px;display:flex;align-items:center;gap:10px;font-size:.85rem;font-family:var(--fb)}
.al-warning{background:#FFFBEB;border:1px solid #FCD34D;color:#92400E}
.al-success{background:#ECFDF5;border:1px solid #A7F3D0;color:#065F46}
.al-danger{background:#FEF2F2;border:1px solid #FECACA;color:#991B1B}
.al-info{background:#EFF6FF;border:1px solid #BAE6FD;color:#0C4A6E}
.fl{display:block;font-size:.7rem;font-weight:700;letter-spacing:.7px;text-transform:uppercase;color:var(--cr2);margin-bottom:5px;font-family:var(--fb)}
.fc,.fctrl{width:100%;padding:9px 12px;border:1.5px solid var(--bd);border-radius:4px;font-family:var(--fb);font-size:.88rem;background:#FAF6EE;outline:none;transition:border-color .2s;color:var(--text)}
.fc:focus,.fctrl:focus{border-color:var(--cr);background:#fff}
.fg,.fgroup{margin-bottom:14px}
.frow{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.flabel{display:block;font-size:.7rem;font-weight:700;letter-spacing:.7px;text-transform:uppercase;color:var(--cr2);margin-bottom:5px}
.req{color:var(--cr)}
.fbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:16px}
.fbar-search{flex:1;min-width:200px}
.finput-wrap{position:relative}
.finput-ico{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--mu);font-size:.8rem}
.finput-wrap .fctrl{padding-left:32px}
.chart-wrap{position:relative;height:220px}
.chart-sm{height:180px}
.modal-bd{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:500;align-items:center;justify-content:center}
.modal-bd.open{display:flex}
.modal-box{background:#fff;border-radius:8px;width:90%;max-width:520px;box-shadow:0 8px 32px rgba(0,0,0,.15);overflow:hidden}
.modal-hd{padding:14px 18px;border-bottom:1px solid var(--bd);display:flex;align-items:center;justify-content:space-between}
.modal-title{font-family:var(--fd);font-size:1rem;font-weight:700;color:var(--cr2)}
.modal-close{background:none;border:none;cursor:pointer;color:var(--mu);font-size:1rem;padding:4px}
.modal-body{padding:18px}
.modal-foot{padding:14px 18px;border-top:1px solid var(--bd);display:flex;gap:10px;justify-content:flex-end}
.tab-nav,.tab-bar{display:flex;gap:4px;border-bottom:2px solid var(--bd);margin-bottom:16px;flex-wrap:wrap}
.tab-btn{padding:8px 16px;border:none;background:transparent;font-family:var(--fb);font-size:.85rem;color:var(--mu);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .2s}
.tab-btn.active{color:var(--cr);border-bottom-color:var(--cr);font-weight:700}
.pagi{display:flex;gap:6px;justify-content:center;margin-top:16px;flex-wrap:wrap}
.pagi-link{width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:1px solid var(--bd);border-radius:4px;text-decoration:none;color:var(--text);font-size:.82rem;transition:all .2s}
.pagi-link:hover,.pagi-link.active{background:var(--cr);color:var(--gold);border-color:var(--cr)}
.page-actions{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:8px}
/* Admin bar ngoài website */
.admin-bar-outer{position:fixed;top:0;left:0;right:0;height:38px;background:linear-gradient(135deg,#1A0505,#3D0000);z-index:99999;display:flex;align-items:center;justify-content:space-between;padding:0 16px;font-family:'EB Garamond',Georgia,serif;font-size:.82rem}
.admin-bar-outer a{color:var(--gold);text-decoration:none;display:flex;align-items:center;gap:6px;transition:opacity .2s}
.admin-bar-outer a:hover{opacity:.8}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}.sidebar.open{transform:translateX(0)}
  .admin-main{margin-left:0!important}
  .stats-grid{grid-template-columns:1fr 1fr}.g7-5,.g5-7,.g4-8{grid-template-columns:1fr}.frow{grid-template-columns:1fr}
}
@media(max-width:480px){.stats-grid{grid-template-columns:1fr}}
</style>
</head>
<body class="admin-body">

<aside class="sidebar" id="sidebar">
  <div class="sb-brand">
    <div class="sb-brand-ico"><i class="fas fa-fan"></i></div>
    <div class="sb-txt">
      <div class="sb-brand-name">Vân Y Các</div>
      <div class="sb-brand-tag">Admin Panel</div>
    </div>
  </div>
  <div class="sb-user">
    <div class="sb-ava"><?= $admin_init ?></div>
    <div class="sb-txt">
      <div class="sb-user-name"><?= $admin_name ?></div>
      <div class="sb-user-role"><i class="fas fa-shield-alt me-1"></i>Quản trị viên</div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="sb-section">Tổng Quan</div>
    <a href="<?= $base ?>admin/panel.php?page=dashboard" class="sb-link <?= ($active_menu??'')==='dashboard'?'active':'' ?>">
      <i class="fas fa-chart-pie"></i><span class="sb-link-txt sb-txt">Dashboard</span></a>

    <div class="sb-section">Quản Lý</div>
    <a href="<?= $base ?>admin/panel.php?page=san-pham" class="sb-link <?= ($active_menu??'')==='san_pham'?'active':'' ?>">
      <i class="fas fa-tshirt"></i><span class="sb-link-txt sb-txt">Sản Phẩm</span></a>
    <a href="<?= $base ?>admin/panel.php?page=danh-muc" class="sb-link <?= ($active_menu??'')==='danh_muc'?'active':'' ?>">
      <i class="fas fa-layer-group"></i><span class="sb-link-txt sb-txt">Danh Mục</span></a>
    <a href="<?= $base ?>admin/panel.php?page=don-hang" class="sb-link <?= ($active_menu??'')==='don_hang'?'active':'' ?>">
      <i class="fas fa-box"></i><span class="sb-link-txt sb-txt">Đơn Hàng</span>
      <?php if($cnt_don>0):?><span class="sb-badge"><?=$cnt_don?></span><?php endif?></a>
    <a href="<?= $base ?>admin/panel.php?page=khach-hang" class="sb-link <?= ($active_menu??'')==='khachhang'?'active':'' ?>">
      <i class="fas fa-users"></i><span class="sb-link-txt sb-txt">Khách Hàng</span></a>
    <a href="<?= $base ?>admin/panel.php?page=danh-gia" class="sb-link <?= ($active_menu??'')==='danh_gia'?'active':'' ?>">
      <i class="fas fa-star"></i><span class="sb-link-txt sb-txt">Đánh Giá</span>
      <?php if($cnt_dg>0):?><span class="sb-badge green"><?=$cnt_dg?></span><?php endif?></a>
      <a href="<?= $base ?>admin/panel.php?page=chat" class="sb-link <?= ($page??'')==='chat'?'active':'' ?>">
      <i class="fas fa-comments"></i><span class="sb-link-txt sb-txt">Tin Nhắn (Chat)</span>
      <?php if(isset($cnt_chat) && $cnt_chat > 0): ?><span class="sb-badge" style="background:#3B82F6"><?= $cnt_chat ?></span><?php endif; ?>
    </a>

    <div class="sb-section">Báo Cáo</div>
    <a href="<?= $base ?>admin/panel.php?page=doanh-thu" class="sb-link <?= ($active_menu??'')==='doanh_thu'?'active':'' ?>">
      <i class="fas fa-chart-line"></i><span class="sb-link-txt sb-txt">Doanh Thu</span></a>

    <div class="sb-section">Hệ Thống</div>
    <a href="<?= $base ?>index.php" class="sb-link" target="_blank">
      <i class="fas fa-external-link-alt"></i><span class="sb-link-txt sb-txt">Xem Website</span></a>
    <a href="<?= $base ?>logout.php" class="sb-link sb-link-logout" onclick="return confirm('Đăng xuất?')">
      <i class="fas fa-sign-out-alt"></i><span class="sb-link-txt sb-txt">Đăng Xuất</span></a>
  </nav>
</aside>

<div class="admin-main" id="adminMain">
  <header class="topbar">
    <div class="topbar-l">
      <button class="tb-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <div class="tb-title"><?= htmlspecialchars($page_title ?? '') ?></div>
    </div>
    <div class="topbar-r">
      <?php if($cnt_all > 0): ?>
      <div class="bell-wrap">
        <button class="tb-bell" id="bellBtn">
          <i class="fas fa-bell"></i>
          <span class="tb-bell-dot"><?= $cnt_all ?></span>
        </button>
     <div class="bell-drop" id="bellDrop">
          <div class="bell-hd">
            <span><i class="fas fa-bell me-1" style="color:var(--cr)"></i> Thông Báo</span>
            <span style="font-size:.72rem;color:var(--mu)"><?= $cnt_all ?> mới</span>
          </div>

          <?php if($cnt_don > 0): ?>
          <a href="<?= $base ?>admin/panel.php?page=don-hang&tab=cho" class="bell-item">
            <div class="bell-ico" style="background:#DBEAFE;color:#1D4ED8"><i class="fas fa-box"></i></div>
            <div><div class="bell-txt"><?= $cnt_don ?> đơn hàng chờ xác nhận</div>
            <div class="bell-sub">Cần gọi điện và xử lý ngay</div></div>
          </a>
          <?php endif; ?>

          <?php if($cnt_chat > 0): ?>
          <a href="<?= $base ?>admin/panel.php?page=chat" class="bell-item">
            <div class="bell-ico" style="background:#E0E7FF;color:#4338CA"><i class="fas fa-comments"></i></div>
            <div><div class="bell-txt"><?= $cnt_chat ?> khách hàng đang nhắn tin</div>
            <div class="bell-sub">Vào chat ngay để hỗ trợ</div></div>
          </a>
          <?php endif; ?>

          <?php if($cnt_dg > 0): ?>
          <a href="<?= $base ?>admin/panel.php?page=danh-gia&tab=chuatl" class="bell-item">
            <div class="bell-ico green"><i class="fas fa-star"></i></div>
            <div><div class="bell-txt"><?= $cnt_dg ?> đánh giá chưa trả lời</div>
            <div class="bell-sub">Vào phản hồi cảm ơn khách hàng</div></div>
          </a>
          <?php endif; ?>

          <?php if($cnt_hethang > 0): ?>
          <a href="<?= $base ?>admin/panel.php?page=san-pham&tk=hethang" class="bell-item">
            <div class="bell-ico red"><i class="fas fa-exclamation-circle"></i></div>
            <div><div class="bell-txt"><?= $cnt_hethang ?> sản phẩm đã hết hàng</div>
            <div class="bell-sub">Cần cập nhật thêm tồn kho</div></div>
          </a>
          <?php endif; ?>

          <?php if($cnt_warn > 0): ?>
          <a href="<?= $base ?>admin/panel.php?page=khach-hang" class="bell-item">
            <div class="bell-ico orange"><i class="fas fa-user-slash"></i></div>
            <div><div class="bell-txt"><?= $cnt_warn ?> khách hàng bom đơn</div>
            <div class="bell-sub">Hủy ≥ 3 lần, cần xem xét khóa</div></div>
          </a>
          <?php endif; ?>

          <?php if($cnt_all == 0): ?>
          <div style="padding:25px 20px; text-align:center; color:var(--mu); font-size:0.9rem;">
              <i class="fas fa-check-circle" style="font-size:2rem; color:#10B981; margin-bottom:10px; display:block;"></i>
              Tuyệt vời! Không có thông báo nào tồn đọng.
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <div class="tb-user">
        <div class="tb-user-ava"><?= $admin_init ?></div>
        <span class="d-none d-md-inline"><?= $admin_name ?></span>
      </div>
    </div>
  </header>
  <div class="cwrap">