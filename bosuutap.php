<?php
session_start();
include 'config/db.php';
include 'resources/views/layouts/header.php';

// =============================================
// LẤY DANH MỤC CHA + CON để hiện menu lọc
// =============================================
$danh_muc_cha = $conn->query("
    SELECT * FROM danh_muc WHERE id_cha IS NULL AND trang_thai = 1 ORDER BY thu_tu ASC
");

$danh_muc_con = [];
while ($cha = $danh_muc_cha->fetch_assoc()) {
    $id_cha = $cha['id'];
    $result_con = $conn->query("
        SELECT * FROM danh_muc WHERE id_cha = $id_cha AND trang_thai = 1 ORDER BY thu_tu ASC
    ");
    $danh_muc_con[$id_cha] = [
        'ten'  => $cha['ten_danh_muc'],
        'slug' => $cha['slug'],
        'con'  => []
    ];
    while ($con = $result_con->fetch_assoc()) {
        $danh_muc_con[$id_cha]['con'][] = $con;
    }
}

// =============================================
// LỌC SẢN PHẨM THEO DANH MỤC / TÌM KIẾM
// =============================================
$filter_dm   = isset($_GET['danh_muc']) ? (int)$_GET['danh_muc'] : 0;
$filter_slug = $_GET['slug'] ?? '';
$search      = trim($_GET['search'] ?? '');
$sort        = $_GET['sort'] ?? 'moi_nhat';
$page        = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 12;
$offset      = ($page - 1) * $per_page;

// Tìm id_danh_muc từ slug nếu có
if ($filter_slug && !$filter_dm) {
    $r = $conn->query("SELECT id FROM danh_muc WHERE slug = '" . $conn->real_escape_string($filter_slug) . "' LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) {
        $filter_dm = (int)$row['id'];
    }
}

// Nếu lọc theo danh mục cha → lấy tất cả danh mục con của nó
$ids_loc = [];
if ($filter_dm) {
    $check_cha = $conn->query("SELECT id_cha FROM danh_muc WHERE id = $filter_dm")->fetch_assoc();
    if ($check_cha && $check_cha['id_cha'] === null) {
        // Là danh mục cha → lấy toàn bộ con
        $rs_con = $conn->query("SELECT id FROM danh_muc WHERE id_cha = $filter_dm");
        while ($c = $rs_con->fetch_assoc()) $ids_loc[] = $c['id'];
    } else {
        // Là danh mục con
        $ids_loc[] = $filter_dm;
    }
}

// Xây WHERE
$where = "WHERE sp.trang_thai = 1";
if (!empty($ids_loc)) {
    $ids_str = implode(',', $ids_loc);
    $where .= " AND sp.id_danh_muc IN ($ids_str)";
}
if ($search) {
    $s = $conn->real_escape_string($search);
    $where .= " AND sp.ten_vi LIKE '%$s%'";
}

// ORDER BY
$order = match($sort) {
    'gia_tang'  => 'sp.gia_ban ASC',
    'gia_giam'  => 'sp.gia_ban DESC',
    'ban_chay'  => 'sp.da_ban DESC',
    default     => 'sp.id DESC'
};

// Đếm tổng
$total_rows  = $conn->query("SELECT COUNT(*) as c FROM san_pham sp $where")->fetch_assoc()['c'];
$total_pages = ceil($total_rows / $per_page);

// Lấy sản phẩm
$products = $conn->query("
    SELECT sp.*, dm.ten_danh_muc, dm.slug as dm_slug,
           dm_cha.ten_danh_muc as ten_danh_muc_cha
    FROM san_pham sp
    LEFT JOIN danh_muc dm ON sp.id_danh_muc = dm.id
    LEFT JOIN danh_muc dm_cha ON dm.id_cha = dm_cha.id
    $where
    ORDER BY $order
    LIMIT $per_page OFFSET $offset
");

// Tên danh mục đang lọc (để hiển thị tiêu đề)
$ten_filter = 'Tất Cả Sản Phẩm';
if ($filter_dm) {
    $r_name = $conn->query("SELECT ten_danh_muc FROM danh_muc WHERE id = $filter_dm")->fetch_assoc();
    if ($r_name) $ten_filter = $r_name['ten_danh_muc'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($ten_filter) ?> — Vân Y Các</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=EB+Garamond:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/bosuutap.css">
</head>
<body>

<!-- ===== HERO NHỎ ===== -->
<div class="bst-hero">
    <div class="bst-hero-overlay"></div>
    <div class="bst-hero-content">
        <p class="bst-hero-sub">Vân Y Các · Tinh Hoa Di Sản</p>
        <h1 class="bst-hero-title"><?= htmlspecialchars($ten_filter) ?></h1>
        <p class="bst-hero-count"><?= number_format($total_rows) ?> sản phẩm</p>
    </div>
</div>

<div class="container-fluid bst-main">
    <div class="row g-0">

        <!-- ===== SIDEBAR LỌC ===== -->
        <aside class="col-lg-2 col-md-3 bst-sidebar">
            <div class="sidebar-sticky">

                <!-- Tìm kiếm -->
                <div class="sidebar-section">
                    <form method="GET" class="search-form-sidebar">
                        <input type="hidden" name="danh_muc" value="<?= $filter_dm ?>">
                        <div class="search-wrap">
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                                   placeholder="Tìm sản phẩm..." class="search-input-sidebar">
                            <button type="submit" class="search-btn-sidebar"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>

                <!-- Danh mục -->
                <div class="sidebar-section">
                    <h6 class="sidebar-title">Danh Mục</h6>

                    <a href="bosuutap.php" class="sidebar-link <?= !$filter_dm ? 'active' : '' ?>">
                        <i class="fas fa-th me-2"></i>Tất Cả Sản Phẩm
                        <span class="sidebar-count"><?= $conn->query("SELECT COUNT(*) as c FROM san_pham WHERE trang_thai=1")->fetch_assoc()['c'] ?></span>
                    </a>

                    <?php foreach ($danh_muc_con as $id_cha => $cha): ?>
                    <div class="sidebar-group">
                        <a href="bosuutap.php?danh_muc=<?= $id_cha ?>"
                           class="sidebar-group-title <?= $filter_dm == $id_cha ? 'active' : '' ?>">
                            <i class="fas fa-chevron-right me-2"></i><?= htmlspecialchars($cha['ten']) ?>
                        </a>
                        <?php foreach ($cha['con'] as $con):
                            $cnt = $conn->query("SELECT COUNT(*) as c FROM san_pham WHERE id_danh_muc={$con['id']} AND trang_thai=1")->fetch_assoc()['c'];
                        ?>
                        <a href="bosuutap.php?danh_muc=<?= $con['id'] ?>"
                           class="sidebar-link sidebar-link-sub <?= $filter_dm == $con['id'] ? 'active' : '' ?>">
                            <?= htmlspecialchars($con['ten_danh_muc']) ?>
                            <span class="sidebar-count"><?= $cnt ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Lọc giá -->
                <div class="sidebar-section">
                    <h6 class="sidebar-title">Khoảng Giá</h6>
                    <?php
                    $price_ranges = [
                        ['label' => 'Dưới 1 triệu',        'min' => 0,       'max' => 1000000],
                        ['label' => '1 – 2 triệu',          'min' => 1000000, 'max' => 2000000],
                        ['label' => '2 – 3 triệu',          'min' => 2000000, 'max' => 3000000],
                        ['label' => '3 – 5 triệu',          'min' => 3000000, 'max' => 5000000],
                        ['label' => 'Trên 5 triệu',         'min' => 5000000, 'max' => 99999999],
                    ];
                    $price_min = isset($_GET['price_min']) ? (int)$_GET['price_min'] : -1;
                    foreach ($price_ranges as $pr):
                    ?>
                    <a href="bosuutap.php?danh_muc=<?= $filter_dm ?>&price_min=<?= $pr['min'] ?>&price_max=<?= $pr['max'] ?>"
                       class="sidebar-link <?= $price_min == $pr['min'] ? 'active' : '' ?>">
                        <?= $pr['label'] ?>
                    </a>
                    <?php endforeach; ?>
                </div>

            </div>
        </aside>

        <!-- ===== CONTENT CHÍNH ===== -->
        <main class="col-lg-10 col-md-9 bst-content">

            <!-- Toolbar sort -->
            <div class="bst-toolbar">
                <div class="bst-breadcrumb">
                    <a href="index.php">Trang chủ</a>
                    <span class="sep">/</span>
                    <span><?= htmlspecialchars($ten_filter) ?></span>
                </div>
                <div class="bst-sort">
                    <label class="sort-label">Sắp xếp:</label>
                    <select class="sort-select" onchange="window.location.href=this.value">
                        <?php
                        $base = "bosuutap.php?danh_muc=$filter_dm&search=" . urlencode($search);
                        $sorts = ['moi_nhat' => 'Mới Nhất', 'ban_chay' => 'Bán Chạy', 'gia_tang' => 'Giá Tăng Dần', 'gia_giam' => 'Giá Giảm Dần'];
                        foreach ($sorts as $val => $label):
                        ?>
                        <option value="<?= $base ?>&sort=<?= $val ?>" <?= $sort === $val ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Grid sản phẩm -->
            <?php if ($products && $products->num_rows > 0): ?>
            <div class="product-grid">
                <?php $idx = 0; while ($item = $products->fetch_assoc()): $idx++; ?>
                <div class="product-card" style="animation-delay: <?= ($idx % 4) * 0.1 ?>s">
                    <div class="product-img-wrap">
                        <?php if ($item['noi_bat']): ?>
                        <div class="product-badge-hot">Nổi bật</div>
                        <?php endif; ?>
                        <?php if ($item['gia_goc'] && $item['gia_goc'] > $item['gia_ban']): ?>
                        <div class="product-badge-sale">
                            -<?= round(($item['gia_goc'] - $item['gia_ban']) / $item['gia_goc'] * 100) ?>%
                        </div>
                        <?php endif; ?>

                        <img src="image/<?= htmlspecialchars($item['duong_dan'] ?? 'no-image.jpg') ?>"
                             onerror="this.src='https://via.placeholder.com/400x500?text=Vân+Y+Các'"
                             alt="<?= htmlspecialchars($item['ten_vi']) ?>"
                             class="product-img" loading="lazy">

                        <div class="product-actions">
                            <a href="sanpham.php?id=<?= $item['id'] ?>" class="action-btn">
                                <i class="fas fa-eye me-1"></i> Xem Chi Tiết
                            </a>
                            <a href="giohang.php?action=add&id=<?= $item['id'] ?>" class="action-btn action-btn-cart">
                                <i class="fas fa-shopping-bag me-1"></i> Thêm Giỏ
                            </a>
                        </div>
                    </div>
                    <div class="product-info">
                        <p class="product-category">
                            <?= htmlspecialchars($item['ten_danh_muc_cha'] ?? '') ?>
                            <?php if ($item['ten_danh_muc_cha']): ?> · <?php endif; ?>
                            <?= htmlspecialchars($item['ten_danh_muc'] ?? '') ?>
                        </p>
                        <h3 class="product-name">
                            <a href="sanpham.php?id=<?= $item['id'] ?>"><?= htmlspecialchars($item['ten_vi']) ?></a>
                        </h3>
                        <?php if ($item['mo_ta_ngan']): ?>
                        <p class="product-desc"><?= htmlspecialchars($item['mo_ta_ngan']) ?></p>
                        <?php endif; ?>
                        <div class="product-price-row">
                            <span class="price-main"><?= number_format($item['gia_ban'], 0, ',', '.') ?> ₫</span>
                            <?php if ($item['gia_goc'] && $item['gia_goc'] > $item['gia_ban']): ?>
                            <span class="price-old"><?= number_format($item['gia_goc'], 0, ',', '.') ?> ₫</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($item['so_luong_ton'] <= 0): ?>
                        <p class="out-of-stock">Hết hàng</p>
                        <?php elseif ($item['so_luong_ton'] <= 5): ?>
                        <p class="low-stock">Còn <?= $item['so_luong_ton'] ?> sản phẩm</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Phân trang -->
            <?php if ($total_pages > 1): ?>
            <div class="bst-pagination">
                <?php if ($page > 1): ?>
                <a href="?danh_muc=<?= $filter_dm ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&page=<?= $page-1 ?>" class="page-btn">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?danh_muc=<?= $filter_dm ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&page=<?= $i ?>"
                   class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                <a href="?danh_muc=<?= $filter_dm ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&page=<?= $page+1 ?>" class="page-btn">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="empty-result">
                <i class="fas fa-search"></i>
                <p>Không tìm thấy sản phẩm nào.</p>
                <a href="bosuutap.php" class="btn-back-all">Xem tất cả sản phẩm</a>
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php include 'resources/views/layouts/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>