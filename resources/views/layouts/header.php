<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Đồng bộ session
if (isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id']   = $_SESSION['user']['id'];
    $_SESSION['ho_ten']    = $_SESSION['user']['fullname'];
    $_SESSION['vai_tro']   = $_SESSION['user']['role'];
}

$_is_logged = isset($_SESSION['user_id']) || isset($_SESSION['user']);
$_fullname  = $_SESSION['ho_ten'] ?? $_SESSION['user']['fullname'] ?? 'Tài khoản';
$_is_admin  = ($_SESSION['vai_tro'] ?? $_SESSION['user']['role'] ?? '') === 'Quản trị viên';

// Lấy tên file hiện tại để check "Active"
$current_page = basename($_SERVER['PHP_SELF']);

// Đếm giỏ hàng
$_cart_count = 0;
if ($_is_logged && isset($conn)) {
    $_uid = (int)($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0);
    if ($_uid) {
        $_r = $conn->query("SELECT COALESCE(SUM(so_luong),0) c FROM gio_hang WHERE id_khach_hang=$_uid");
        if ($_r) $_cart_count = (int)$_r->fetch_assoc()['c'];
    }
}

// Lấy danh mục từ DB
$_nav_dm = [];
if (isset($conn)) {
    $_rs = $conn->query("SELECT id, ten_danh_muc, slug, id_cha FROM danh_muc WHERE trang_thai=1 ORDER BY id_cha ASC, thu_tu ASC");
    if ($_rs) {
        while ($_row = $_rs->fetch_assoc()) $_nav_dm[] = $_row;
    }
}

// =============================================================
// LOGIC MENU TỰ ĐỘNG LẤY TỪ DATABASE (CỰC KỲ CHUẨN XÁC)
// =============================================================
$current_dm_id = 0;
if (isset($active_dm_id)) {
    $current_dm_id = $active_dm_id; 
} elseif (isset($_GET['danh_muc'])) {
    $current_dm_id = (int)$_GET['danh_muc']; 
} elseif (isset($_GET['id']) && $current_page !== 'sanpham.php') {
    $current_dm_id = (int)$_GET['id']; 
}

$current_slug  = $_GET['slug'] ?? '';
// --- THÊM ĐOẠN NÀY ĐỂ FIX LỖI ---
$id_co_phuc = null;
$id_truyen_thong = null;
$menu_co_phuc = [];
$menu_truyen_thong = [];
// -------------------------------

// 1. Tìm ID của 2 danh mục cha (Việt Cổ Phục & Truyền Thống)
foreach ($_nav_dm as $dm) {
    if (empty($dm['id_cha'])) {
        if (mb_stripos($dm['ten_danh_muc'], 'Cổ Phục', 0, 'UTF-8') !== false) {
            $id_co_phuc = $dm['id'];
        }
        if (mb_stripos($dm['ten_danh_muc'], 'Truyền Thống', 0, 'UTF-8') !== false) {
            $id_truyen_thong = $dm['id'];
        }
    }
}

// 2. Nhặt các danh mục con đẩy vào đúng mảng của nó
foreach ($_nav_dm as $dm) {
    if ($dm['id_cha'] == $id_co_phuc && $id_co_phuc != null) {
        $menu_co_phuc[] = $dm;
    } elseif ($dm['id_cha'] == $id_truyen_thong && $id_truyen_thong != null) {
        $menu_truyen_thong[] = $dm;
    }
}

// 3. Kiểm tra xem khách hàng có đang đứng ở danh mục con nào không để bật Active sáng lên
$is_vcp_active = false;
$is_tt_active  = false;

