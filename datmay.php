<?php
session_start();
include 'config/db.php';

// 1. LẤY THÔNG TIN NGƯỜI DÙNG (NẾU ĐÃ ĐĂNG NHẬP)
$uid = $_SESSION['user_id'] ?? null;
$user_info = null;
if ($uid) {
    $user_info = $conn->query("SELECT HoVaTen, SoDienThoai FROM khachhang WHERE idKhachHang = $uid LIMIT 1")->fetch_assoc();
}

$msg = '';
$msg_type = '';

// 2. XỬ LÝ LƯU FORM ĐẶT MAY
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['gui_dat_may'])) {
    $id_khach = $uid ?? 0; 
    if ($id_khach == 0) { 
        echo "<script>alert('Vui lòng đăng nhập để thực hiện đặt may!'); $('#loginModal').modal('show');</script>";
  } else {
        $id_sp = isset($_POST['id_san_pham']) && $_POST['id_san_pham'] !== '' ? (int)$_POST['id_san_pham'] : 0;
        
        $cao = (float)$_POST['chieu_cao'];
        $nang = (float)$_POST['can_nang'];
        $v1 = (float)$_POST['vong_1'];
        $v2 = (float)$_POST['vong_2'];
        $v3 = (float)$_POST['vong_3'];

        // KIỂM TRA LỖI LOGIC VÀ KÍCH THƯỚC
        if ($id_sp === 0) {
            $msg = "Bạn chưa chọn sản phẩm nào! Vui lòng chọn một mẫu áo ở danh sách.";
            $msg_type = "danger";
        } elseif ($cao < 100 || $cao > 250 || $nang < 30 || $nang > 150 || $v1 < 40 || $v1 > 150 || $v2 < 40 || $v2 > 150 || $v3 < 40 || $v3 > 150) {
            $msg = "Số đo không hợp lý! Vui lòng nhập số đo thực tế (VD: Cao 100-250cm, Nặng 30-150kg, Vòng 40-150cm).";
            $msg_type = "danger";
        } else {
            // Gom các số đo thành 1 chuỗi văn bản
            $thong_so = "Chiều cao: {$cao}cm, Cân nặng: {$nang}kg, V1: {$v1}, V2: {$v2}, V3: {$v3}";
            $thong_so_e = $conn->real_escape_string($thong_so);

            // Thêm vào giỏ hàng với size là 'May đo' và kèm thông số
            $sql_add_cart = "INSERT INTO gio_hang (id_khach_hang, id_san_pham, so_luong, size, thong_so_rieng) 
                             VALUES ($id_khach, $id_sp, 1, 'May đo', '$thong_so_e')";
            
            if ($conn->query($sql_add_cart)) {
                $new_id = $conn->insert_id;
                header("Location: thanhtoan.php?items=$new_id");
                exit;
            }
        }
    }
}
// 3. LẤY DANH MỤC LÀM TABS
$dm_list = $conn->query("SELECT id, ten_danh_muc FROM danh_muc WHERE trang_thai = 1 AND id_cha IS NOT NULL ORDER BY thu_tu ASC");
$categories = [];
while($r = $dm_list->fetch_assoc()) {
    $categories[] = $r;
}
// Xác định danh mục đang active (ưu tiên URL GET, nếu không lấy cái đầu tiên)
$active_dm = isset($_GET['danh_muc']) ? (int)$_GET['danh_muc'] : ($categories[0]['id'] ?? 0);

// 4. LẤY SẢN PHẨM THEO DANH MỤC ĐANG CHỌN
$sp_list = [];
if ($active_dm > 0) {
    $rs_sp = $conn->query("SELECT id, ten_vi, gia_ban, duong_dan FROM san_pham WHERE id_danh_muc = $active_dm AND trang_thai = 1 ORDER BY id DESC");
    if($rs_sp) {
        while($r = $rs_sp->fetch_assoc()) $sp_list[] = $r;
    }
}

include 'resources/views/layouts/header.php';
?>

