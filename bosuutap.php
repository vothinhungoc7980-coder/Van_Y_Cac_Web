<?php
session_start();
include 'config/db.php';

// =============================================
// LẤY DANH MỤC
// =============================================
$danh_muc_cha = $conn->query("SELECT * FROM danh_muc WHERE id_cha IS NULL AND trang_thai=1 ORDER BY thu_tu ASC");
$danh_muc_con = [];
while ($cha = $danh_muc_cha->fetch_assoc()) {
    $id_cha = $cha['id'];
    $result_con = $conn->query("SELECT * FROM danh_muc WHERE id_cha=$id_cha AND trang_thai=1 ORDER BY thu_tu ASC");
    $danh_muc_con[$id_cha] = ['ten'=>$cha['ten_danh_muc'],'slug'=>$cha['slug'],'con'=>[],'id'=>$id_cha];
    while ($con = $result_con->fetch_assoc()) $danh_muc_con[$id_cha]['con'][] = $con;
}

// =============================================
// FILTER PARAMS
// =============================================
$filter_dm   = isset($_GET['danh_muc']) ? (int)$_GET['danh_muc'] : 0;
$filter_slug = $_GET['slug'] ?? '';
$search      = trim($_GET['search'] ?? '');
$sort        = $_GET['sort'] ?? 'moi_nhat';
$page        = max(1, (int)($_GET['page'] ?? 1));
$gioi_tinh   = $_GET['gt'] ?? ''; // 'nam' | 'nu' | ''
$per_page    = 12;
$offset      = ($page - 1) * $per_page;

// Tìm id từ slug
if ($filter_slug && !$filter_dm) {
    $r = $conn->query("SELECT id FROM danh_muc WHERE slug='".($conn->real_escape_string($filter_slug))."' LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) $filter_dm = (int)$row['id'];
}

// Lấy IDs cần lọc
$ids_loc = [];
if ($filter_dm) {
    $check_cha = $conn->query("SELECT id_cha FROM danh_muc WHERE id=$filter_dm")->fetch_assoc();
    if ($check_cha && $check_cha['id_cha'] === null) {
        $rs_con = $conn->query("SELECT id FROM danh_muc WHERE id_cha=$filter_dm");
        while ($c = $rs_con->fetch_assoc()) $ids_loc[] = $c['id'];
    } else { $ids_loc[] = $filter_dm; }
}

// Lọc theo cột gioi_tinh trực tiếp trên bảng san_pham
$where = "WHERE sp.trang_thai=1";
if (!empty($ids_loc)) $where .= " AND sp.id_danh_muc IN (" . implode(',', $ids_loc) . ")";
if ($gioi_tinh === 'nam') $where .= " AND sp.gioi_tinh = 'Nam'";
elseif ($gioi_tinh === 'nu') $where .= " AND sp.gioi_tinh IN ('Nữ','Unisex')";
if ($search) $where .= " AND sp.ten_vi LIKE '%" . ($conn->real_escape_string($search)) . "%'";

// Price filter
if (isset($_GET['price_min'])) {
    $pmin = (int)$_GET['price_min']; $pmax = (int)$_GET['price_max'];
    $where .= " AND sp.gia_ban BETWEEN $pmin AND $pmax";
}

$order = match($sort) { 'gia_tang'=>'sp.gia_ban ASC','gia_giam'=>'sp.gia_ban DESC','ban_chay'=>'sp.da_ban DESC',default=>'sp.id DESC' };

$total_rows  = $conn->query("SELECT COUNT(*) c FROM san_pham sp $where")->fetch_assoc()['c'];
$total_pages = max(1, ceil($total_rows / $per_page));

$products = $conn->query("
    SELECT sp.*, dm.ten_danh_muc, dm.slug as dm_slug,
           dm_cha.ten_danh_muc as ten_danh_muc_cha, dm_cha.id as id_dm_cha
    FROM san_pham sp
    LEFT JOIN danh_muc dm ON sp.id_danh_muc=dm.id
    LEFT JOIN danh_muc dm_cha ON dm.id_cha=dm_cha.id
    $where ORDER BY $order LIMIT $per_page OFFSET $offset
");

$ten_filter = 'Tất Cả Sản Phẩm';
if ($gioi_tinh === 'nam') $ten_filter = 'Trang Phục Nam';
elseif ($gioi_tinh === 'nu') $ten_filter = 'Trang Phục Nữ';
if ($filter_dm) {
    $r_name = $conn->query("SELECT ten_danh_muc FROM danh_muc WHERE id=$filter_dm")->fetch_assoc();
    if ($r_name) $ten_filter = $r_name['ten_danh_muc'];
}

include 'resources/views/layouts/header.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($ten_filter) ?> — Vân Y Các</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=EB+Garamond:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="public/css/bosuutap.css">
</head>
<style>
/* ================= ROOT ================= */
:root{
    --cr: #8B0000;
    --cr2: #3E2C23;
    --gold: #C9A84C;
}

/* ================= SIDEBAR ================= */
.sidebar-section {
    margin-bottom: 25px;
}

/* ===== TITLE ===== */
.sidebar-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.1rem;
    font-weight: 700;
    letter-spacing: 1px;
    color: var(--cr);
    margin-bottom: 12px;
    text-transform: uppercase;
    border-bottom: 1px solid #E8DCCB;
    padding-bottom: 6px;
}

/* ===== LINK CHUNG ===== */
.sidebar-link {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    font-size: 0.95rem;
    color: #3E2C23;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.25s ease;
}

.sidebar-link:hover {
    background: #F5EFE6;
    color: var(--cr);
    transform: translateX(3px);
}

/* ACTIVE */
.sidebar-link.active {
    background: rgba(139, 0, 0, 0.08);
    color: var(--cr);
    font-weight: 600;
    border-left: 3px solid var(--cr);
}

/* ===== GROUP CHA ===== */
.sidebar-group-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--cr2);
    cursor: pointer;
    padding: 6px 4px;
    transition: all 0.3s;
    user-select: none;
}

