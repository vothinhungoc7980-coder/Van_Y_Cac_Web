<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "config/db.php";
session_start();

// ═══ HÀM TIỆN ÍCH ═══
function sach($str) {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

// ═══ API: LẤY 1 SẢN PHẨM NGẪU NHIÊN CHO XIN XĂM (Gọi ngầm bằng AJAX) ═══
if (isset($_GET['action']) && $_GET['action'] === 'random_sp') {
    header('Content-Type: application/json');
    $kw = sach($_GET['keyword'] ?? '');
    $kw_sql = '%' . $kw . '%';
    
    // Tìm sản phẩm có chứa từ khóa màu sắc của quẻ xăm
    $stmt = $conn->prepare("SELECT id, ten_vi, gia_ban, duong_dan FROM san_pham WHERE trang_thai = 1 AND ten_vi LIKE ? ORDER BY RAND() LIMIT 1");
    $stmt->bind_param("s", $kw_sql);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    
    if ($res) {
        echo json_encode(['success' => true, 'data' => $res]);
    } else {
        // Nếu không có màu đó, lấy đại 1 sản phẩm bán chạy nhất làm may mắn
        $fallback = $conn->query("SELECT id, ten_vi, gia_ban, duong_dan FROM san_pham WHERE trang_thai = 1 ORDER BY da_ban DESC LIMIT 1")->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $fallback]);
    }
    exit;
}

// ═══ TƯ VẤN SIZE & PHONG CÁCH ═══
$ket_qua = $dang_nguoi = $mo_ta = $size_goi_y = $mix_match = "";
$bmi_value = 0; $result_sp = null; $loi_tuvan = "";

if (isset($_GET['tu_van'])) {
    $gioi_tinh = sach($_GET['gioi_tinh'] ?? '');
    $chieu_cao = (float)($_GET['chieu_cao'] ?? 0);
    $can_nang  = (float)($_GET['can_nang']  ?? 0);

    if (!in_array($gioi_tinh, ['nam', 'nu'])) {
        $loi_tuvan = "Vui lòng chọn giới tính hợp lệ.";
    } elseif ($chieu_cao < 100 || $chieu_cao > 250) {
        $loi_tuvan = "Chiều cao phải từ 100cm đến 250cm.";
    } elseif ($can_nang < 20 || $can_nang > 300) {
        $loi_tuvan = "Cân nặng phải từ 20kg đến 300kg.";
    } else {
        $bmi_value = $can_nang / pow($chieu_cao / 100, 2);

        if ($bmi_value < 18.5) {
            $ket_qua    = "Dáng thon mảnh mai 🌿";
            $mo_ta      = "Vóc dáng thanh thoát! Thiết kế có độ phồng nhẹ, hoa văn lớn sẽ tôn lên nét đẹp của bạn. Nên chọn màu sắc tươi sáng.";
            $size_goi_y = "S";
            $mix_match  = "Áo dài họa tiết nổi + Giày gót vuông thấp + Quạt lụa";
            $icon = "🌸";
        } elseif ($bmi_value < 25) {
            $ket_qua    = "Dáng chuẩn lý tưởng ✨";
            $mo_ta      = "Vóc dáng cân đối tuyệt vời! Hầu hết các thiết kế Việt Cổ Phục đều sẽ tôn lên đường cong tự nhiên của bạn.";
            $size_goi_y = "M - L";
            $mix_match  = "Nhật Bình / Áo Tấc + Hài thêu + Ngọc bội";
            $icon = "✨";
        } else {
            $ket_qua    = "Dáng đầy đặn quyến rũ 💫";
            $mo_ta      = "Vóc dáng khỏe khoắn, thu hút! Thiết kế suông, họa tiết nhí hoặc kẻ dọc, màu sắc trầm ấm sẽ là vũ khí tối thượng.";
            $size_goi_y = "XL - 2XL";
            $mix_match  = "Áo Ngũ Thân suông + Guốc mộc + Túi gấm";
            $icon = "💫";
        }

        $gt_sql = $gioi_tinh === 'nam' ? "Nam" : "Nữ";
        $stmt = $conn->prepare("SELECT id, ten_vi, gia_ban, gia_goc, duong_dan FROM san_pham WHERE trang_thai = 1 AND (gioi_tinh = ? OR gioi_tinh = 'Unisex') ORDER BY da_ban DESC, luot_xem DESC LIMIT 3");
        $stmt->bind_param("s", $gt_sql);
        $stmt->execute();
        $result_sp = $stmt->get_result();
    }
}

// ═══ XEM BÓI MÀU & ĐỀ XUẤT SẢN PHẨM PHONG THỦY ═══
$boi_mau = $boi_loi_chuc = $ngu_hanh = ""; $mau_hop = []; $loi_boi = "";
$sp_phong_thuy = []; // Chứa danh sách sản phẩm phong thủy