foreach ($menu_co_phuc as $con) {
    if ($current_dm_id == $con['id'] || ($current_slug && $current_slug === $con['slug'])) {
        $is_vcp_active = true;
    }
}
foreach ($menu_truyen_thong as $con) {
    if ($current_dm_id == $con['id'] || ($current_slug && $current_slug === $con['slug'])) {
        $is_tt_active = true;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=EB+Garamond:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
    :root {
        --dark-red: #8B0000;
        --gold: #ffc107;
        --font-serif: 'Cormorant Garamond', serif;
        --font-body: 'EB Garamond', serif;
    }

    /* NAVBAR CHUNG */
    .navbar { 
        font-family: var(--font-body);
        box-shadow: 0 2px 15px rgba(0,0,0,0.2); 
        padding: 12px 0;
        background-color: var(--dark-red) !important;
    }
    /* Style cho Logo Image */
.logo-custom {
    height: 50px;           /* Chiều cao cố định để không vỡ menu */
    width: auto;            /* Chiều rộng tự động theo tỉ lệ */
    margin-right: 12px;     /* Khoảng cách với chữ VÂN Y CÁC */
    object-fit: contain;    /* Giữ ảnh nguyên vẹn trong khung */
    display: block;         /* Đảm bảo ảnh được coi là một khối hiển thị */
    
    /* Fix lỗi ảnh bị mờ hoặc ẩn trên một số trình duyệt Mac */
    image-rendering: -webkit-optimize-contrast;
}

/* Căn giữa chữ và logo trên thanh Navbar */
.navbar-brand {
    display: flex;
    align-items: center;
}
   

    /* LINK BÌNH THƯỜNG */
    .nav-link { 
        font-size: 1.05rem; 
        font-weight: 500;
        margin: 0 8px; 
        color: rgba(255,255,255,0.85) !important;
        transition: all 0.3s;
    }

    /* LINK ACTIVE (Trang chủ/Bộ sưu tập/Danh mục cha đang chọn) */
    .nav-link.active-page, .nav-link:hover { 
        color: var(--gold) !important; 
    }

    /* DROPDOWN MENU */
    .custom-dropdown {
        border: none; 
        border-top: 3px solid var(--gold);
        border-radius: 0 0 8px 8px; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        padding: 10px 0; 
        min-width: 230px;
        background: #fff;
    }
    .dropdown-item {
        padding: 10px 20px; 
        font-size: 1rem; 
        color: #333;
        border-bottom: 1px solid #f8f1f1;
        transition: all 0.2s;
    }
    .dropdown-item:last-child { border-bottom: none; }
    
    /* STYLE KHI HOVER HOẶC ĐANG Ở TRANG ĐÓ */
    .dropdown-item:hover, .dropdown-item.active-sub {
        background-color: var(--dark-red);
        color: var(--gold) !important;
        padding-left: 28px;
        font-weight: 600;
    }

    /* SỬA LỖI TAM GIÁC SỔ XUỐNG */
    .dropdown-toggle::after {
        display: inline-block;
        margin-left: 0.455em;
        vertical-align: 0.255em;
        content: "";
        border-top: 0.3em solid;
        border-right: 0.3em solid transparent;
        border-bottom: 0;
        border-left: 0.3em solid transparent;
        transition: transform 0.3s ease;
    }
    .parent-hover:hover .dropdown-toggle::after { transform: rotate(180deg); }

    @media (min-width: 992px) {
        .parent-hover:hover > .dropdown-menu { display: block; margin-top: 0; }
    }

    #cartBadge { font-size: 0.65rem; padding: 4px 6px; }
    </style>
</head>
<body data-logged="<?= $_is_logged ? '1' : '0' ?>">

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container-fluid px-4">
<a class="navbar-brand d-flex align-items-center" href="index.php">
    <img src="image/logo.png" class="logo-custom" alt="Logo">
    <span class="fw-bold text-warning fs-3">VÂN Y CÁC</span>
</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'index.php') ? 'active-page fw-bold' : '' ?>" href="index.php">Trang chủ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'bosuutap.php' && empty($current_dm_id)) ? 'active-page fw-bold' : '' ?>" href="bosuutap.php">Bộ Sưu Tập</a>
                </li>

                <li class="nav-item dropdown parent-hover">
                    <a class="nav-link dropdown-toggle <?= $is_vcp_active ? 'active-page fw-bold' : '' ?>" href="#" role="button">Việt Cổ Phục</a>
                    <ul class="dropdown-menu custom-dropdown">
                        <?php foreach ($menu_co_phuc as $con): 
                            $url = !empty($con['slug']) ? "danh-muc-detail.php?slug=" . $con['slug'] : "danh-muc-detail.php?id=" . $con['id'];
                            $is_sub_active = ($current_dm_id == $con['id'] || ($current_slug && $current_slug == $con['slug']));
                        ?>
                        <li><a class="dropdown-item <?= $is_sub_active ? 'active-sub' : '' ?>" href="<?= $url ?>">
                            <i class="fas fa-minus me-2" style="font-size:.6rem; opacity:0.5"></i><?= htmlspecialchars($con['ten_danh_muc']) ?>
                        </a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>

                <li class="nav-item dropdown parent-hover">
                    <a class="nav-link dropdown-toggle <?= $is_tt_active ? 'active-page fw-bold' : '' ?>" href="#" role="button">Trang Phục Truyền Thống</a>
                    <ul class="dropdown-menu custom-dropdown">
                        <?php foreach ($menu_truyen_thong as $con): 
                            $url2 = !empty($con['slug']) ? "danh-muc-detail.php?slug=" . $con['slug'] : "danh-muc-detail.php?id=" . $con['id'];
                            $is_sub_active2 = ($current_dm_id == $con['id'] || ($current_slug && $current_slug == $con['slug']));
                        ?>
                        <li><a class="dropdown-item <?= $is_sub_active2 ? 'active-sub' : '' ?>" href="<?= $url2 ?>">
                            <i class="fas fa-minus me-2" style="font-size:.6rem; opacity:0.5"></i><?= htmlspecialchars($con['ten_danh_muc']) ?>
                        </a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>

             <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'tuvan.php') ? 'active-page fw-bold' : '' ?>" href="tuvan.php">Tư Vấn AI</a>
                </li>
            </ul>

            <div class="d-flex align-items-center ms-lg-4 gap-3">
                <a href="giohang.php" class="btn btn-outline-warning position-relative rounded-circle p-0"
                   style="width:38px;height:38px;display:flex;align-items:center;justify-content:center">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cartBadge"><?= $_cart_count ?></span>
                </a>

                <?php if ($_is_logged): ?>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_fullname) ?>&background=ffc107&color=8B0000"
                             width="34" height="34" class="rounded-circle me-2 border border-warning">
                        <span class="fw-bold text-warning d-none d-lg-inline"><?= htmlspecialchars($_fullname) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow custom-dropdown">
                        <li><a class="dropdown-item" href="trangcanhan.php"><i class="fas fa-user me-2"></i>Trang Cá nhân</a></li>
                        <li><a class="dropdown-item" href="trangcanhan.php?tab=orders"><i class="fas fa-box me-2"></i>Đơn hàng</a></li>
                        
                        <?php if ($_is_admin): ?>
                        <li><a class="dropdown-item text-primary" href="admin/index.php"><i class="fas fa-cog me-2"></i>Trang Quản trị</a></li>
                        <?php endif; ?>

                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php" onclick="return confirm('Bạn có chắc chắn muốn đăng xuất không?');"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
                    </ul>
                </div>
                <?php else: ?>
                <button class="btn btn-warning fw-bold btn-sm px-3 rounded-pill" data-bs-toggle="modal" data-bs-target="#loginModal">Đăng nhập</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3" style="border-radius:16px;border:none;box-shadow:0 10px 40px rgba(0,0,0,.2)">
            <div class="modal-header" style="border-bottom:none;padding-bottom:0">
                <h5 class="modal-title" style="font-weight:700;font-size:1.5rem;color:#8B0000">Đăng Nhập</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="loginMessage" class="alert d-none text-center"></div>
                <form id="loginForm">
                    <div class="mb-3"><input type="text" name="username" class="form-control" placeholder="Tên đăng nhập" required style="border-radius:8px;padding:12px;background:#f9f9f9;border:1px solid #eee"></div>
                    <div class="mb-3"><input type="password" name="password" class="form-control" placeholder="Mật khẩu" required style="border-radius:8px;padding:12px;background:#f9f9f9;border:1px solid #eee"></div>
                    <button type="submit" class="btn w-100 text-white fw-bold py-3" style="background:linear-gradient(90deg,#8B0000,#B22222);border:none;border-radius:8px">Đăng Nhập</button>
                </form>
                <div class="text-center mt-3">
                    <span class="text-muted small">Chưa có tài khoản? </span>
                    <a href="#" id="btnSwitchToRegister" class="fw-bold text-decoration-none" style="color:#8B0000">Đăng ký ngay</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="registerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3" style="border-radius:16px;border:none;box-shadow:0 10px 40px rgba(0,0,0,.2)">
            <div class="modal-header" style="border-bottom:none;padding-bottom:0">
                <h5 class="modal-title" style="font-weight:700;font-size:1.5rem;color:#8B0000">Đăng Ký Tài Khoản</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="registerMessage" class="alert d-none text-center" style="font-size:.9rem"></div>
                <form id="registerForm">
                    <div class="mb-3"><input type="text" name="fullname" class="form-control" placeholder="Họ và tên" required style="border-radius:8px;padding:12px;background:#f9f9f9;border:1px solid #eee"></div>
                    <div class="mb-3"><input type="text" name="username" class="form-control" placeholder="Tên đăng nhập" required style="border-radius:8px;padding:12px;background:#f9f9f9;border:1px solid #eee"></div>
                    <div class="mb-3"><input type="email" name="email" class="form-control" placeholder="Địa chỉ Email" required style="border-radius:8px;padding:12px;background:#f9f9f9;border:1px solid #eee"></div>
                    <div class="mb-3"><input type="text" name="phone" class="form-control" placeholder="Số điện thoại" pattern="[0-9]{10}" required style="border-radius:8px;padding:12px;background:#f9f9f9;border:1px solid #eee"></div>
                    <div class="mb-3"><input type="password" name="password" class="form-control" placeholder="Mật khẩu" required style="border-radius:8px;padding:12px;background:#f9f9f9;border:1px solid #eee"></div>
                    <div class="mb-3"><input type="password" name="confirm_password" class="form-control" placeholder="Nhập lại mật khẩu" required style="border-radius:8px;padding:12px;background:#f9f9f9;border:1px solid #eee"></div>
                    <button type="submit" class="btn w-100 text-white fw-bold py-3" style="background:linear-gradient(90deg,#8B0000,#B22222);border:none;border-radius:8px">Đăng Ký</button>
                </form>
                <div class="text-center mt-3">
                    <span class="text-muted small">Đã có tài khoản? </span>
                    <a href="#" id="btnSwitchToLogin" class="fw-bold text-decoration-none" style="color:#8B0000">Đăng nhập</a>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<style>