.sidebar-group-title:hover {
    color: var(--cr);
}

/* ===== ICON MŨI TÊN ===== */
.transition-icon {
    transition: transform 0.3s ease;
    color: var(--cr);
}

.sidebar-group-title[aria-expanded="false"] .transition-icon {
    transform: rotate(-90deg);
}

/* ===== DANH MỤC CON ===== */
.sidebar-link-sub {
    font-size: 0.9rem;
    color: #5a4a42;
    padding-left: 10px;
}

.sidebar-link-sub:hover {
    color: var(--cr);
}

/* ===== BADGE SỐ ===== */
.sidebar-count {
    background: #F0E8D8;
    color: #8B0000;
    border-radius: 50%;
    width: 22px;
    height: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: bold;
    transition: all 0.2s;
}

.sidebar-link:hover .sidebar-count {
    background: var(--gold);
    color: #fff;
}

/* ===== COLLAPSE ===== */
.sidebar-group {
    margin-bottom: 10px;
}

.collapse .sidebar-link {
    margin-bottom: 4px;
}

/* ===== SEARCH ===== */
.search-input-sidebar {
    width: 100%;
    padding: 8px 12px;
    border-radius: 20px;
    border: 1px solid #ddd;
    font-size: 0.9rem;
    transition: 0.3s;
}

.search-input-sidebar:focus {
    border-color: var(--gold);
    outline: none;
    box-shadow: 0 0 5px rgba(201,168,76,0.4);
}

.search-btn-sidebar {
    background: var(--cr);
    color: #fff;
    border-radius: 50%;
    border: none;
    width: 34px;
    height: 34px;
    transition: 0.3s;
}

