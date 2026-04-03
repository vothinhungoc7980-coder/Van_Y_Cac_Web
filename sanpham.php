<?php
session_start();
include 'config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: bosuutap.php'); exit; }

$sp = $conn->query("
    SELECT sp.*, dm.ten_danh_muc, dm.slug AS dm_slug,
           dm_cha.ten_danh_muc AS ten_dm_cha, dm_cha.slug AS slug_cha,
           dm_cha.id AS id_dm_cha
    FROM san_pham sp
    LEFT JOIN danh_muc dm ON sp.id_danh_muc = dm.id
    LEFT JOIN danh_muc dm_cha ON dm.id_cha = dm_cha.id
    WHERE sp.id = $id AND sp.trang_thai = 1 LIMIT 1
")->fetch_assoc();

if (!$sp) { header('Location: bosuutap.php'); exit; }
$conn->query("UPDATE san_pham SET luot_xem = luot_xem + 1 WHERE id = $id");

// Xác định giới tính
$gioi_tinh = 'nu';
if (!empty($sp['gioi_tinh']) && $sp['gioi_tinh'] === 'Nam') {
    $gioi_tinh = 'nam';
} elseif (str_contains(mb_strtolower($sp['ten_dm_cha'] ?? ''), 'nam')) {
    $gioi_tinh = 'nam';
}

// Ảnh phụ
$hinh_phu = [];
if (!empty($sp['hinh_anh_phu'])) {
    $dec = json_decode($sp['hinh_anh_phu'], true);
    if (is_array($dec)) $hinh_phu = $dec;
}

$sp_lq = $conn->query("SELECT id, ten_vi, gia_ban, gia_goc, duong_dan, noi_bat FROM san_pham WHERE id_danh_muc={$sp['id_danh_muc']} AND id!=$id AND trang_thai=1 ORDER BY da_ban DESC LIMIT 4");

$dg_list = $conn->query("SELECT dg.*, kh.TaiKhoan FROM danh_gia dg LEFT JOIN khachhang kh ON dg.id_khach_hang = kh.idKhachHang WHERE dg.id_san_pham=$id AND dg.trang_thai IN ('Chưa trả lời', 'Đã trả lời') ORDER BY dg.ngay_tao DESC LIMIT 10");
$dg_tq = $conn->query("SELECT COUNT(*) tong, ROUND(AVG(so_sao),1) avg, SUM(so_sao=5) s5, SUM(so_sao=4) s4, SUM(so_sao=3) s3, SUM(so_sao=2) s2, SUM(so_sao=1) s1 FROM danh_gia WHERE id_san_pham=$id AND trang_thai IN ('Chưa trả lời', 'Đã trả lời')")->fetch_assoc();

$avg_sao  = $dg_tq['avg'] ?? 0;
$tong_dg  = (int)($dg_tq['tong'] ?? 0);
$pct_giam = ($sp['gia_goc'] && $sp['gia_goc'] > $sp['gia_ban']) ? round(($sp['gia_goc'] - $sp['gia_ban']) / $sp['gia_goc'] * 100) : 0;
$da_dang_nhap = isset($_SESSION['user_id']) || isset($_SESSION['user']);
$is_admin     = ($_SESSION['vai_tro'] ?? $_SESSION['user']['vai_tro'] ?? '') === 'Quản trị viên';
// KIỂM TRA XEM KHÁCH ĐÃ MUA VÀ HOÀN THÀNH ĐƠN CHƯA MỚI CHO ĐÁNH GIÁ
$da_mua_hang = false;
if ($da_dang_nhap) {
    $uid_check = (int)($_SESSION['user_id'] ?? $_SESSION['user']['id']);
    $check_mua = $conn->query("SELECT 1 FROM don_hang dh JOIN chi_tiet_don_hang ct ON dh.id = ct.id_don_hang WHERE dh.id_khach_hang = $uid_check AND dh.trang_thai_dh = 'Hoàn thành' AND ct.id_san_pham = $id LIMIT 1");
    if ($check_mua && $check_mua->num_rows > 0) {
        $da_mua_hang = true;
    }
}

$size_nu  = [['S','80–84','62–66','86–90','152–158','45–52'],['M','85–89','67–71','91–95','158–163','52–58'],['L','90–94','72–76','96–100','163–168','58–65'],['XL','95–99','77–81','101–105','168–173','65–72'],['2XL','100–104','82–86','106–110','173–178','72–80']];
$size_nam = [['S','88–92','74–78','88–92','160–165','55–62'],['M','92–96','78–82','92–96','165–170','62–68'],['L','96–100','82–86','96–100','170–175','68–75'],['XL','100–104','86–90','100–104','175–180','75–82'],['2XL','104–108','90–94','104–108','180–185','82–90']];
$sizes = $gioi_tinh === 'nam' ? $size_nam : $size_nu;

// BÁO CHO HEADER BIẾT DANH MỤC HIỆN TẠI ĐỂ SÁNG MÀU VÀNG
$active_dm_id = $sp['id_danh_muc'];

include 'resources/views/layouts/header.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($sp['ten_vi']) ?> — Vân Y Các</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="public/css/chitietsp.css">
<style>
/* ==============================================================
   CÁC ĐOẠN CSS BỔ SUNG ĐỂ SỬA LỖI (GIỮ NGUYÊN GIAO DIỆN GỐC)
============================================================== */

/* 1. SỬA LỖI HEADER BỊ 2 GẠCH VÀ MẤT MŨI TÊN */
.navbar .nav-link::after {
    display: none !important; /* Tắt vạch vàng do chitietsp.css gây ra trên header */
}
.navbar .dropdown-toggle::after {
    display: inline-block !important;
    margin-left: 0.255em;
    vertical-align: 0.255em;
    content: "" !important;
    border-top: 0.3em solid;
    border-right: 0.3em solid transparent;
    border-bottom: 0;
    border-left: 0.3em solid transparent;
    width: auto;
    height: auto;
    background: transparent;
    margin-top: 0;
}

/* 2. CHỈNH LẠI MÀU SẮC TABS (Chi Tiết, Bảng Size, Đánh Giá) */
#myTab .nav-link {
    color: #888 !important; /* Màu xám tối nhạt cho mục chưa chọn để dễ nhìn */
    font-weight: 500;
    background: transparent !important;
    border: none;
    transition: all 0.3s ease;
}
#myTab .nav-link:hover {
    color: #8B0000 !important; /* Hiện đỏ khi di chuột qua */
}
#myTab .nav-link.active {
    color: #8B0000 !important; /* Đỏ đậm nổi bật khi đang ở mục đó */
    font-weight: 700;
    border-bottom: 3px solid #8B0000 !important;
}