if (isset($_GET['xem_boi'])) {
    $nam_sinh = (int)($_GET['nam_sinh'] ?? 0);
    if ($nam_sinh < 1920 || $nam_sinh > 2024) {
        $loi_boi = "Năm sinh không hợp lệ.";
    } else {
        $can = $nam_sinh % 10;
        $hanh_map = [0=>'Kim',1=>'Kim',2=>'Thủy',3=>'Thủy',4=>'Mộc',5=>'Mộc',6=>'Hỏa',7=>'Hỏa',8=>'Thổ',9=>'Thổ'];
        $ngu_hanh = $hanh_map[$can];
        $tu_khoa_mau = []; // Các từ khóa để Search SQL
        
        switch ($ngu_hanh) {
            case 'Kim':
                $mau_hop = ['Trắng ngà','Vàng ánh kim','Bạc xám'];
                $tu_khoa_mau = ['Trắng', 'Vàng', 'Bạc'];
                $boi_mau = "Mệnh Kim – Tinh tế & Sang trọng";
                $boi_loi_chuc = "Màu trắng và vàng kim mang lại sự thuần khiết và thịnh vượng. Năm nay vận may rực rỡ như ánh vàng! 🌟";
                break;
            case 'Thủy':
                $mau_hop = ['Đen huyền','Xanh navy','Xanh biển'];
                $tu_khoa_mau = ['Đen', 'Xanh', 'Navy'];
                $boi_mau = "Mệnh Thủy – Sâu sắc & Trí tuệ";
                $boi_loi_chuc = "Màu đen và xanh biển tượng trưng cho sự uyên bác. Duyên may và tài lộc tuôn chảy như dòng nước! 💙";
                break;
            case 'Mộc':
                $mau_hop = ['Xanh lá','Xanh ngọc','Xanh mint'];
                $tu_khoa_mau = ['Xanh', 'Ngọc', 'Mint'];
                $boi_mau = "Mệnh Mộc – Tươi mới & Bình yên";
                $boi_loi_chuc = "Màu xanh lá tượng trưng cho sự sinh sôi nảy nở. Cuộc sống và công việc tươi tốt như cây cối mùa xuân! 🌿";
                break;
            case 'Hỏa':
                $mau_hop = ['Đỏ son','Hồng cánh sen','Cam đất'];
                $tu_khoa_mau = ['Đỏ', 'Hồng', 'Cam'];
                $boi_mau = "Mệnh Hỏa – Nhiệt huyết & Đam mê";
                $boi_loi_chuc = "Đỏ và hồng mang ngọn lửa của tình yêu và danh vọng. Bạn sẽ tỏa sáng rực rỡ và thu hút mọi ánh nhìn! 🔥";
                break;
            case 'Thổ':
                $mau_hop = ['Vàng đất','Nâu trầm','Be'];
                $tu_khoa_mau = ['Vàng', 'Nâu', 'Be'];
                $boi_mau = "Mệnh Thổ – Vững chãi & Bao dung";
                $boi_loi_chuc = "Vàng đất và nâu trầm tượng trưng cho sự bền bỉ, an toàn. Gia đạo bình an, sự nghiệp vững chắc như núi! 🌻";
                break;
        }

        // TÌM SẢN PHẨM CÓ MÀU HỢP MỆNH
        if (!empty($tu_khoa_mau)) {
            $likes = []; $params = []; $types = "";
            foreach($tu_khoa_mau as $kw) {
                $likes[] = "ten_vi LIKE ?";
                $params[] = "%" . $kw . "%";
                $types .= "s";
            }
            $like_sql = implode(" OR ", $likes);
            $stmt_pt = $conn->prepare("SELECT id, ten_vi, gia_ban, duong_dan FROM san_pham WHERE trang_thai = 1 AND ($like_sql) ORDER BY RAND() LIMIT 6");
            if ($stmt_pt) {
                $stmt_pt->bind_param($types, ...$params);
                $stmt_pt->execute();
                $rs_pt = $stmt_pt->get_result();
                while($r = $rs_pt->fetch_assoc()) $sp_phong_thuy[] = $r;
            }
        }
    }
}

$active_dm_id = 0; 
include 'resources/views/layouts/header.php';
?>