.search-btn-sidebar:hover {
    background: var(--gold);
    color: #1A0A0A;
}
</style>
<body>
<div class="bst-hero">
    <div class="bst-hero-overlay"></div>
    <div class="bst-hero-content">
        <p class="bst-hero-sub">Vân Y Các · Tinh Hoa Di Sản</p>
        <h1 class="bst-hero-title"><?= htmlspecialchars($ten_filter) ?></h1>
        <!-- Gender switch pills -->
        <div style="display:flex;gap:8px;justify-content:center;margin-top:14px;flex-wrap:wrap">
            <a href="bosuutap.php" style="padding:7px 20px;border-radius:20px;font-size:.75rem;font-weight:700;text-decoration:none;letter-spacing:1px;transition:all .2s;<?= !$gioi_tinh&&!$filter_dm?'background:#C9A84C;color:#1A0A0A':'background:rgba(255,255,255,.15);color:rgba(255,255,255,.8);border:1px solid rgba(255,255,255,.3)' ?>">Tất Cả</a>
            <a href="bosuutap.php?gt=nu" style="padding:7px 20px;border-radius:20px;font-size:.75rem;font-weight:700;text-decoration:none;letter-spacing:1px;transition:all .2s;<?= $gioi_tinh==='nu'?'background:#8B0050;color:#fff':'background:rgba(255,255,255,.15);color:rgba(255,255,255,.8);border:1px solid rgba(255,255,255,.3)' ?>"><i class="fas fa-venus me-1"></i> Trang Phục Nữ</a>
            <a href="bosuutap.php?gt=nam" style="padding:7px 20px;border-radius:20px;font-size:.75rem;font-weight:700;text-decoration:none;letter-spacing:1px;transition:all .2s;<?= $gioi_tinh==='nam'?'background:#1A3A5C;color:#fff':'background:rgba(255,255,255,.15);color:rgba(255,255,255,.8);border:1px solid rgba(255,255,255,.3)' ?>"><i class="fas fa-mars me-1"></i> Trang Phục Nam</a>
        </div>
        <p class="bst-hero-count"><?= number_format($total_rows) ?> sản phẩm</p>
    </div>
</div>