/* 3. LỚP PHỦ OVERLAY SẢN PHẨM LIÊN QUAN (Như form bạn yêu cầu) */
.rel-img-wrap {
    position: relative;
    overflow: hidden;
}
.rel-overlay {
    position: absolute;
    inset: 0;
    background: rgba(128,48,48,0.85);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    opacity: 0;
    transition: opacity 0.3s;
}
.rel-card:hover .rel-overlay { opacity: 1; }
.rel-btn {
    padding: 8px 16px;
    width: 75%;
    text-align: center;
    font-family: 'Merriweather', serif;
    font-size: 0.85rem;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s;
    cursor: pointer;
    border-radius: 0;
}
.rel-btn-view { background: #C9A84C; color: #8B0000; border: none; }
.rel-btn-buy { background: transparent; color: #fff; border: 1px solid rgba(255,255,255,0.6); }
.rel-btn-view:hover { background: #fff; color: #8B0000; }
.rel-btn-buy:hover { background: #fff; color: #8B0000; border-color: #fff; }

/* Các CSS cũ của bạn */
.size-btns{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px}
.size-btn{width:46px;height:46px;border:1.5px solid #ddd;border-radius:4px;background:#fff;font-weight:700;font-size:.9rem;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center}
.size-btn:hover{border-color:#dc3545;color:#dc3545}
.size-btn.selected{background:#8B0000;border-color:#8B0000;color:#FFD700}
.size-guide-link{font-size:.75rem;color:#8B0000;text-decoration:underline;cursor:pointer;border:none;background:none;padding:0}
.size-tbl{width:100%;border-collapse:collapse;font-size:.78rem}
.size-tbl th{background:#8B0000;color:#fff;padding:7px 10px;text-align:center;font-weight:600}
.size-tbl td{padding:6px 10px;text-align:center;border-bottom:1px solid #eee}
.size-tbl tr:nth-child(even) td{background:#FFF8F8}
.size-tbl tr.hl td{background:#FFF3CD;font-weight:700}
.selected-size-txt{font-size:.82rem;color:#8B0000;font-weight:700;margin-left:6px}
.qty-row{display:flex;align-items:center;gap:12px;margin-bottom:20px}
.toast-wrap{position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px}
.my-toast{display:flex;align-items:center;gap:10px;background:#1a1a1a;color:#fff;padding:12px 18px;border-radius:8px;font-size:.85rem;min-width:240px;animation:toastIn .3s ease;box-shadow:0 8px 24px rgba(0,0,0,.2)}
.my-toast.ok{border-left:4px solid #22c55e}
.my-toast.warn{border-left:4px solid #f59e0b}
.my-toast.err{border-left:4px solid #ef4444}
@keyframes toastIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
@keyframes toastOut{to{opacity:0;transform:translateX(110%)}}
</style>
</head>
<body data-logged="<?= (isset($_SESSION['user_id']) || isset($_SESSION['user'])) ? '1' : '0' ?>">

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-secondary">Trang chủ</a></li>
            <?php if ($sp['ten_dm_cha']): ?>
            <li class="breadcrumb-item"><a href="bosuutap.php?danh_muc=<?= $sp['id_dm_cha'] ?>" class="text-decoration-none text-secondary"><?= htmlspecialchars($sp['ten_dm_cha']) ?></a></li>
            <?php endif; ?>
            <?php if ($sp['ten_danh_muc']): ?>
            <li class="breadcrumb-item"><a href="bosuutap.php?danh_muc=<?= $sp['id_danh_muc'] ?>" class="text-decoration-none text-secondary"><?= htmlspecialchars($sp['ten_danh_muc']) ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active text-danger fw-bold"><?= htmlspecialchars($sp['ten_vi']) ?></li>
        </ol>
    </nav>
</div>

<div class="container py-3">
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="main-image-box mb-3">
                <?php $img_src = $sp['duong_dan'] ? 'image/' . htmlspecialchars($sp['duong_dan']) : ''; ?>
                <img src="<?= $img_src ?>"
                     id="mainImage"
                     onerror="this.src='https://placehold.co/500x600/FAF6EE/8B0000?text=Vân+Y+Các'"
                     class="img-fluid w-100"
                     alt="<?= htmlspecialchars($sp['ten_vi']) ?>">
            </div>
            <div class="d-flex gap-2 justify-content-center flex-wrap">
                <img onclick="changeImage(this)"
                     src="<?= $img_src ?>"
                     onerror="this.src='https://placehold.co/80x80/FAF6EE/8B0000?text=SP'"
                     class="thumbnail rounded active" alt="">
                <?php foreach ($hinh_phu as $hp): ?>
                <img onclick="changeImage(this)"
                     src="image/<?= htmlspecialchars($hp) ?>"
                     onerror="this.src='https://placehold.co/80x80/FAF6EE/8B0000?text=SP'"
                     class="thumbnail rounded" alt="">
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-md-6">
            <h2 class="fw-bold mb-2" style="color:var(--brand-red)"><?= htmlspecialchars($sp['ten_vi']) ?></h2>
            <div class="mb-3 d-flex align-items-center flex-wrap gap-2">
                <div class="text-warning small">
                    <?php for ($i=1;$i<=5;$i++): ?><i class="fas fa-star<?= $i > round($avg_sao) ? '-o' : '' ?>"></i><?php endfor; ?>
                </div>
                <span class="text-muted small border-start ps-2"><?= $avg_sao ?> (<?= $tong_dg ?> đánh giá)</span>
                <span class="text-muted small border-start ps-2"><i class="fas fa-eye me-1"></i><?= number_format($sp['luot_xem']) ?> lượt xem</span>

                <?php if ($sp['so_luong_ton'] > 0): ?>
                <span class="badge bg-success ms-1">Còn hàng</span>
                <?php else: ?>
                <span class="badge bg-danger ms-1">Hết hàng</span>
                <?php endif; ?>
            </div>

            <div class="price-tag mb-4 p-3 bg-white rounded shadow-sm border-start border-4 border-danger">
                <span class="fs-2 fw-bold text-danger"><?= number_format($sp['gia_ban'],0,',','.') ?>₫</span>
                <?php if ($sp['gia_goc'] && $sp['gia_goc'] > $sp['gia_ban']): ?>
                <span class="text-decoration-line-through text-muted ms-3"><?= number_format($sp['gia_goc'],0,',','.') ?>₫</span>
                <span class="badge bg-warning text-dark ms-2">-<?= $pct_giam ?>%</span>
                <?php endif; ?>
            </div>

            <?php if ($sp['mo_ta_ngan']): ?>
            <div class="alert alert-light border border-warning mb-4">
                <p class="mb-0 small text-secondary"><?= nl2br(htmlspecialchars($sp['mo_ta_ngan'])) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($sp['so_luong_ton'] > 0): ?>
            <div class="mb-3" id="chon-size">
                <label class="fw-bold mb-2 d-block">
                    Kích Thước:
                    <span class="selected-size-txt" id="selectedSizeTxt"></span>
                </label>
                <div class="size-btns" id="sizeBtns">
                    <?php foreach ($sizes as $s): ?>
                    <button type="button" class="size-btn" data-size="<?= $s[0] ?>" onclick="selectSize(this)"><?= $s[0] ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="qty-row">
                <label class="fw-bold">Số Lượng:</label>
                <div class="input-group" style="width:140px">
                    <button class="btn btn-outline-secondary" type="button" onclick="changeQty(-1)">−</button>
                    <input type="number" class="form-control text-center fw-bold" id="qtyInput" value="1" min="1" max="<?= $sp['so_luong_ton'] ?>">
                    <button class="btn btn-outline-secondary" type="button" onclick="changeQty(1)">+</button>
                </div>
                <small class="text-muted">Còn <?= $sp['so_luong_ton'] ?> sp</small>
            </div>
            <?php endif; ?>

<?php if ($is_admin): ?>
            <div class="d-grid mt-4">
                <button disabled class="btn btn-lg" style="background:#f3f4f6; color:#6b7280; border: 1px dashed #9ca3af;">
                    <i class="fas fa-shield-alt me-2"></i>Chế độ Admin: Không thể đặt hàng
                </button>
            </div>
            <?php elseif ($sp['so_luong_ton'] <= 0): ?>
            <div class="d-grid mt-3">
                <button disabled class="btn btn-secondary btn-lg">Hết Hàng</button>
            </div>
            <?php elseif ($da_dang_nhap): ?>
            <div class="d-grid gap-2 d-md-flex mt-4">
                <button type="button" class="btn btn-outline-danger btn-lg flex-grow-1" onclick="handleCart('add')">
                    <i class="fas fa-cart-plus me-2"></i>Thêm Giỏ Hàng
                </button>
                <button type="button" class="btn btn-buy-now btn-lg flex-grow-1 fw-bold" onclick="handleCart('buy')">
                    <i class="fas fa-bolt me-2"></i>Mua Ngay
                </button>
            </div>
            <?php else: ?>
            <div class="d-grid gap-2 d-md-flex mt-4">
                <button type="button" class="btn btn-outline-danger btn-lg flex-grow-1"
                        data-bs-toggle="modal" data-bs-target="#loginModal"
                        onclick="showToast('Vui lòng đăng nhập để mua hàng!','warn')">
                    <i class="fas fa-cart-plus me-2"></i>Thêm Giỏ Hàng
                </button>
                <button type="button" class="btn btn-buy-now btn-lg flex-grow-1 fw-bold"
                        data-bs-toggle="modal" data-bs-target="#loginModal"
                        onclick="showToast('Vui lòng đăng nhập để mua hàng!','warn')">
                    <i class="fas fa-bolt me-2"></i>Mua Ngay
                </button>
            </div>
            <p class="text-center text-muted small mt-2">
                <i class="fas fa-lock me-1"></i>
                <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" class="text-danger fw-bold">Đăng nhập</a>
                hoặc <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal" class="text-danger fw-bold">Đăng ký</a> để mua hàng
            </p>
            <?php endif; ?>

            <?php if ($sp['so_luong_ton'] > 0): ?>
            <div class="mt-4" id="size-guide-wrap">
                <button type="button" class="size-guide-link" onclick="toggleSizeTable()" style="font-size:.82rem;color:#8B0000;border:1px solid #ddd;border-radius:4px;padding:6px 14px;background:#FFF8EE;display:inline-flex;align-items:center;gap:6px">
                    <i class="fas fa-ruler-horizontal"></i>Xem bảng size chi tiết
                    <i class="fas fa-chevron-down" id="sizeArrow"></i>
                </button>
                <div class="collapse mt-2" id="sizeCollapse">
                    <div class="overflow-auto border rounded p-2 bg-white">
                        <table class="size-tbl">
                            <thead><tr><th>Size</th><th>Ngực</th><th>Eo</th><th>Hông</th><th>Cao (cm)</th><th>Nặng (kg)</th></tr></thead>
                            <tbody>
                            <?php foreach ($sizes as $s): ?>
                            <tr id="sr<?= $s[0] ?>"><td><?= $s[0] ?></td><td><?= $s[1] ?></td><td><?= $s[2] ?></td><td><?= $s[3] ?></td><td><?= $s[4] ?></td><td><?= $s[5] ?></td></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row mt-4 g-2 text-muted small">
                <div class="col-6"><i class="fas fa-truck text-warning me-1"></i> FreeShip đơn &gt; 500k</div>
                <div class="col-6"><i class="fas fa-sync-alt text-warning me-1"></i> Đổi trả trong 7 ngày</div>
                <div class="col-6"><i class="fas fa-check-circle text-warning me-1"></i> Kiểm tra trước khi nhận</div>
                <div class="col-6"><i class="fas fa-shield-alt text-warning me-1"></i> Bảo hành đường may 6 tháng</div>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <ul class="nav nav-tabs border-bottom border-danger" id="myTab">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#desc">Chi Tiết Sản Phẩm</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#size_chart">Bảng Size</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#review">Đánh Giá (<?= $tong_dg ?>)</button></li>
    </ul>
    <div class="tab-content p-4 border border-top-0 bg-white shadow-sm">

        <div class="tab-pane fade show active" id="desc">
            <div class="material-showcase bg-light p-4 rounded-3 mb-5 border border-warning">
                <h5 class="text-center fw-bold mb-4 text-uppercase" style="color:var(--brand-red);letter-spacing:2px">🌟 Tinh Hoa Chất Liệu</h5>
                <div class="row text-center g-4">
                    <?php
                    $mats = $gioi_tinh==='nam'
                        ? [['fa-scroll','Lụa Tơ Tằm','Vải lụa cao cấp thoáng mát, bền đẹp.'],['fa-wind','Thoải Mái','Phom dáng nam giới hiện đại, dễ mặc.'],['fa-gem','Thủ Công','Đường kim mũi chỉ bởi nghệ nhân lành nghề.']]
                        : [['fa-feather-alt','Gấm Thượng Uyển','Gấm dệt nổi hoa văn 3D, bắt sáng, quyền quý.'],['fa-wind','Đông Ấm – Hạ Mát','Sợi tự nhiên điều hòa thân nhiệt, lót lụa mềm.'],['fa-gem','Thêu Tay Tỉ Mỉ','Màu nhuộm bền, đường chỉ chắc chắn.']];
                    foreach ($mats as $m):
                    ?>
                    <div class="col-md-4">
                        <div class="material-card">
                            <div class="icon-box mb-3 text-warning"><i class="fas <?= $m[0] ?> fa-2x"></i></div>
                            <h6 class="fw-bold"><?= $m[1] ?></h6>
                            <p class="small text-muted mb-0"><?= $m[2] ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <?php if ($sp['mo_ta']): ?>
                    <h5 class="fw-bold text-danger mb-3">Mô Tả Chi Tiết</h5>
                    <div class="text-muted"><?= nl2br(htmlspecialchars($sp['mo_ta'])) ?></div>
                    <?php endif; ?>
                    <h5 class="fw-bold text-danger mt-4 mb-3">Thông Số Kỹ Thuật</h5>
                    <table class="table table-bordered table-striped">
                        <tbody>
                            <tr><th width="35%">Chất liệu</th><td><?= $gioi_tinh==='nam'?'Lụa tơ tằm / Gấm':'Gấm tơ tằm dệt nổi' ?></td></tr>
                            <tr><th>Giới tính</th><td><?= $gioi_tinh==='nam'?'Nam':'Nữ' ?></td></tr>
                            <tr><th>Lót trong</th><td>Lụa Habutai mềm mại</td></tr>
                            <tr><th>Xuất xứ</th><td>Việt Nam — Thủ công truyền thống</td></tr>
                            <tr><th>Tồn kho</th><td><?= $sp['so_luong_ton'] ?> sản phẩm</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-4">
                    <div class="alert alert-warning">
                        <h6 class="fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Bảo quản</h6>
                        <ul class="small mb-0 ps-3">
                            <li>Nên giặt khô (Dry Clean)</li>
                            <li>Giặt tay với dầu gội đầu</li>
                            <li>Không phơi nắng gắt</li>
                            <li>Ủi hơi nước nhiệt thấp</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="size_chart">
            <h5 class="fw-bold text-danger mb-3">Bảng Kích Cỡ <?= $gioi_tinh==='nam'?'Nam':'Nữ' ?></h5>
            <div class="table-responsive">
                <table class="size-tbl">
                    <thead><tr><th>Size</th><th>Ngực (cm)</th><th>Eo (cm)</th><th>Hông (cm)</th><th>Cao (cm)</th><th>Nặng (kg)</th></tr></thead>
                    <tbody>
                    <?php foreach ($sizes as $s): ?>
                    <tr><td><?= $s[0] ?></td><td><?= $s[1] ?></td><td><?= $s[2] ?></td><td><?= $s[3] ?></td><td><?= $s[4] ?></td><td><?= $s[5] ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-muted small mt-3"><i class="fas fa-phone me-1 text-warning"></i>Tư vấn may đo: <strong>0987.654.321</strong></p>
        </div>

        <div class="tab-pane fade" id="review">
            <?php if ($tong_dg > 0): ?>
            <div class="row g-3 mb-4">
                <div class="col-md-3 text-center">
                    <div class="fs-1 fw-bold text-danger"><?= $avg_sao ?></div>
                    <div class="text-warning"><?php for ($i=1;$i<=5;$i++): ?><i class="fas fa-star<?= $i>round($avg_sao)?'-o':'' ?>"></i><?php endfor; ?></div>
                    <small class="text-muted"><?= $tong_dg ?> đánh giá</small>
                </div>
                <div class="col-md-9">
                    <?php foreach ([5,4,3,2,1] as $s):
                        $cnt_s=(int)($dg_tq["s$s"]??0);
                        $pct_s=$tong_dg>0?round($cnt_s/$tong_dg*100):0;
                    ?>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <small style="min-width:14px"><?= $s ?></small>
                        <i class="fas fa-star text-warning" style="font-size:.7rem"></i>
                        <div class="progress flex-grow-1" style="height:8px"><div class="progress-bar bg-warning" style="width:<?= $pct_s ?>%"></div></div>
                        <small class="text-muted" style="min-width:20px"><?= $cnt_s ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($dg_list && $dg_list->num_rows > 0):
                while ($dg = $dg_list->fetch_assoc()):
            ?>
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between">
                    <div><strong><?= htmlspecialchars($dg['ho_ten'] ?? $dg['TaiKhoan']) ?></strong>
                        <div class="text-warning small"><?php for ($i=1;$i<=5;$i++): ?><i class="fas fa-star<?= $i>$dg['so_sao']?'-o':'' ?>"></i><?php endfor; ?></div>
                    </div>
                    <small class="text-muted"><?= date('d/m/Y',strtotime($dg['ngay_tao'])) ?></small>
                </div>
                <?php if ($dg['noi_dung']): ?><p class="mb-1 mt-2"><?= nl2br(htmlspecialchars($dg['noi_dung'])) ?></p><?php endif; ?>
                <?php if ($dg['phan_hoi_admin']): ?>
                <div class="bg-light border-start border-warning ps-3 py-2 mt-2 small">
                    <strong class="text-danger">Vân Y Các:</strong> <?= nl2br(htmlspecialchars($dg['phan_hoi_admin'])) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endwhile; else: ?>
            <div class="text-center py-4 text-muted"><i class="fas fa-comments fa-2x mb-2 d-block"></i>Chưa có đánh giá. Hãy là người đầu tiên!</div>
            <?php endif; ?>

            <?php if ($da_dang_nhap): ?>
            <div class="border rounded p-4 mt-4 bg-light">
                <h6 class="fw-bold text-danger mb-3"><i class="fas fa-pen me-2"></i>Viết Đánh Giá</h6>
                <form id="reviewForm">
                    <input type="hidden" name="id_san_pham" value="<?= $sp['id'] ?>">
                    <div class="mb-3">
                        <label class="fw-bold small">Số sao:</label>
                        <div id="starPick" class="d-flex gap-2 mt-1">
                            <?php for ($i=1;$i<=5;$i++): ?>
                            <i class="fas fa-star fs-4 text-warning" data-val="<?= $i ?>" onclick="rateStar(<?= $i ?>)" style="cursor:pointer"></i>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="so_sao" id="soSaoInp" value="5">
                    </div>
                    <textarea name="noi_dung" class="form-control mb-3" rows="3" placeholder="Chia sẻ cảm nhận..."></textarea>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-paper-plane me-2"></i>Gửi Đánh Giá</button>
                </form>
            </div>
            <?php else: ?>
            <div class="alert alert-light border border-warning mt-4 text-center">
                <i class="fas fa-lock me-2 text-warning"></i>
                <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" class="text-danger fw-bold">Đăng nhập</a> để viết đánh giá.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($sp_lq && $sp_lq->num_rows > 0): ?>
<div class="container-fluid py-5" style="background:#FFF8EE">
    <div class="container">
        <h4 class="fw-bold text-danger mb-4 text-center" style="font-family:'Cormorant Garamond',serif; font-size:2.2rem;">Sản Phẩm Liên Quan</h4>
        <div class="row g-3">
            <?php while ($r=$sp_lq->fetch_assoc()): ?>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100 rel-card">
                    <div class="rel-img-wrap" style="height:220px; padding:8px; background:#FAF6EE;">
                        <img src="image/<?= htmlspecialchars($r['duong_dan']??'no-image.jpg') ?>"
                             onerror="this.src='https://placehold.co/300x400/FAF6EE/8B0000?text=SP'"
                             class="card-img-top w-100 h-100" style="object-fit:contain;"
                             alt="<?= htmlspecialchars($r['ten_vi']) ?>">
                        
                        <div class="rel-overlay">
                            <button class="rel-btn rel-btn-view" onclick="window.location.href='sanpham.php?id=<?= $r['id'] ?>'"><i class="fas fa-eye me-1"></i>Xem Chi Tiết</button>
                           <?php if (!$is_admin): ?>
<button class="rel-btn rel-btn-buy" onclick="handleRelCart(<?= $r['id'] ?>);"><i class="fas fa-shopping-bag me-1"></i>Chọn Mua</button>
<?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card-body p-2 text-center">
                        <a href="sanpham.php?id=<?= $r['id'] ?>" class="text-decoration-none">
                            <p class="small fw-bold text-dark mb-1"><?= htmlspecialchars(mb_substr($r['ten_vi'],0,45)) ?></p>
                            <span class="text-danger fw-bold"><?= number_format($r['gia_ban'],0,',','.') ?>₫</span>
                            <?php if ($r['gia_goc']&&$r['gia_goc']>$r['gia_ban']): ?>
                            <span class="text-muted text-decoration-line-through small ms-2"><?= number_format($r['gia_goc'],0,',','.') ?>₫</span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'resources/views/layouts/footer.php'; ?>

<?php if ($is_admin): ?>
<div class="admin-edit-bar">
    <span><i class="fas fa-shield-alt me-1"></i>Admin</span>
    <a href="admin/san-pham/form.php?id=<?= $sp['id'] ?>" class="btn-edit">
        <i class="fas fa-edit me-1"></i>Chỉnh Sửa SP
    </a>
    <a href="#" class="btn-stock" onclick="quickUpdateStock(<?= $sp['id'] ?>)">
        <i class="fas fa-boxes me-1"></i>Cập Nhật Tồn Kho
    </a>
    <a href="admin/san-pham/index.php" class="btn-stock">
        <i class="fas fa-list me-1"></i>Danh Sách SP
    </a>
</div>
<script>
function quickUpdateStock(spId) {
    const cur = <?= (int)$sp['so_luong_ton'] ?>;
    const val = prompt('Số lượng tồn kho hiện tại: ' + cur + '\nNhập số lượng mới:', cur);
    if (val === null || isNaN(+val)) return;
    fetch('api/admin-quick.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update_stock', id: spId, qty: +val })
    }).then(r => r.json()).then(d => {
        if (d.success) { showToast('Đã cập nhật tồn kho: ' + val, 'ok'); }
        else showToast(d.message || 'Lỗi cập nhật', 'err');
    }).catch(() => showToast('Lỗi kết nối', 'err'));
}
</script>
<?php endif; ?>
<div class="toast-wrap" id="toastWrap"></div>
<script>
function changeImage(img) {
    document.getElementById('mainImage').src = img.src;
    document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
    img.classList.add('active');
}

let selectedSize = '';
function selectSize(btn) {
    document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    selectedSize = btn.dataset.size;
    document.getElementById('selectedSizeTxt').textContent = '— ' + selectedSize;
    document.querySelectorAll('.size-tbl tbody tr').forEach(tr => tr.classList.remove('hl'));
    const row = document.getElementById('sr' + selectedSize);
    if (row) row.classList.add('hl');
}

function toggleSizeTable() {
    const el = document.getElementById('sizeCollapse');
    const arrow = document.getElementById('sizeArrow');
    const isOpen = el.classList.contains('show');
    el.classList.toggle('show');
    arrow.className = isOpen ? 'fas fa-chevron-down ms-1' : 'fas fa-chevron-up ms-1';
}

function changeQty(delta) {
    const inp = document.getElementById('qtyInput');
    if (!inp) return;
    let v = parseInt(inp.value) + delta;
    inp.value = Math.min(Math.max(v, 1), parseInt(inp.max) || 99);
}

// XỬ LÝ NÚT MUA NGAY HOẶC THÊM GIỎ
async function handleCart(action) {
    if (!selectedSize) {
        showToast('Vui lòng chọn kích thước trước!', 'warn');
        document.getElementById('chon-size').scrollIntoView({ behavior:'smooth', block:'center' });
        document.getElementById('sizeBtns').style.outline = '2px dashed #dc3545';
        setTimeout(() => document.getElementById('sizeBtns').style.outline = '', 2000);
        return;
    }
    const qty = parseInt(document.getElementById('qtyInput')?.value) || 1;
    
    try {
        const res = await fetch('public/api.php?action=cart', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ action:'add', id_san_pham: <?= $sp['id'] ?>, size: selectedSize, so_luong: qty })
        });
        const data = await res.json();
        
        if (data.need_login) {
            const modal = new bootstrap.Modal(document.getElementById('loginModal'));
            modal.show();
            return;
        }
        if (data.success) {
            document.querySelectorAll('.cart-badge, #cartBadge').forEach(b => b.textContent = data.cart_count);
            
            if (action === 'buy') {
                // CHUYỂN TỚI TRANG THANH TOÁN (Lấy ID giỏ hàng vừa thêm)
                showToast('Đang xử lý đơn hàng...', 'ok');
                const cartRes = await fetch('public/api.php?action=cart');
                const cartData = await cartRes.json();
                
                if (cartData.success && cartData.cart_items) {
                    const newItem = cartData.cart_items.find(i => i.id_san_pham == <?= $sp['id'] ?> && i.size == selectedSize);
                    if (newItem) {
                        setTimeout(() => window.location.href = 'thanhtoan.php?items=' + newItem.id, 700);
                        return;
                    }
                }
                setTimeout(() => window.location.href = 'giohang.php', 700);
            } else {
                showToast(`Đã thêm ${qty} áo Size ${selectedSize} vào giỏ hàng!`, 'ok');
            }
        } else {
            showToast(data.message || 'Có lỗi xảy ra', 'err');
        }
    } catch(e) { showToast('Không thể kết nối máy chủ', 'err'); }
}

// XỬ LÝ CHỌN MUA Ở SẢN PHẨM LIÊN QUAN
async function handleRelCart(id_sp) {
    try {
        const res = await fetch('public/api.php?action=cart', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ action:'add', id_san_pham: id_sp, size: 'M', so_luong: 1 })
        });
        const data = await res.json();
        
        if (data.need_login) {
            const modal = new bootstrap.Modal(document.getElementById('loginModal'));
            modal.show();
            return;
        }
        if (data.success) {
            document.querySelectorAll('#cartBadge').forEach(b => b.textContent = data.cart_count);
            showToast(`Đã thêm sản phẩm vào giỏ hàng!`, 'ok');
        } else {
            showToast(data.message || 'Có lỗi xảy ra', 'warn');
        }
    } catch(e) { showToast('Lỗi kết nối máy chủ', 'err'); }
}

function rateStar(val) {
    document.querySelectorAll('#starPick i').forEach((s, i) => { s.style.opacity = i < val ? '1' : '0.35'; });
    document.getElementById('soSaoInp').value = val;
}

const reviewForm = document.getElementById('reviewForm');
if (reviewForm) {
    reviewForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        try {
            const res = await fetch('review.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) });
            const result = await res.json();
            if (result.success) { showToast('Đánh giá đã gửi, chờ duyệt!', 'ok'); this.reset(); rateStar(5); }
            else showToast(result.message||'Gửi thất bại','err');
        } catch { showToast('Lỗi kết nối','err'); }
    });
}

function showToast(msg, type='ok') {
    const icons = {ok:'fa-check-circle',warn:'fa-exclamation-circle',err:'fa-times-circle'};
    const wrap  = document.getElementById('toastWrap');
    const t     = document.createElement('div');
    t.className = 'my-toast ' + type;
    t.innerHTML = `<i class="fas ${icons[type]}"></i><span>${msg}</span>`;
    wrap.appendChild(t);
    setTimeout(() => { t.style.animation='toastOut .3s ease forwards'; setTimeout(()=>t.remove(),300); }, 3000);
}

document.addEventListener('loginSuccess', () => setTimeout(() => window.location.reload(), 600));
</script>
</body>
</html>