<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<style>
body { background: #FAF6EE; color: #1A0A0A; }
.hero-ai {
    background: linear-gradient(rgba(139,0,0,0.8), rgba(26,10,10,0.9)), url('image/banner.jpg') center/cover fixed;
    padding: 80px 0; text-align: center; color: #fff;
    border-bottom: 5px solid #C9A84C;
}
.hero-ai h1 { font-family: 'Cormorant Garamond', serif; font-size: 3rem; font-weight: 700; color: #FFD700; }
.hero-ai p { font-family: 'EB Garamond', serif; font-size: 1.2rem; color: #E8E1D5; font-style: italic; }

.ai-card {
    background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    border: 1px solid #E8E1D5; padding: 40px; margin-bottom: 40px;
}
.ai-card-title {
    font-family: 'Cormorant Garamond', serif; font-size: 1.8rem; font-weight: 700;
    color: #8B0000; text-align: center; margin-bottom: 30px; position: relative;
}
.ai-card-title::after {
    content: ''; display: block; width: 60px; height: 3px; background: #C9A84C;
    margin: 10px auto 0; border-radius: 2px;
}

.form-label { font-weight: 600; color: #5C0000; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; }
.form-control, .form-select { border: 1.5px solid #E8E1D5; border-radius: 8px; padding: 12px; background: #FAF6EE; }
.form-control:focus, .form-select:focus { border-color: #8B0000; box-shadow: 0 0 0 3px rgba(139,0,0,0.1); background: #fff; }
.btn-gold {
    background: linear-gradient(135deg, #C9A84C, #A17F2C); color: #fff;
    border: none; border-radius: 8px; padding: 12px 30px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s; width: 100%;
}
.btn-gold:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(201,168,76,0.4); color: #fff; }

.result-box {
    background: #FFF8EE; border: 1px solid #C9A84C; border-radius: 12px;
    padding: 25px; text-align: center; margin-top: 30px;
}
.result-box h4 { color: #8B0000; font-family: 'Cormorant Garamond', serif; font-weight: 700; font-size: 1.8rem; }
.info-chip { display: inline-block; background: #8B0000; color: #FFD700; padding: 8px 20px; border-radius: 30px; font-weight: 600; margin-top: 15px; font-size: 1.1rem; }
.mixmatch-box { background: #fff; border: 1px dashed #C9A84C; border-radius: 12px; padding: 20px; margin-top: 20px; text-align: center; }

/* CARD SẢN PHẨM DÙNG CHUNG */
.prod-card {
    background: #fff; border: 1px solid #E8E1D5; border-radius: 8px; overflow: hidden;
    transition: all 0.3s ease; position: relative; height: 100%;
}
.prod-card:hover { box-shadow: 0 15px 35px rgba(139,0,0,0.1); transform: translateY(-8px); border-color: #C9A84C; }
.prod-img-wrap { height: 250px; overflow: hidden; position: relative; background: #F9F9F9; }
.prod-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
.prod-card:hover .prod-img { transform: scale(1.05); }
.prod-overlay {
    position: absolute; inset: 0; background: rgba(26,10,10,0.5);
    display: flex; align-items: center; justify-content: center; gap: 10px;
    opacity: 0; transition: opacity 0.3s ease;
}
.prod-card:hover .prod-overlay { opacity: 1; }
.prod-action-btn {
    width: 45px; height: 45px; border-radius: 50%; background: #fff; color: #8B0000;
    display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
    text-decoration: none; transform: translateY(20px); transition: all 0.3s ease; border: none; cursor: pointer;
}
.prod-action-btn:hover { background: #8B0000; color: #fff; }
.prod-card:hover .prod-action-btn { transform: translateY(0); }
.prod-info { padding: 15px; text-align: center; }
.prod-name { font-family: 'EB Garamond', serif; font-size: 1.1rem; font-weight: 600; color: #1A0A0A; text-decoration: none; display: block; margin-bottom: 5px; }
.prod-price { font-family: 'Cormorant Garamond', serif; font-size: 1.2rem; font-weight: 700; color: #8B0000; }

.mau-chip {
    display: inline-block; padding: 8px 25px; border-radius: 30px; font-weight: 600; color: #fff;
    margin: 5px; border: 2px solid rgba(255,255,255,0.3); box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}

/* XIN XĂM */
.xam-container { text-align: center; }
.ong-xam {
    width: 100px; height: 180px; background: linear-gradient(180deg,#8B0000,#5C0000);
    border-radius: 10px 10px 5px 5px; margin: 0 auto 30px; position: relative;
    border: 3px solid #C9A84C; box-shadow: 0 10px 30px rgba(0,0,0,0.3); cursor: pointer;
}
.ong-xam::before { content: '籤'; position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); font-size: 60px; color: #C9A84C; opacity: 0.4; }
.xam-stick {
    width: 8px; height: 130px; background: linear-gradient(180deg,#F5DEB3,#D2B48C,#F5DEB3);
    border-radius: 4px 4px 2px 2px; position: absolute; bottom: 10px; transform-origin: bottom center;
}
.xam-stick:nth-child(1){left:15px;transform:rotate(-25deg);} .xam-stick:nth-child(2){left:25px;transform:rotate(-15deg);}
.xam-stick:nth-child(3){left:35px;transform:rotate(-5deg);}  .xam-stick:nth-child(4){left:45px;transform:rotate(5deg);}
.xam-stick:nth-child(5){left:55px;transform:rotate(15deg);}  .xam-stick:nth-child(6){left:65px;transform:rotate(25deg);}
@keyframes shake { 0%,100%{transform:rotate(0) translateX(0)} 25%{transform:rotate(-10deg) translateX(-5px)} 50%{transform:rotate(10deg) translateX(5px)} 75%{transform:rotate(-5deg) translateX(-2px)} }
.ong-xam.shaking { animation: shake 0.6s ease infinite; }
@keyframes fallOut { 0%{transform:translateY(0) rotate(0); opacity:1} 100%{transform:translateY(80px) rotate(60deg); opacity:0} }
.xam-stick.falling { animation: fallOut 0.5s ease forwards; z-index: 10;}
.que-result {
    background: url('image/banner.jpg') center/cover; position: relative;
    border: 2px solid #C9A84C; border-radius: 12px; padding: 40px 30px; margin-top: 30px; display: none;
}
.que-overlay { position: absolute; inset: 0; background: rgba(26,10,10,0.9); border-radius: 10px; }
.que-content { position: relative; z-index: 2; color: #E8E1D5; }
.que-so { font-family: 'Cormorant Garamond', serif; font-size: 3.5rem; color: #FFD700; font-weight: 700; line-height: 1; }
.que-name { font-size: 1.5rem; color: #fff; font-weight: 700; margin: 10px 0 20px; }
.que-label { color: #C9A84C; font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px; display: block; margin-top: 15px; }

.toast-wrap{position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px}
.my-toast{display:flex;align-items:center;gap:10px;background:#1a1a1a;color:#fff;padding:12px 18px;border-radius:8px;font-size:.9rem;min-width:260px;animation:toastIn .3s ease;box-shadow:0 8px 24px rgba(0,0,0,.2)}
.my-toast.ok{border-left:4px solid #22c55e}
.my-toast.warn{border-left:4px solid #f59e0b}
.my-toast.err{border-left:4px solid #ef4444}
@keyframes toastIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
@keyframes toastOut{to{opacity:0;transform:translateX(110%)}}
</style>

<div class="hero-ai">
    <div class="container" data-aos="zoom-in">
        <span class="badge border border-warning text-warning mb-3 px-3 py-2 rounded-pill">✦ Hệ Thống AI Tư Vấn Thông Minh ✦</span>
        <h1>Trang phục truyền thống<br>dành riêng cho bạn</h1>
        <p class="mt-3">Tư vấn vóc dáng · Phong thủy ngũ hành · Trải nghiệm gieo quẻ</p>
    </div>
</div>

<div class="container" style="margin-top: -40px; position: relative; z-index: 10; padding-bottom: 60px;">

    <div class="ai-card" data-aos="fade-up" id="sec-tu-van">
        <h2 class="ai-card-title">Tư Vấn Vóc Dáng & Phong Cách</h2>
        <form method="GET" action="#sec-tu-van">
            <div class="row g-4 align-items-end justify-content-center">
                <div class="col-md-3">
                    <label class="form-label">Giới tính</label>
                    <select name="gioi_tinh" class="form-select" required>
                        <option value="">-- Chọn --</option>
                        <option value="nam" <?= (($_GET['gioi_tinh']??'')==='nam')?'selected':'' ?>>Nam giới</option>
                        <option value="nu"  <?= (($_GET['gioi_tinh']??'')==='nu') ?'selected':'' ?>>Nữ giới</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Chiều cao (cm)</label>
                    <input type="number" name="chieu_cao" placeholder="VD: 165" class="form-control" min="100" max="250" value="<?= isset($_GET['chieu_cao'])?sach($_GET['chieu_cao']):'' ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cân nặng (kg)</label>
                    <input type="number" name="can_nang" placeholder="VD: 55" class="form-control" min="20" max="200" value="<?= isset($_GET['can_nang'])?sach($_GET['can_nang']):'' ?>" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" name="tu_van" value="1" class="btn-gold"><i class="fas fa-magic me-2"></i> Phân Tích</button>
                </div>
            </div>
        </form>

        <?php if ($loi_tuvan): ?>
            <div class="alert alert-danger mt-4 text-center"><i class="fas fa-exclamation-triangle me-2"></i><?= sach($loi_tuvan) ?></div>
        <?php endif; ?>

        <?php if ($ket_qua): ?>
            <div class="row mt-5">
                <div class="col-md-5">
                    <div class="result-box h-100">
                        <div style="font-size: 3rem; margin-bottom: 10px;"><?= $icon ?></div>
                        <h4><?= $ket_qua ?></h4>
                        <p class="text-muted mt-3">Chỉ số BMI: <strong><?= number_format($bmi_value,1) ?></strong></p>
                        <p style="font-size: 1.05rem; line-height: 1.6;"><?= $mo_ta ?></p>
                        <div class="info-chip">Size Gợi Ý: <?= $size_goi_y ?></div>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="mixmatch-box h-100 d-flex flex-column justify-content-center">
                        <h5 style="color:#8B0000; font-family:'Cormorant Garamond',serif; font-weight:700; font-size:1.5rem;"><i class="fas fa-lightbulb text-warning me-2"></i>Gợi Ý Mix & Match</h5>
                        <p style="font-size:1.1rem; color:#5C0000; margin-top:15px; font-style:italic;">"<?= $mix_match ?>"</p>
                        
                        <div class="mt-4 text-start">
                            <h6 style="font-weight:700; color:#1A0A0A; border-bottom: 2px solid #E8E1D5; padding-bottom:8px; margin-bottom:15px;">✨ Phù hợp nhất dành cho bạn:</h6>
                            <div class="row g-3">
                                <?php if ($result_sp && $result_sp->num_rows > 0): ?>
                                    <?php while ($row = $result_sp->fetch_assoc()): ?>
                                        <div class="col-4">
                                            <div class="prod-card">
                                                <div class="prod-img-wrap" style="height: 160px;">
                                                    <img src="image/<?= sach($row['duong_dan']) ?>" onerror="this.src='https://placehold.co/200x300/FAF6EE/8B0000?text=SP'" class="prod-img">
                                                    <div class="prod-overlay">
                                                        <a href="sanpham.php?id=<?= $row['id'] ?>" class="prod-action-btn" title="Xem chi tiết"><i class="fas fa-eye"></i></a>
                                                        <button type="button" class="prod-action-btn" onclick="themVaoGio(<?= $row['id'] ?>)" title="Thêm giỏ hàng"><i class="fas fa-cart-plus"></i></button>
                                                    </div>
                                                </div>
                                                <div class="prod-info p-2">
                                                    <a href="sanpham.php?id=<?= $row['id'] ?>" class="prod-name text-truncate" style="font-size: 0.9rem;"><?= sach($row['ten_vi']) ?></a>
                                                    <div class="prod-price" style="font-size: 1rem;"><?= number_format($row['gia_ban']) ?>đ</div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="col-12 text-center text-muted"><i class="fas fa-box-open mb-2 d-block"></i>Chưa tìm thấy sản phẩm phù hợp.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="ai-card" data-aos="fade-up" id="sec-boi-mau">
        <h2 class="ai-card-title">Tra Cứu Màu Sắc Phong Thủy</h2>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form method="GET" action="#sec-boi-mau">
                    <label class="form-label text-center d-block">Nhập năm sinh (Âm lịch/Dương lịch)</label>
                    <div class="input-group mt-2 mb-3">
                        <input type="number" name="nam_sinh" placeholder="VD: 1999" class="form-control" min="1920" max="2024" value="<?= isset($_GET['nam_sinh'])?sach($_GET['nam_sinh']):'' ?>" required>
                        <button type="submit" name="xem_boi" value="1" class="btn btn-danger" style="background:#8B0000; border:none; padding:0 25px;"><i class="fas fa-yin-yang"></i> Xem</button>
                    </div>
                </form>

                <?php if ($loi_boi): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= sach($loi_boi) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($boi_mau): ?>
            <div class="result-box mt-4 p-4 text-center" style="background: linear-gradient(135deg, #1A0A0A, #3D0000); border: none;">
                <p style="color: #C9A84C; letter-spacing: 2px; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 5px;">Bản mệnh của bạn</p>
                <h3 style="font-family: 'Cormorant Garamond', serif; color: #FFD700; font-weight: 700; font-size: 2.2rem;"><?= $boi_mau ?></h3>
                
                <div class="mt-4 mb-3">
                    <?php
                    $mau_css = ['Trắng ngà'=>'#F5F0E8','Vàng ánh kim'=>'#D4A017','Bạc xám'=>'#A8A9AD','Đen huyền'=>'#1a1a2e','Xanh navy'=>'#003153','Xanh biển'=>'#0284C7','Xanh lá'=>'#2E7D32','Xanh ngọc'=>'#00897B','Xanh mint'=>'#4CAF50','Đỏ son'=>'#B71C1C','Hồng cánh sen'=>'#E91E63','Cam đất'=>'#E64A19','Vàng đất'=>'#F57F17','Nâu trầm'=>'#5D4037','Be'=>'#D2B48C'];
                    foreach ($mau_hop as $mt):
                        $css = $mau_css[$mt] ?? '#ccc';
                        $textColor = in_array($mt, ['Trắng ngà', 'Be', 'Bạc xám']) ? '#1A0A0A' : '#FFF';
                    ?>
                        <span class="mau-chip" style="background:<?= $css ?>; color: <?= $textColor ?>;"><?= $mt ?></span>
                    <?php endforeach; ?>
                </div>
                <p style="color: #E8E1D5; font-style: italic; line-height: 1.6; font-size: 1.05rem;">"<?= sach($boi_loi_chuc) ?>"</p>
            </div>

            <?php if (!empty($sp_phong_thuy)): ?>
            <div class="mt-5">
                <h4 style="font-family:'Cormorant Garamond',serif; color:#8B0000; text-align:center; font-weight:bold; margin-bottom: 20px;">Trang Phục Hợp Mệnh Phát Tài Dành Cho Bạn</h4>
                <div class="row g-4 justify-content-center">
                    <?php foreach ($sp_phong_thuy as $sp): ?>
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="prod-card">
                            <div class="prod-img-wrap" style="height: 180px;">
                                <img src="image/<?= sach($sp['duong_dan']) ?>" onerror="this.src='https://placehold.co/200x300/FAF6EE/8B0000?text=SP'" class="prod-img">
                                <div class="prod-overlay">
                                    <a href="sanpham.php?id=<?= $sp['id'] ?>" class="prod-action-btn" title="Xem chi tiết"><i class="fas fa-eye"></i></a>
                                    <button type="button" class="prod-action-btn" onclick="themVaoGio(<?= $sp['id'] ?>)" title="Thêm giỏ hàng"><i class="fas fa-cart-plus"></i></button>
                                </div>
                            </div>
                            <div class="prod-info p-2">
                                <a href="sanpham.php?id=<?= $sp['id'] ?>" class="prod-name text-truncate" style="font-size: 0.85rem;"><?= sach($sp['ten_vi']) ?></a>
                                <div class="prod-price" style="font-size: 1rem;"><?= number_format($sp['gia_ban']) ?>đ</div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="ai-card" data-aos="fade-up">
        <h2 class="ai-card-title">Gieo Quẻ Khởi Sự</h2>
        <div class="xam-container">
            <p class="text-muted mb-4">Nhắm mắt, thành tâm tĩnh lặng và chạm vào ống xăm để nhận thông điệp dành cho bạn.</p>
            <div class="ong-xam" id="ongXam" onclick="lacXam()">
                <div class="xam-stick"></div><div class="xam-stick"></div><div class="xam-stick"></div>
                <div class="xam-stick"></div><div class="xam-stick"></div><div class="xam-stick"></div>
            </div>
            
            <div class="que-result" id="queResult">
                <div class="que-overlay"></div>
                <div class="que-content row text-start align-items-center">
                    <div class="col-md-4 text-center border-end border-warning">
                        <div class="que-so" id="queSo"></div>
                        <div class="badge bg-danger fs-6 mt-2" id="queLoai"></div>
                        <div class="que-name" id="queName"></div>
                    </div>
                    <div class="col-md-5 ps-md-4">
                        <span class="que-label"><i class="fas fa-scroll me-2"></i>Lời Quẻ</span>
                        <p class="mb-3" id="queLoi" style="font-size: 1.1rem; font-style: italic;"></p>
                        
                        <span class="que-label"><i class="fas fa-briefcase me-2"></i>Sự nghiệp & Tài lộc</span>
                        <p class="mb-3" id="queSuNghiep"></p>
                        
                        <span class="que-label"><i class="fas fa-lightbulb me-2"></i>Lời khuyên & Trang phục</span>
                        <p class="mb-0 text-warning" id="queLoiKhuyen"></p>
                    </div>
                    <div class="col-md-3 text-center border-start border-warning" id="queProductWrap">
                        <p style="color:#C9A84C; font-size:0.85rem; font-weight:bold; margin-bottom:10px; text-transform:uppercase;">Vật Phẩm Cát Tường</p>
                        <div id="queProductBox">
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include 'resources/views/layouts/footer.php'; ?>

<div class="toast-wrap" id="toastWrap"></div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    AOS.init({ once: true, offset: 50 });
});

// XỬ LÝ TOAST THÔNG BÁO
function showToast(msg, type='ok') {
    const icons = {ok:'fa-check-circle',warn:'fa-exclamation-circle',err:'fa-times-circle'};
    const wrap  = document.getElementById('toastWrap');
    const t     = document.createElement('div');
    t.className = 'my-toast ' + type;
    t.innerHTML = `<i class="fas ${icons[type]}\"></i><span>${msg}</span>`;
    wrap.appendChild(t);
    setTimeout(() => { t.style.animation='toastOut .3s ease forwards'; setTimeout(()=>t.remove(),300); }, 3000);
}

// XỬ LÝ THÊM VÀO GIỎ HÀNG
async function themVaoGio(id_sp) {
    <?php if(($_SESSION['vai_tro'] ?? $_SESSION['user']['role'] ?? '') === 'Quản trị viên'): ?>
    showToast('Chế độ Admin: Không thể thêm vào giỏ hàng!', 'warn');
    return;
    <?php endif; ?>
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
            showToast('Vui lòng đăng nhập để mua hàng!', 'warn');
            return;
        }
        if (data.success) {
            document.querySelectorAll('#cartBadge, .cart-badge').forEach(b => b.textContent = data.cart_count);
            showToast('Đã thêm sản phẩm vào giỏ hàng!', 'ok');
        } else {
            showToast(data.message || 'Có lỗi xảy ra', 'warn');
        }
    } catch(e) { showToast('Lỗi kết nối máy chủ', 'err'); }
}

// XIN XĂM (CÓ TỪ KHÓA MÀU SẮC ĐỂ FETCH SẢN PHẨM)
const QUE = [
    { so:1,  loai:'Thượng Thượng', ten:'Quẻ Đại Cát',    loi:'Trời đất hanh thông, vạn sự như ý. Mọi việc bạn đang theo đuổi đều sẽ thành công rực rỡ.', su_nghiep:'Vận may đang đến rất gần. Hãy mạnh dạn tiến lên, cơ hội tốt sẽ không chờ đợi.', loi_khuyen:'Mặc Áo dài đỏ son hoặc vàng rực. Ngôi sao may mắn đang chiếu rọi trên bạn!', keyword:'Đỏ' },
    { so:7,  loai:'Thượng',        ten:'Quẻ Cát Tường',  loi:'Mây tan trăng hiện, vận hội đang đến. Khó khăn qua đi, bình yên và hạnh phúc ở phía trước.', su_nghiep:'Công việc hanh thông. Nên nhờ người bề trên chỉ dẫn sẽ thêm thuận lợi.', loi_khuyen:'Mặc Áo tứ thân xanh ngọc. Giữ tâm bình thản, quý nhân đang đứng bên bạn!', keyword:'Xanh' },
    { so:15, loai:'Trung Bình',    ten:'Quẻ Bình Thường',loi:'Bước đi từng bước, chớ vội vàng. Mọi việc cần thêm thời gian và kiên nhẫn mới thành tựu.', su_nghiep:'Vận trình bình ổn. Nên tích lũy thực lực và chờ thời cơ chín muồi.', loi_khuyen:'Mặc Áo dài trắng ngà. Kiên trì là chìa khóa — hạt giống tốt rồi cũng sẽ nở hoa!', keyword:'Trắng' },
    { so:33, loai:'Trung Bình',    ten:'Quẻ Chờ Đợi',    loi:'Nửa mây nửa nắng, vận hội chưa rõ. Cần thêm nỗ lực và tu tâm dưỡng đức.', su_nghiep:'Có thể gặp trở ngại nhỏ. Cẩn thận trong lời nói và quyết định.', loi_khuyen:'Mặc Áo Tấc màu xanh biển. Hãy học cách buông bỏ, nhẹ nhàng hơn, hạnh phúc hơn!', keyword:'Xanh' },
    { so:42, loai:'Hạ',            ten:'Quẻ Thử Thách',  loi:'Gió ngược chiều, bước đường gian nan. Cần thận trọng trong mọi quyết định lớn.', su_nghiep:'Chưa phải lúc mạo hiểm. Tránh đầu tư lớn trong giai đoạn này.', loi_khuyen:'Mặc Áo đen cách tân giúp giữ vững tinh thần. Nhẫn nại là vũ khí tốt nhất, mưa rồi sẽ tạnh!', keyword:'Đen' },
    { so:56, loai:'Thượng Thượng', ten:'Quẻ Hồng Phúc',  loi:'Phúc lộc song toàn, tình duyên khởi sắc. Thời điểm tuyệt vời để bắt đầu điều mới.', su_nghiep:'Vận may đỉnh cao! Mọi kế hoạch đều có khả năng thành công cao.', loi_khuyen:'Mặc Áo Nhật Bình sắc hồng. Đây là thời điểm vàng, hãy sống hết mình!', keyword:'Hồng' },
    { so:68, loai:'Trung Bình',    ten:'Quẻ Bình An',    loi:'Sóng lặng gió yên, cuộc sống bình yên. Không có biến cố lớn, gia đình thuận hòa.', su_nghiep:'Công việc ổn định. Đây là lúc tích lũy và xây dựng nền tảng vững chắc.', loi_khuyen:'Mặc Áo giao lĩnh màu lục. Trân trọng những gì đang có, bình yên chính là hạnh phúc!', keyword:'Lục' },
    { so:88, loai:'Thượng',        ten:'Quẻ Song Hỉ',    loi:'Đôi hỉ lâm môn, may mắn kép đến. Có tin vui cả trong tình duyên lẫn sự nghiệp.', su_nghiep:'Cơ hội thăng tiến đang gõ cửa. Hãy chuẩn bị thật tốt để nắm bắt.', loi_khuyen:'Mặc Áo dài đỏ viền vàng. Hãy mở lòng đón nhận điều tốt đẹp, hạnh phúc đang đến!', keyword:'Đỏ' },
    { so:99, loai:'Đại Kiết',      ten:'Quẻ Đại Phát',   loi:'Rồng bay phượng múa, vận đại phát tài. Quẻ hiếm gặp nhất, báo hiệu vận may phi thường.', su_nghiep:'Tài lộc dồi dào, sự nghiệp thăng tiến vượt bậc ngoài mong đợi.', loi_khuyen:'Mặc Long Bào hoặc trang phục vàng gold hoàng gia. Bạn đang ở đỉnh vận may!', keyword:'Vàng' }
];

let dangLac = false;
function lacXam() {
    if (dangLac) return;
    dangLac = true;
    const ong = document.getElementById('ongXam');
    document.getElementById('queResult').style.display = 'none';
    ong.classList.add('shaking');
    
    setTimeout(() => {
        ong.classList.remove('shaking');
        const sticks = ong.querySelectorAll('.xam-stick');
        const idx = Math.floor(Math.random() * sticks.length);
        sticks[idx].classList.add('falling');
        
        setTimeout(async () => {
            sticks[idx].classList.remove('falling');
            const que = QUE[Math.floor(Math.random() * QUE.length)];
            
            // CẬP NHẬT GIAO DIỆN QUẺ XĂM
            document.getElementById('queSo').textContent       = 'Số ' + que.so;
            document.getElementById('queName').textContent     = que.ten;
            document.getElementById('queLoi').textContent      = '"' + que.loi + '"';
            document.getElementById('queSuNghiep').textContent = que.su_nghiep;
            document.getElementById('queLoiKhuyen').textContent= que.loi_khuyen;
            document.getElementById('queLoai').textContent     = que.loai;
            
            // GỌI API LẤY 1 SẢN PHẨM THEO MÀU CỦA QUẺ XĂM ĐÓ
            document.getElementById('queProductBox').innerHTML = '<i class="fas fa-spinner fa-spin text-warning fs-3 mt-4"></i>';
            try {
                const spRes = await fetch(`tuvan.php?action=random_sp&keyword=${encodeURIComponent(que.keyword)}`);
                const spData = await spRes.json();
                
                if (spData.success && spData.data) {
                    const sp = spData.data;
                    document.getElementById('queProductBox').innerHTML = `
                        <div class="prod-card mx-auto" style="max-width: 200px; border-color:#C9A84C; background:rgba(255,255,255,0.1);">
                            <div class="prod-img-wrap" style="height: 180px;">
                                <img src="image/${sp.duong_dan}" onerror="this.src='https://placehold.co/180x250/FAF6EE/8B0000?text=SP'" class="prod-img">
                                <div class="prod-overlay">
                                    <a href="sanpham.php?id=${sp.id}" class="prod-action-btn" title="Xem chi tiết"><i class="fas fa-eye"></i></a>
                                </div>
                            </div>
                            <div class="prod-info p-2 bg-white text-dark">
                                <span class="prod-name text-truncate" style="font-size: 0.85rem; color:#1A0A0A;">${sp.ten_vi}</span>
                                <div class="prod-price" style="font-size: 1rem;">${new Intl.NumberFormat('vi-VN').format(sp.gia_ban)}đ</div>
                                <button type="button" class="btn btn-sm btn-danger mt-2 w-100" onclick="themVaoGio(${sp.id})"><i class="fas fa-cart-plus me-1"></i>Thêm Giỏ</button>
                            </div>
                        </div>
                    `;
                }
            } catch(e) {
                document.getElementById('queProductBox').innerHTML = '<span class="text-muted">Không tải được vật phẩm</span>';
            }

            const r = document.getElementById('queResult');
            r.style.display = 'block';
            r.scrollIntoView({ behavior:'smooth', block:'center' });
            dangLac = false;
        }, 500);
    }, 1000);
}
</script>
</body>
</html>