<div class="container-fluid bst-main">
    <div class="row g-0">
        <!-- SIDEBAR -->
        <aside class="col-lg-2 col-md-3 bst-sidebar">
            <div class="sidebar-sticky">
                <div class="sidebar-section">
                    <form method="GET" class="search-form-sidebar">
                        <input type="hidden" name="danh_muc" value="<?= $filter_dm ?>">
                        <input type="hidden" name="gt" value="<?= $gioi_tinh ?>">
                        <div class="search-wrap">
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Tìm sản phẩm..." class="search-input-sidebar">
                            <button type="submit" class="search-btn-sidebar"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>

                <!-- Lọc giới tính -->
                <div class="sidebar-section">
                    <h6 class="sidebar-title">Giới Tính</h6>
                    <a href="bosuutap.php<?= $filter_dm?"?danh_muc=$filter_dm":'' ?>" class="sidebar-link <?= !$gioi_tinh?'active':'' ?>"><i class="fas fa-users me-2"></i>Tất Cả</a>
                    <a href="bosuutap.php?gt=nu<?= $filter_dm?"&danh_muc=$filter_dm":'' ?>" class="sidebar-link <?= $gioi_tinh==='nu'?'active':'' ?>" style="<?= $gioi_tinh==='nu'?'color:#8B0050':''; ?>"><i class="fas fa-venus me-2" style="color:#8B0050"></i>Trang Phục Nữ</a>
                    <a href="bosuutap.php?gt=nam<?= $filter_dm?"&danh_muc=$filter_dm":'' ?>" class="sidebar-link <?= $gioi_tinh==='nam'?'active':'' ?>" style="<?= $gioi_tinh==='nam'?'color:#1A3A5C':''; ?>"><i class="fas fa-mars me-2" style="color:#1A3A5C"></i>Trang Phục Nam</a>
                </div>

                <!-- Danh mục -->
          <div class="sidebar-section">
                    <h6 class="sidebar-title">Danh Mục</h6>
                    <a href="bosuutap.php<?= $gioi_tinh?"?gt=$gioi_tinh":'' ?>" class="sidebar-link <?= !$filter_dm?'active':'' ?>">
                        <i class="fas fa-th me-2"></i>Tất Cả
                        <span class="sidebar-count"><?= $conn->query("SELECT COUNT(*) c FROM san_pham WHERE trang_thai=1")->fetch_assoc()['c'] ?></span>
                    </a>
                    
                    <?php foreach ($danh_muc_con as $id_cha => $cha): 
                        // Kiểm tra xem khách có đang chọn danh mục con bên trong không để tự động mở menu
                        $is_open = ($filter_dm == $id_cha);
                        foreach ($cha['con'] as $c) { if ($filter_dm == $c['id']) $is_open = true; }
                    ?>
                    <div class="sidebar-group mt-3">
                        <div class="sidebar-group-title d-flex justify-content-between align-items-center mb-1" 
                             data-bs-toggle="collapse" 
                             data-bs-target="#collapseDm<?= $id_cha ?>" 
                             aria-expanded="<?= $is_open ? 'true' : 'false' ?>" 
                             style="font-family:'Cormorant Garamond', serif; font-size:1.2rem; font-weight:700; color:var(--cr2);">
                            
                            <span><i class="fas fa-chevron-down me-2 transition-icon" style="font-size:0.9rem;"></i><?= htmlspecialchars($cha['ten']) ?></span>
                        </div>
                        
                        <div class="collapse <?= $is_open ? 'show' : '' ?> ps-4" id="collapseDm<?= $id_cha ?>">
                            
                            <a href="bosuutap.php?danh_muc=<?= $id_cha ?>" class="sidebar-link sidebar-link-sub <?= $filter_dm==$id_cha?'active':'' ?>" style="font-style:italic; padding: 6px 12px;">
                                Tất cả <?= htmlspecialchars($cha['ten']) ?>
                            </a>
                            
                            <?php foreach ($cha['con'] as $con):
                                $cnt=$conn->query("SELECT COUNT(*) c FROM san_pham WHERE id_danh_muc={$con['id']} AND trang_thai=1")->fetch_assoc()['c'];
                            ?>
                            <a href="bosuutap.php?danh_muc=<?= $con['id'] ?>" class="sidebar-link sidebar-link-sub <?= $filter_dm==$con['id']?'active':'' ?>" style="padding: 6px 12px;">
                                <?= htmlspecialchars($con['ten_danh_muc']) ?>
                                <span class="sidebar-count" style="background:#F0E8D8; color:#8B0000; border-radius:50%; width:22px; height:22px; display:inline-flex; align-items:center; justify-content:center; font-size:0.7rem; font-weight:bold;"><?= $cnt ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Giá -->
                <div class="sidebar-section">
                    <h6 class="sidebar-title">Khoảng Giá</h6>
                    <?php
                    $price_ranges=[['Dưới 1 triệu',0,1000000],['1 – 2 triệu',1000000,2000000],['2 – 3 triệu',2000000,3000000],['3 – 5 triệu',3000000,5000000],['Trên 5 triệu',5000000,99999999]];
                    $pm=isset($_GET['price_min'])?(int)$_GET['price_min']:-1;
                    foreach ($price_ranges as [$lbl,$mn,$mx]): ?>
                    <a href="bosuutap.php?danh_muc=<?= $filter_dm ?>&gt=<?= $gioi_tinh ?>&price_min=<?= $mn ?>&price_max=<?= $mx ?>" class="sidebar-link <?= $pm==$mn?'active':'' ?>"><?= $lbl ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>

        <!-- CONTENT -->
        <main class="col-lg-10 col-md-9 bst-content">
            <div class="bst-toolbar">
                <div class="bst-breadcrumb">
                    <a href="index.php">Trang chủ</a><span class="sep">/</span>
                    <?php if ($gioi_tinh): ?><a href="bosuutap.php?gt=<?= $gioi_tinh ?>"><?= $gioi_tinh==='nam'?'Trang Phục Nam':'Trang Phục Nữ' ?></a><span class="sep">/</span><?php endif; ?>
                    <span><?= htmlspecialchars($ten_filter) ?></span>
                </div>
                <div class="bst-sort">
                    <label class="sort-label">Sắp xếp:</label>
                    <select class="sort-select" onchange="window.location.href=this.value">
                        <?php $base="bosuutap.php?danh_muc=$filter_dm&gt=$gioi_tinh&search=".urlencode($search);
                        foreach (['moi_nhat'=>'Mới Nhất','ban_chay'=>'Bán Chạy','gia_tang'=>'Giá Tăng Dần','gia_giam'=>'Giá Giảm Dần'] as $val=>$lbl): ?>
                        <option value="<?= $base ?>&sort=<?= $val ?>" <?= $sort===$val?'selected':'' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if ($products && $products->num_rows > 0): ?>
            <div class="product-grid">
                <?php $idx=0; while ($item=$products->fetch_assoc()): $idx++; ?>
                <div class="product-card" style="animation-delay:<?= ($idx%4)*.1 ?>s">
                    <div class="product-img-wrap">
                        <?php if ($item['noi_bat']): ?><div class="product-badge-hot">Nổi bật</div><?php endif; ?>
                        <?php if ($item['gia_goc']&&$item['gia_goc']>$item['gia_ban']): ?>
                        <div class="product-badge-sale">-<?= round(($item['gia_goc']-$item['gia_ban'])/$item['gia_goc']*100) ?>%</div>
                        <?php endif; ?>

                        <img src="image/<?= htmlspecialchars($item['duong_dan']??'no-image.jpg') ?>"
                             onerror="this.src='https://placehold.co/400x500?text=Vân+Y+Các'"
                             alt="<?= htmlspecialchars($item['ten_vi']) ?>" class="product-img" loading="lazy">
                        <div class="product-actions">
                            <a href="sanpham.php?id=<?= $item['id'] ?>" class="action-btn"><i class="fas fa-eye me-1"></i>Xem Chi Tiết</a>
                            <?php if (isset($_SESSION['user_id']) || isset($_SESSION['user'])): ?>
                            <a href="sanpham.php?id=<?= $item['id'] ?>" class="action-btn action-btn-cart"><i class="fas fa-shopping-bag me-1"></i>Chọn Mua</a>
                            <?php else: ?>
                            <a href="#" class="action-btn action-btn-cart" data-bs-toggle="modal" data-bs-target="#loginModal"><i class="fas fa-lock me-1"></i>Đăng Nhập</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="product-info">
                        <p class="product-category">
                            <?= htmlspecialchars($item['ten_danh_muc_cha']??'') ?>
                            <?php if ($item['ten_danh_muc_cha']): ?> · <?php endif; ?>
                            <?= htmlspecialchars($item['ten_danh_muc']??'') ?>
                        </p>
                        <h3 class="product-name"><a href="sanpham.php?id=<?= $item['id'] ?>"><?= htmlspecialchars($item['ten_vi']) ?></a></h3>
                        <?php if ($item['mo_ta_ngan']): ?><p class="product-desc"><?= htmlspecialchars($item['mo_ta_ngan']) ?></p><?php endif; ?>
                        <div class="product-price-row">
                            <span class="price-main"><?= number_format($item['gia_ban'],0,',','.') ?> ₫</span>
                            <?php if ($item['gia_goc']&&$item['gia_goc']>$item['gia_ban']): ?>
                            <span class="price-old"><?= number_format($item['gia_goc'],0,',','.') ?> ₫</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($item['so_luong_ton']<=0): ?><p class="out-of-stock">Hết hàng</p>
                        <?php elseif ($item['so_luong_ton']<=5): ?><p class="low-stock">Còn <?= $item['so_luong_ton'] ?> sản phẩm</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <?php if ($total_pages>1): ?>
            <div class="bst-pagination">
                <?php if ($page>1): ?><a href="?danh_muc=<?= $filter_dm ?>&gt=<?= $gioi_tinh ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&page=<?= $page-1 ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
                <?php for ($i=1;$i<=$total_pages;$i++): ?>
                <a href="?danh_muc=<?= $filter_dm ?>&gt=<?= $gioi_tinh ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&page=<?= $i ?>" class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page<$total_pages): ?><a href="?danh_muc=<?= $filter_dm ?>&gt=<?= $gioi_tinh ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&page=<?= $page+1 ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="empty-result">
                <i class="fas fa-search"></i>
                <p>Không tìm thấy sản phẩm nào<?= $gioi_tinh?' cho trang phục '.($gioi_tinh==='nam'?'nam':'nữ'):'' ?>.</p>
                <a href="bosuutap.php" class="btn-back-all">Xem tất cả sản phẩm</a>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'resources/views/layouts/footer.php'; ?>

</body></html>