#blossom-container{
    position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
    pointer-events: none; z-index: 99999; overflow: hidden;
}
/* Hoa Mai */
.petal {
    position: absolute; background: radial-gradient(circle, #FFD700 30%, #F59E0B 100%);
    border-radius: 15% 85% 15% 85%;
    opacity: 0.8; filter: drop-shadow(0 0 3px rgba(255,215,0,0.5));
    animation: fall linear forwards, sway ease-in-out infinite alternate;
}
@keyframes fall { 0% { top: -10%; transform: rotate(0deg); } 100% { top: 110%; transform: rotate(720deg); } }
@keyframes sway { 0% { transform: translateX(0px); } 100% { transform: translateX(25px); } }
</style>
<div id="blossom-container"></div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Script tạo Hoa Mai Rơi
    const blossomContainer = document.getElementById('blossom-container');
    if (blossomContainer) {
        function createPetal() {
            const petal = document.createElement('div');
            petal.classList.add('petal');
            const size = Math.random() * 8 + 8;
            petal.style.width = size + 'px';
            petal.style.height = size + 'px';
            petal.style.left = Math.random() * 100 + 'vw';
            const fallDuration = Math.random() * 5 + 7; 
            const swayDuration = Math.random() * 2 + 2; 
            petal.style.animationDuration = `${fallDuration}s, ${swayDuration}s`;
            blossomContainer.appendChild(petal);
            setTimeout(() => { petal.remove(); }, fallDuration * 1000);
        }
        setInterval(createPetal, 600); // 600ms rơi 1 cánh
    }
});
</script>