<style>
    /* Nền đỏ đậm, chữ vàng giống hệt thiết kế */
    body { background-color: #1a0505; } /* Dự phòng */
    
    .datmay-wrapper {
        background: linear-gradient(135deg, #2b0505 0%, #4a0000 50%, #2b0505 100%);
        min-height: calc(100vh - 80px);
        padding: 40px 20px 80px;
        position: relative;
    }
    
    /* Hoa văn chìm (tuỳ chọn nếu bạn có ảnh pattern) */
    .datmay-wrapper::before {
        content: ""; position: absolute; inset: 0; opacity: 0.05; pointer-events: none;
        background-image: url('https://www.transparenttextures.com/patterns/black-scales.png');
    }

    .dm-header { text-align: center; margin-bottom: 40px; position: relative; z-index: 2; }
    .dm-title { font-family: 'Cormorant Garamond', serif; color: #FFD700; font-size: 2.2rem; font-weight: bold; letter-spacing: 2px; margin-bottom: 5px;}
    .dm-subtitle { font-family: 'EB Garamond', serif; color: rgba(255, 215, 0, 0.7); font-size: 1rem;}

    .dm-layout {
        display: flex; gap: 30px; max-width: 1200px; margin: 0 auto; position: relative; z-index: 2; align-items: flex-start;
    }

    /* === CỘT TRÁI: DANH SÁCH SẢN PHẨM === */
    .dm-left { flex: 1; }
    
    /* Tabs Danh mục */
    .dm-tabs { display: flex; flex-wrap: wrap; gap: 15px; border-bottom: 1px solid rgba(255, 215, 0, 0.3); padding-bottom: 15px; margin-bottom: 25px; }
    .dm-tab-item { color: rgba(255, 215, 0, 0.7); text-decoration: none; font-family: 'EB Garamond', serif; font-size: 1.1rem; padding: 5px 10px; transition: 0.3s; }
    .dm-tab-item:hover { color: #FFD700; }
    .dm-tab-item.active { color: #FFD700; font-weight: bold; border-bottom: 2px solid #FFD700; }

    /* Lưới sản phẩm */
    .dm-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .dm-product {
        background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 215, 0, 0.2); border-radius: 8px; padding: 15px; text-align: center; cursor: pointer; transition: 0.3s;
    }
    .dm-product:hover { border-color: rgba(255, 215, 0, 0.6); transform: translateY(-5px); }
    .dm-product.selected { border: 2px solid #FFD700; background: rgba(255, 215, 0, 0.1); box-shadow: 0 0 15px rgba(255, 215, 0, 0.3); }
    
    .dm-product img { width: 100%; height: 200px; object-fit: cover; border-radius: 4px; margin-bottom: 12px; }
    .dm-product h3 { color: #fff; font-family: 'EB Garamond', serif; font-size: 1rem; margin-bottom: 8px; line-height: 1.4; }
    .dm-product .price { color: #FFD700; font-weight: bold; font-size: 0.95rem; }

    /* === CỘT PHẢI: FORM ĐẶT MAY === */
    .dm-right { width: 380px; flex-shrink: 0; position: sticky; top: 100px; }
    .dm-form-card { background: #fff; border-radius: 12px; padding: 25px; box-shadow: 0 15px 30px rgba(0,0,0,0.3); }
    
    .form-title { font-family: 'Cormorant Garamond', serif; color: #8B0000; font-size: 1.4rem; font-weight: bold; margin-bottom: 20px; text-transform: uppercase; border-bottom: 2px solid #eee; padding-bottom: 10px;}
    
    .form-label { font-size: 0.85rem; font-weight: 600; color: #333; margin-bottom: 5px; display: block; }
    .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px; font-size: 0.9rem; }
    .form-control:focus { border-color: #8B0000; outline: none; }
    
    .row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
    
    .btn-submit { width: 100%; background: #8B0000; color: #fff; border: none; padding: 14px; font-weight: bold; font-size: 1rem; border-radius: 4px; cursor: pointer; transition: 0.3s; margin-top: 10px; }
    .btn-submit:hover { background: #5C0000; }
    
    .dm-note { background: #FFFBEB; border: 1px solid #FCD34D; padding: 15px; border-radius: 4px; margin-top: 20px; text-align: center; font-size: 0.85rem; color: #92400E; }
    
    @media (max-width: 992px) {
        .dm-layout { flex-direction: column; }
        .dm-right { width: 100%; position: static; }
        .dm-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 576px) { .dm-grid { grid-template-columns: 1fr; } }
</style>

<div class="datmay-wrapper">
    <div class="dm-header">
        <h1 class="dm-title">TINH HOA CỔ PHỤC VIỆT</h1>
        <div class="dm-subtitle">Khôi phục vẻ đẹp truyền thống - Kết nối giá trị hiện đại</div>
    </div>

    <?php if ($msg): ?>
        <div class="container mb-4" style="max-width: 1200px; position: relative; z-index: 2;">
            <div class="alert alert-<?=$msg_type?> text-center fw-bold p-3 rounded shadow">
                <?=$msg?>
            </div>
        </div>
    <?php endif; ?>

    <div class="dm-layout">
        <div class="dm-left">
            <div class="dm-tabs">
                <?php foreach($categories as $c): ?>
                    <a href="?danh_muc=<?=$c['id']?>" class="dm-tab-item <?=$c['id'] == $active_dm ? 'active' : ''?>">
                        <?=htmlspecialchars($c['ten_danh_muc'])?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="dm-grid">
                <?php if(empty($sp_list)): ?>
                    <div style="color: #fff; grid-column: 1/-1;">Chưa có sản phẩm nào trong danh mục này.</div>
                <?php else: ?>
                    <?php foreach($sp_list as $sp): ?>
                        <div class="dm-product" onclick="selectProduct(this, <?=$sp['id']?>)">
                            <img src="image/<?=htmlspecialchars($sp['duong_dan'])?>" onerror="this.src='https://placehold.co/300x400/333/fff?text=SP'" alt="SP">
                            <h3><?=htmlspecialchars($sp['ten_vi'])?></h3>
                            <div class="price"><?=number_format($sp['gia_ban'],0,',','.')?> VNĐ</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="dm-right">
            <div class="dm-form-card">
                <div class="form-title">PHIẾU ĐẶT MAY</div>
                
                <form method="POST" action="">
                    <input type="hidden" name="id_san_pham" id="input_id_san_pham" value="">

                    <label class="form-label">Họ tên khách hàng</label>
                    <input type="text" name="ho_ten" class="form-control" required placeholder="Nguyễn Văn A" value="<?=htmlspecialchars($user_info['HoVaTen'] ?? '')?>">

                    <label class="form-label">Số điện thoại</label>
                    <input type="tel" name="so_dien_thoai" class="form-control" required placeholder="090..." pattern="[0-9]{10}" value="<?=htmlspecialchars($user_info['SoDienThoai'] ?? '')?>">

          <div class="row-2">
                        <div>
                            <label class="form-label">Chiều Cao (cm)</label>
                            <input type="number" step="0.1" name="chieu_cao" class="form-control" required min="100" max="250" placeholder="VD: 160">
                        </div>
                        <div>
                            <label class="form-label">Cân Nặng (kg)</label>
                            <input type="number" step="0.1" name="can_nang" class="form-control" required min="30" max="150" placeholder="VD: 50">
                        </div>
                    </div>

                    <div class="row-3">
                        <div>
                            <label class="form-label">Vòng 1</label>
                            <input type="number" step="0.1" name="vong_1" class="form-control" required min="40" max="150" placeholder="VD: 85">
                        </div>
                        <div>
                            <label class="form-label">Vòng 2</label>
                            <input type="number" step="0.1" name="vong_2" class="form-control" required min="40" max="150" placeholder="VD: 62">
                        </div>
                        <div>
                            <label class="form-label">Vòng 3</label>
                            <input type="number" step="0.1" name="vong_3" class="form-control" required min="40" max="150" placeholder="VD: 90">
                        </div>
                    </div>

                    <button type="submit" name="gui_dat_may" class="btn-submit">GỬI YÊU CẦU ĐẶT MAY</button>

                    <div class="dm-note">
                        <strong>Lưu ý:</strong> Sản phẩm may đo thủ công, dự kiến trả hàng sau <strong>30 ngày</strong> kể từ khi xác nhận số đo.
                        <hr style="border-color: rgba(0,0,0,0.1); margin: 10px 0;">
                        <span style="font-size: 0.8rem; color: #666;">Chúng tôi sẽ liên hệ lại để tư vấn chi tiết hơn.</span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Javascript xử lý hiệu ứng click chọn sản phẩm
function selectProduct(element, idSp) {
    // Xóa class 'selected' ở tất cả các sản phẩm
    let allProducts = document.querySelectorAll('.dm-product');
    allProducts.forEach(p => p.classList.remove('selected'));
    
    // Thêm class 'selected' (viền vàng sáng) cho sản phẩm vừa click
    element.classList.add('selected');
    
    // Gán ID sản phẩm vào thẻ input ẩn trong form
    document.getElementById('input_id_san_pham').value = idSp;
}
</script>

<?php include 'resources/views/layouts/footer.php'; ?>