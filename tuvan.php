<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "config/db.php";
session_start();

// ═══ HÀM TIỆN ÍCH ═══
function sach($str) {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

function them_gio_hang($conn, $id_sp) {
    $id_sp = (int)$id_sp;
    if ($id_sp <= 0) return ['ok' => false, 'msg' => 'Sản phẩm không hợp lệ'];

    $sql = $conn->prepare("SELECT id, ten_vi, gia_ban FROM san_pham WHERE id = ?");
    $sql->bind_param("i", $id_sp);
    $sql->execute();
    $sp = $sql->get_result()->fetch_assoc();
    if (!$sp) return ['ok' => false, 'msg' => 'Không tìm thấy sản phẩm'];

    if (!isset($_SESSION['gio_hang'])) $_SESSION['gio_hang'] = [];
    if (isset($_SESSION['gio_hang'][$id_sp])) {
        $_SESSION['gio_hang'][$id_sp]['so_luong']++;
    } else {
        $_SESSION['gio_hang'][$id_sp] = [
            'id'       => $id_sp,
            'ten'      => $sp['ten_vi'],
            'gia'      => $sp['gia_ban'],
            'so_luong' => 1
        ];
    }
    return ['ok' => true, 'msg' => 'Đã thêm vào giỏ hàng!'];
}

function tong_gio_hang() {
    if (empty($_SESSION['gio_hang'])) return 0;
    return array_sum(array_column($_SESSION['gio_hang'], 'so_luong'));
}

// ═══ XỬ LÝ AJAX THÊM GIỎ HÀNG ═══
if (isset($_GET['action']) && $_GET['action'] === 'them_gio') {
    header('Content-Type: application/json');
    $res = them_gio_hang($conn, $_GET['id'] ?? 0);
    $res['tong'] = tong_gio_hang();
    echo json_encode($res);
    exit;
}

// ═══ XỬ LÝ XÓA / CẬP NHẬT GIỎ HÀNG ═══
if (isset($_POST['action_gio'])) {
    $id = (int)($_POST['id_sp'] ?? 0);
    if ($_POST['action_gio'] === 'xoa' && isset($_SESSION['gio_hang'][$id])) {
        unset($_SESSION['gio_hang'][$id]);
    }
    if ($_POST['action_gio'] === 'cap_nhat' && isset($_SESSION['gio_hang'][$id])) {
        $sl = (int)$_POST['so_luong'];
        if ($sl <= 0) unset($_SESSION['gio_hang'][$id]);
        else $_SESSION['gio_hang'][$id]['so_luong'] = min($sl, 99);
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ═══ TƯ VẤN SIZE ═══
$ket_qua = $dang_nguoi = $mo_ta = $size_goi_y = $mix_match = "";
$bmi_value = 0; $result = null; $loi_tuvan = "";

if (isset($_GET['tu_van'])) {
    $gioi_tinh = sach($_GET['gioi_tinh'] ?? '');
    $chieu_cao = (float)($_GET['chieu_cao'] ?? 0);
    $can_nang  = (float)($_GET['can_nang']  ?? 0);

    // VALIDATION
    if (!in_array($gioi_tinh, ['nam', 'nu'])) {
        $loi_tuvan = "Vui lòng chọn giới tính hợp lệ.";
    } elseif ($chieu_cao < 100 || $chieu_cao > 250) {
        $loi_tuvan = "Chiều cao phải từ 100cm đến 250cm.";
    } elseif ($can_nang < 20 || $can_nang > 300) {
        $loi_tuvan = "Cân nặng phải từ 20kg đến 300kg.";
    } else {
        $bmi_value = $can_nang / pow($chieu_cao / 100, 2);

        if ($bmi_value < 18.5) {
            $dang_nguoi = "gay";
            $ket_qua    = "Dáng thon mảnh mai 🌿";
            $mo_ta      = "Vóc dáng thanh thoát! Thiết kế eo nổi bật và họa tiết ngang sẽ tôn lên nét đẹp của bạn.";
            $size_goi_y = "XS – S";
            $mix_match  = "Áo dài cổ thuyền + thắt lưng vàng đồng + bông tai thả dài";
            $icon = "🌸"; $mau = "#fff8e1"; $mau_vien = "#C9A84C";
        } elseif ($bmi_value < 25) {
            $dang_nguoi = "can_doi";
            $ket_qua    = "Dáng chuẩn lý tưởng ✨";
            $mo_ta      = "Vóc dáng cân đối! Hầu hết thiết kế đều tuyệt vời trên bạn.";
            $size_goi_y = "M – L";
            $mix_match  = "Áo tứ thân cách tân + quần ống rộng + túi cói + dép thêu";
            $icon = "✨"; $mau = "#fdf3e3"; $mau_vien = "#C9A84C";
        } else {
            $dang_nguoi = "day_dan";
            $ket_qua    = "Dáng đầy đặn quyến rũ 💫";
            $mo_ta      = "Vóc dáng đầy đặn thu hút! Thiết kế chữ A và họa tiết dọc sẽ tôn lên nét đẹp.";
            $size_goi_y = "L – XL";
            $mix_match  = "Áo dài suôn dài + giày cao gót + vòng ngọc trai";
            $icon = "💫"; $mau = "#fff8e1"; $mau_vien = "#8B1A1A";
        }

        $stmt = $conn->prepare("SELECT * FROM san_pham WHERE gioi_tinh = ? AND dang_nguoi = ? LIMIT 6");
        $stmt->bind_param("ss", $gioi_tinh, $dang_nguoi);
        $stmt->execute();
        $result = $stmt->get_result();
    }
}

// ═══ XEM BÓI MÀU ═══
$boi_mau = $boi_loi_chuc = $ngu_hanh = ""; $mau_hop = []; $loi_boi = "";

if (isset($_GET['xem_boi'])) {
    $nam_sinh = (int)($_GET['nam_sinh'] ?? 0);
    if ($nam_sinh < 1920 || $nam_sinh > 2010) {
        $loi_boi = "Năm sinh phải từ 1920 đến 2010.";
    } else {
        $can = $nam_sinh % 10;
        $hanh_map = [0=>'Kim',1=>'Kim',2=>'Thủy',3=>'Thủy',4=>'Mộc',5=>'Mộc',6=>'Hỏa',7=>'Hỏa',8=>'Thổ',9=>'Thổ'];
        $ngu_hanh = $hanh_map[$can];
        switch ($ngu_hanh) {
            case 'Kim':
                $mau_hop = ['Trắng ngà','Vàng ánh kim','Bạc xám'];
                $boi_mau = "Mệnh Kim – Trắng & Vàng";
                $boi_loi_chuc = "Màu trắng và vàng kim mang lại tinh khiết và thịnh vượng. Năm nay vận may rực rỡ như ánh vàng! 🌟";
                break;
            case 'Thủy':
                $mau_hop = ['Đen huyền','Xanh navy','Tím than'];
                $boi_mau = "Mệnh Thủy – Đen & Xanh đậm";
                $boi_loi_chuc = "Màu đen và xanh đậm tượng trưng sâu sắc và trí tuệ. Duyên may đến như dòng nước chảy mãi! 💙";
                break;
            case 'Mộc':
                $mau_hop = ['Xanh lá','Xanh ngọc','Xanh mint'];
                $boi_mau = "Mệnh Mộc – Xanh lá & Ngọc";
                $boi_loi_chuc = "Màu xanh lá tượng trưng sinh sôi và phát triển. Cuộc sống tươi tốt như cây cối mùa xuân! 🌿";
                break;
            case 'Hỏa':
                $mau_hop = ['Đỏ son','Hồng cánh sen','Cam đất'];
                $boi_mau = "Mệnh Hỏa – Đỏ & Hồng";
                $boi_loi_chuc = "Màu đỏ và hồng mang ngọn lửa nhiệt huyết. Năm nay bạn tỏa sáng như ánh lửa! 🔥";
                break;
            case 'Thổ':
                $mau_hop = ['Vàng đất','Nâu trầm','Be'];
                $boi_mau = "Mệnh Thổ – Vàng đất & Nâu";
                $boi_loi_chuc = "Màu vàng đất và nâu trầm tượng trưng vững chắc và bền bỉ. Gia đình an khang! 🌻";
                break;
        }
    }
}

include 'resources/views/layouts/header.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>AI Tư Vấn Trang Phục Truyền Thống</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Be+Vietnam+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Be Vietnam Pro', sans-serif; background: #1a0a0a; }

        :root {
            --do:       #8B1A1A;
            --do-dam:   #5C0F0F;
            --vang:     #C9A84C;
            --vang-nhat:#E8C96A;
            --vang-dam: #A07830;
            --kem:      #FDF8F0;
        }

        /* HERO */
        .hero {
            background: linear-gradient(135deg, #5C0F0F, #8B1A1A, #A52020);
            color: white; padding: 70px 0 110px;
            clip-path: ellipse(100% 80% at 50% 20%);
            text-align: center; position: relative; overflow: hidden;
        }
        .hero::before {
            content:''; position:absolute; inset:0;
            background: repeating-linear-gradient(90deg, transparent, transparent 60px, rgba(201,168,76,0.07) 60px, rgba(201,168,76,0.07) 61px);
        }
        .hero-badge {
            display:inline-block; background:rgba(201,168,76,0.2);
            border:1px solid var(--vang); border-radius:20px;
            padding:6px 20px; font-size:13px; font-weight:600;
            color:var(--vang-nhat); letter-spacing:1px; margin-bottom:16px;
        }
        .hero h1 { font-family:'Playfair Display',serif; font-size:2.6rem; font-weight:800; line-height:1.3; }
        .hero h1 span { color:var(--vang-nhat); }
        .hero p { font-size:1rem; opacity:.85; color:#f5e6c8; margin-top:10px; }

        /* GIỎ HÀNG NỔI */
        .gio-hang-float {
            position:fixed; top:20px; right:20px; z-index:999;
            background:linear-gradient(135deg,var(--vang-dam),var(--vang));
            color:var(--do-dam); border:none; border-radius:50px;
            padding:10px 20px; font-weight:800; font-size:14px;
            box-shadow:0 4px 20px rgba(201,168,76,0.5);
            cursor:pointer; transition:all .2s; text-decoration:none;
        }
        .gio-hang-float:hover { transform:translateY(-2px); box-shadow:0 8px 28px rgba(201,168,76,0.6); color:var(--do-dam); }
        .badge-sl {
            background:var(--do); color:white;
            border-radius:50%; width:22px; height:22px;
            display:inline-flex; align-items:center; justify-content:center;
            font-size:11px; font-weight:800; margin-left:6px;
        }

        /* TABS */
        .tab-nav {
            display:flex; gap:8px; margin:-30px 0 28px;
            justify-content:center; flex-wrap:wrap; position:relative; z-index:10;
        }
        .tab-btn {
            background:var(--kem); border:2px solid var(--vang);
            border-radius:24px; padding:10px 22px; font-size:13px;
            font-weight:700; color:var(--do); cursor:pointer;
            transition:all .2s; text-decoration:none;
            box-shadow:0 2px 8px rgba(0,0,0,.2);
        }
        .tab-btn:hover,.tab-btn.active {
            background:linear-gradient(135deg,var(--vang-dam),var(--vang));
            color:var(--do-dam); border-color:transparent;
            box-shadow:0 4px 16px rgba(201,168,76,.4); transform:translateY(-2px);
        }

        /* CARD */
        .main-card {
            background:var(--kem); border-radius:20px;
            box-shadow:0 8px 40px rgba(0,0,0,.3), inset 0 1px 0 rgba(201,168,76,.3);
            padding:32px; margin-bottom:24px;
            border:1px solid rgba(201,168,76,.2);
        }
        .card-title-section {
            font-family:'Playfair Display',serif; font-size:1.15rem;
            font-weight:700; color:var(--do); margin-bottom:20px;
            display:flex; align-items:center; gap:10px;
        }
        .card-title-section::after {
            content:''; flex:1; height:2px;
            background:linear-gradient(90deg,var(--vang),transparent); border-radius:2px;
        }
        .gold-divider { height:2px; background:linear-gradient(90deg,transparent,var(--vang),transparent); margin:8px 0 20px; border:none; }

        /* FORM */
        .form-label-custom { font-size:11px; font-weight:700; color:var(--vang-dam); text-transform:uppercase; letter-spacing:.8px; margin-bottom:6px; }
        .form-control,.form-select { border-radius:12px; border:1.5px solid rgba(201,168,76,.4); padding:12px 16px; font-size:14px; background:#fffdf8; color:#2a0a0a; }
        .form-control:focus,.form-select:focus { border-color:var(--vang); box-shadow:0 0 0 3px rgba(201,168,76,.15); }
        .form-control.is-invalid { border-color:#dc3545; }

        .btn-gold {
            background:linear-gradient(135deg,var(--vang-dam),var(--vang),var(--vang-nhat));
            color:var(--do-dam); border:none; border-radius:14px;
            padding:14px; font-weight:800; font-size:15px; width:100%;
            transition:all .2s; box-shadow:0 4px 16px rgba(201,168,76,.3); cursor:pointer;
        }
        .btn-gold:hover { transform:translateY(-2px); box-shadow:0 8px 28px rgba(201,168,76,.5); }

        /* ALERT LỖI */
        .alert-do { background:#fff0f0; border:1px solid #f5c6c6; border-left:4px solid var(--do); border-radius:12px; padding:14px 18px; color:#5c0f0f; font-size:14px; margin-top:16px; }

        /* KẾT QUẢ BMI */
        .result-box { border-radius:16px; padding:24px; border-left:5px solid; margin-top:24px; }
        .result-box h4 { font-family:'Playfair Display',serif; font-weight:700; font-size:1.15rem; margin-bottom:6px; color:var(--do-dam); }
        .info-chip { display:inline-flex; align-items:center; gap:6px; background:linear-gradient(135deg,var(--vang-dam),var(--vang)); color:var(--do-dam); border-radius:12px; padding:8px 16px; font-size:13px; font-weight:700; margin:6px 4px 0 0; }
        .mixmatch-box { background:linear-gradient(135deg,#fff8e1,var(--kem)); border-radius:16px; padding:20px 24px; margin-top:16px; border:1px dashed var(--vang); }
        .mixmatch-box h6 { color:var(--do); font-weight:700; margin-bottom:8px; }

        /* SẢN PHẨM */
        .product-card { background:white; border-radius:16px; overflow:hidden; box-shadow:0 2px 16px rgba(0,0,0,.1); transition:transform .2s, box-shadow .2s; height:100%; border:1px solid rgba(201,168,76,.2); }
        .product-card:hover { transform:translateY(-6px); box-shadow:0 12px 32px rgba(139,26,26,.2); }
        .product-card img { width:100%; height:220px; object-fit:cover; }
        .product-card .card-body { padding:16px; text-align:center; }
        .product-card h6 { font-weight:700; font-size:14px; color:#1a0a0a; margin-bottom:6px; line-height:1.4; }
        .product-card .gia { color:var(--do); font-weight:800; font-size:16px; margin-bottom:12px; }
        .btn-gio { background:linear-gradient(135deg,var(--vang-dam),var(--vang)); color:var(--do-dam); border:none; border-radius:10px; padding:8px 20px; font-size:13px; font-weight:700; text-decoration:none; display:inline-block; transition:all .2s; cursor:pointer; }
        .btn-gio:hover { transform:scale(1.05); box-shadow:0 4px 14px rgba(201,168,76,.4); color:var(--do-dam); }
        .btn-gio.added { background:linear-gradient(135deg,#2e7d32,#43a047); color:white; }

        /* BÓI MÀU */
        .boi-result { background:linear-gradient(135deg,var(--do-dam),var(--do)); border-radius:20px; padding:28px; margin-top:24px; text-align:center; border:1px solid var(--vang); }
        .boi-result h3 { font-family:'Playfair Display',serif; font-weight:800; color:var(--vang-nhat); }
        .boi-result p { color:#f5e6c8; }
        .mau-chip { display:inline-block; border-radius:30px; padding:8px 20px; font-size:13px; font-weight:700; margin:6px 4px; border:2px solid rgba(201,168,76,.5); color:white; text-shadow:0 1px 2px rgba(0,0,0,.4); }
        .boi-loi-chuc { background:rgba(255,255,255,.1); border-radius:14px; padding:16px 20px; margin-top:16px; font-size:14px; line-height:1.7; color:#f5e6c8; border-left:4px solid var(--vang); text-align:left; }

        /* MINI GAME */
        .color-option { width:52px; height:52px; border-radius:50%; border:3px solid transparent; cursor:pointer; transition:all .2s; display:inline-block; margin:6px; }
        .color-option:hover { transform:scale(1.15); border-color:var(--vang); }
        .color-option.selected { border-color:var(--vang); transform:scale(1.2); box-shadow:0 4px 16px rgba(201,168,76,.5); }
        .game-result { background:linear-gradient(135deg,#fff8e1,var(--kem)); border-radius:14px; padding:16px 20px; margin-top:16px; font-size:14px; color:var(--do-dam); font-weight:600; border-left:4px solid var(--vang); display:none; }

        /* XIN XĂM */
        .xam-container { text-align:center; padding:10px 0; }
        .ong-xam { width:90px; height:160px; background:linear-gradient(180deg,#5C0F0F,#8B1A1A); border-radius:8px 8px 4px 4px; margin:0 auto 24px; position:relative; border:2px solid var(--vang); box-shadow:0 8px 24px rgba(0,0,0,.4); cursor:pointer; transition:transform .1s; }
        .ong-xam:hover { transform:scale(1.03); }
        .ong-xam::before { content:'籤'; position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); font-size:48px; color:var(--vang); opacity:.6; }
        .xam-stick { width:6px; height:120px; background:linear-gradient(180deg,#D4A017,#8B6914,#D4A017); border-radius:3px 3px 2px 2px; position:absolute; bottom:8px; transform-origin:bottom center; transition:transform .3s; box-shadow:1px 0 3px rgba(0,0,0,.3); }
        .xam-stick:nth-child(1){left:12px;transform:rotate(-28deg);}
        .xam-stick:nth-child(2){left:20px;transform:rotate(-18deg);}
        .xam-stick:nth-child(3){left:29px;transform:rotate(-8deg);}
        .xam-stick:nth-child(4){left:38px;transform:rotate(2deg);}
        .xam-stick:nth-child(5){left:47px;transform:rotate(12deg);}
        .xam-stick:nth-child(6){left:56px;transform:rotate(22deg);}
        .xam-stick:nth-child(7){left:64px;transform:rotate(32deg);}
        @keyframes shake { 0%,100%{transform:rotate(0) translateX(0)} 15%{transform:rotate(-8deg) translateX(-4px)} 30%{transform:rotate(8deg) translateX(4px)} 45%{transform:rotate(-6deg) translateX(-3px)} 60%{transform:rotate(6deg) translateX(3px)} 75%{transform:rotate(-3deg) translateX(-2px)} 90%{transform:rotate(3deg) translateX(2px)} }
        .ong-xam.shaking { animation:shake .8s ease; }
        @keyframes fallOut { 0%{transform:translateY(0) rotate(0); opacity:1} 100%{transform:translateY(60px) rotate(45deg); opacity:0} }
        .xam-stick.falling { animation:fallOut .5s ease forwards; }
        .btn-xam { background:linear-gradient(135deg,var(--vang-dam),var(--vang),var(--vang-nhat)); color:var(--do-dam); border:none; border-radius:30px; padding:14px 40px; font-weight:800; font-size:16px; cursor:pointer; transition:all .2s; box-shadow:0 4px 20px rgba(201,168,76,.4); margin-bottom:8px; }
        .btn-xam:hover { transform:translateY(-3px); box-shadow:0 8px 28px rgba(201,168,76,.6); }
        .que-result { background:linear-gradient(135deg,var(--do-dam),#3a0a0a); border:1px solid var(--vang); border-radius:20px; padding:28px; margin-top:24px; display:none; position:relative; overflow:hidden; }
        .que-result::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,transparent,var(--vang),transparent); }
        .que-so { font-family:'Playfair Display',serif; font-size:3rem; color:var(--vang-nhat); font-weight:800; margin-bottom:4px; text-shadow:0 0 20px rgba(201,168,76,.5); }
        .que-loai { display:inline-block; border-radius:20px; padding:4px 16px; font-size:13px; font-weight:700; margin-bottom:16px; letter-spacing:.5px; }
        .que-loai.thuong{background:#1565C0;color:#E3F2FD;}
        .que-loai.trung{background:#2E7D32;color:#E8F5E9;}
        .que-loai.ha{background:#5C0F0F;color:#FFCDD2;}
        .que-name { font-family:'Playfair Display',serif; font-size:1.4rem; color:var(--vang-nhat); font-weight:700; margin-bottom:12px; }
        .que-divider { height:1px; background:linear-gradient(90deg,transparent,var(--vang),transparent); margin:16px 0; }
        .que-label { color:var(--vang); font-weight:700; font-size:11px; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px; display:block; }
        .que-value { color:#f5e6c8; margin-bottom:12px; display:block; font-size:14px; line-height:1.7; }
        .btn-xam-lai { background:transparent; border:1.5px solid var(--vang); color:var(--vang); border-radius:20px; padding:8px 24px; font-size:13px; font-weight:700; cursor:pointer; margin-top:16px; transition:all .2s; }
        .btn-xam-lai:hover { background:var(--vang); color:var(--do-dam); }

        /* GIỎ HÀNG MODAL */
        .gio-table th { background:var(--do); color:white; font-size:13px; }
        .gio-table td { vertical-align:middle; font-size:13px; }
        .gio-tong { font-family:'Playfair Display',serif; color:var(--do); font-size:1.3rem; font-weight:700; }
        .btn-dat-hang { background:linear-gradient(135deg,var(--do-dam),var(--do)); color:white; border:none; border-radius:12px; padding:12px 32px; font-weight:700; font-size:15px; width:100%; transition:all .2s; }
        .btn-dat-hang:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(139,26,26,.4); }
        .empty-state { text-align:center; padding:48px; color:#aaa; }

        /* STORY */
        .story-card { background:linear-gradient(135deg,var(--do-dam),var(--do)); border-radius:20px; padding:32px; color:white; margin-bottom:24px; border:1px solid var(--vang); position:relative; overflow:hidden; }
        .story-card::before { content:'❝'; position:absolute; top:10px; right:24px; font-size:80px; color:rgba(201,168,76,.15); font-family:serif; }
        .story-card h5 { font-family:'Playfair Display',serif; color:var(--vang-nhat); margin-bottom:12px; font-size:1.2rem; }
        .story-card p { opacity:.9; font-size:14px; line-height:1.9; color:#f5e6c8; }
    </style>
</head>
<body>

<!-- GIỎ HÀNG NỔI -->
<a href="#" class="gio-hang-float" data-bs-toggle="modal" data-bs-target="#modalGio">
    🛒 Giỏ hàng <span class="badge-sl" id="badgeSl"><?= tong_gio_hang() ?></span>
</a>

<!-- HERO -->
<div class="hero">
    <div class="container" style="position:relative;z-index:1;">
        <div class="hero-badge">✦ Powered by AI · Tư vấn thông minh ✦</div>
        <h1>Trang phục <span>truyền thống</span><br>dành riêng cho bạn</h1>
        <p>Tư vấn vóc dáng · Hợp phong thủy · Mix & Match · Xin xăm gieo quẻ</p>
    </div>
</div>

<div class="container" style="max-width:960px; padding-bottom:60px;">

    <!-- TABS -->
    <div class="tab-nav">
        <a href="#tu-van"    class="tab-btn active">👗 Tư vấn size</a>
        <a href="#boi-mau"   class="tab-btn">🔮 Xem bói màu</a>
        <a href="#mini-game" class="tab-btn">🎨 Chọn màu vui</a>
        <a href="#xin-xam"   class="tab-btn">🎋 Xin xăm gieo quẻ</a>
    </div>

    <!-- ═══ 1. TƯ VẤN SIZE ═══ -->
    <div id="tu-van" class="main-card">
        <div class="card-title-section">👗 Tư vấn size & phong cách</div>
        <hr class="gold-divider">

        <form method="GET" id="formTuVan" novalidate>
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <div class="form-label-custom">Giới tính</div>
                    <select name="gioi_tinh" class="form-select" required>
                        <option value="">Chọn giới tính</option>
                        <option value="nam" <?= (($_GET['gioi_tinh']??'')==='nam')?'selected':'' ?>>Nam</option>
                        <option value="nu"  <?= (($_GET['gioi_tinh']??'')==='nu') ?'selected':'' ?>>Nữ</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="form-label-custom">Chiều cao (cm)</div>
                    <input type="number" name="chieu_cao" placeholder="100 – 250 cm"
                           class="form-control <?= $loi_tuvan?'is-invalid':'' ?>"
                           min="100" max="250" step="0.1"
                           value="<?= isset($_GET['chieu_cao'])?sach($_GET['chieu_cao']):'' ?>" required>
                </div>
                <div class="col-md-3">
                    <div class="form-label-custom">Cân nặng (kg)</div>
                    <input type="number" name="can_nang" placeholder="20 – 300 kg"
                           class="form-control <?= $loi_tuvan?'is-invalid':'' ?>"
                           min="20" max="300" step="0.1"
                           value="<?= isset($_GET['can_nang'])?sach($_GET['can_nang']):'' ?>" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" name="tu_van" value="1" class="btn-gold">✦ Tư vấn ngay</button>
                </div>
            </div>
        </form>

        <?php if ($loi_tuvan): ?>
            <div class="alert-do">⚠️ <?= sach($loi_tuvan) ?></div>
        <?php endif; ?>

        <?php if ($ket_qua): ?>
            <div class="result-box" style="background:<?= $mau ?>;border-color:<?= $mau_vien ?>;">
                <h4><?= $icon ?> <?= $ket_qua ?> &nbsp;·&nbsp; BMI: <?= number_format($bmi_value,1) ?></h4>
                <p style="font-size:14px;margin-bottom:12px;color:#3a1a0a;"><?= $mo_ta ?></p>
                <span class="info-chip">📏 Size gợi ý: <?= $size_goi_y ?></span>
            </div>
            <div class="mixmatch-box">
                <h6>💡 Gợi ý mix & match hôm nay</h6>
                <p style="font-size:14px;color:#5c3010;margin:0;line-height:1.7;"><?= $mix_match ?></p>
            </div>

            <div class="card-title-section mt-4">🛍️ Sản phẩm đề xuất</div>
            <div class="row g-3">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="col-md-4">
                            <div class="product-card">
                                <img src="images/<?= sach($row['duong_dan']) ?>"
                                     alt="<?= sach($row['ten_vi']) ?>">
                                <div class="card-body">
                                    <h6><?= sach($row['ten_vi']) ?></h6>
                                    <div class="gia"><?= number_format($row['gia_ban']) ?> đ</div>
                                    <button class="btn-gio" onclick="themGio(this, <?= (int)$row['id'] ?>)">
                                        🛒 Thêm vào giỏ
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">🔍 Không có sản phẩm phù hợp.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ═══ 2. XEM BÓI MÀU ═══ -->
    <div id="boi-mau" class="main-card">
        <div class="card-title-section">🔮 Xem bói màu hợp mệnh</div>
        <hr class="gold-divider">

        <form method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <div class="form-label-custom">Năm sinh (1920 – 2010)</div>
                    <input type="number" name="nam_sinh" placeholder="VD: 1999"
                           class="form-control <?= $loi_boi?'is-invalid':'' ?>"
                           min="1920" max="2010"
                           value="<?= isset($_GET['nam_sinh'])?sach($_GET['nam_sinh']):'' ?>">
                </div>
                <div class="col-md-6">
                    <button type="submit" name="xem_boi" value="1" class="btn-gold">🔮 Xem bói ngay</button>
                </div>
            </div>
        </form>

        <?php if ($loi_boi): ?>
            <div class="alert-do">⚠️ <?= sach($loi_boi) ?></div>
        <?php endif; ?>

        <?php if ($boi_mau): ?>
            <div class="boi-result">
                <p style="font-size:12px;opacity:.6;margin-bottom:4px;color:var(--vang-nhat);">Ngũ hành của bạn</p>
                <h3>⚡ Mệnh <?= $ngu_hanh ?></h3>
                <p style="font-size:14px;margin-bottom:16px;"><?= $boi_mau ?></p>
                <div>
                    <?php
                    $mau_css = ['Trắng ngà'=>'#F5F0E8','Vàng ánh kim'=>'#D4A017','Bạc xám'=>'#A8A9AD','Đen huyền'=>'#1a1a2e','Xanh navy'=>'#003153','Tím than'=>'#4B0082','Xanh lá'=>'#2E7D32','Xanh ngọc'=>'#00897B','Xanh mint'=>'#4CAF50','Đỏ son'=>'#B71C1C','Hồng cánh sen'=>'#E91E63','Cam đất'=>'#E64A19','Vàng đất'=>'#F57F17','Nâu trầm'=>'#5D4037','Be'=>'#A0896A'];
                    foreach ($mau_hop as $mt):
                        $css = $mau_css[$mt] ?? '#ccc';
                    ?>
                        <span class="mau-chip" style="background:<?= $css ?>;"><?= $mt ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="boi-loi-chuc"><?= sach($boi_loi_chuc) ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- ═══ 3. MINI GAME ═══ -->
    <div id="mini-game" class="main-card">
        <div class="card-title-section">🎨 Mini game – Chọn màu áo yêu thích</div>
        <hr class="gold-divider">
        <p style="font-size:14px;color:#7a5c3a;">Hãy chọn màu bạn thích nhất — chúng tôi sẽ nói lên tính cách của bạn! 🌸</p>
        <div class="text-center mt-3">
            <div class="color-option" style="background:#8B1A1A;" data-msg="Bạn đầy nhiệt huyết và đam mê! Màu đỏ son trên áo dài sẽ làm bạn tỏa sáng rực rỡ. 🔥" onclick="chonMau(this)"></div>
            <div class="color-option" style="background:#C9A84C;" data-msg="Bạn sang trọng và quý phái! Áo dài vàng gold chính là lựa chọn hoàng gia dành cho bạn. 👑" onclick="chonMau(this)"></div>
            <div class="color-option" style="background:#9C27B0;" data-msg="Bạn bí ẩn và quyến rũ! Áo tứ thân tím huyền bí cực kỳ hợp với cá tính của bạn. 💜" onclick="chonMau(this)"></div>
            <div class="color-option" style="background:#1565C0;" data-msg="Bạn điềm tĩnh và sâu sắc! Áo dài xanh navy sang trọng chính là màu của bạn. 💙" onclick="chonMau(this)"></div>
            <div class="color-option" style="background:#2E7D32;" data-msg="Bạn yêu thiên nhiên và tràn đầy sức sống! Áo tứ thân xanh lá tươi mát rất hợp. 🌿" onclick="chonMau(this)"></div>
            <div class="color-option" style="background:#F5F0E8;border:2px solid #C9A84C;" data-msg="Bạn thanh lịch và tinh tế! Áo dài trắng ngà là biểu tượng vẻ đẹp trường tồn. 🤍" onclick="chonMau(this)"></div>
            <div class="color-option" style="background:#5D4037;" data-msg="Bạn trầm ổn và đầy nội tâm! Áo dài nâu trầm toát lên nét đẹp cổ kính tao nhã. 🍂" onclick="chonMau(this)"></div>
            <div class="color-option" style="background:#212121;" data-msg="Bạn cá tính và hiện đại! Áo dài đen cách tân là vũ khí thời trang của bạn. 🖤" onclick="chonMau(this)"></div>
        </div>
        <div class="game-result" id="gameResult"></div>
    </div>

    <!-- ═══ 4. XIN XĂM GIEO QUẺ ═══ -->
    <div id="xin-xam" class="main-card">
        <div class="card-title-section">🎋 Xin xăm gieo quẻ</div>
        <hr class="gold-divider">
        <div class="xam-container">
            <p style="font-size:14px;color:#7a5c3a;margin-bottom:20px;">Thành tâm khấn nguyện, lắc ống xăm và chờ quẻ hiện ra ✨</p>
            <div class="ong-xam" id="ongXam" onclick="lacXam()">
                <div class="xam-stick"></div><div class="xam-stick"></div>
                <div class="xam-stick"></div><div class="xam-stick"></div>
                <div class="xam-stick"></div><div class="xam-stick"></div>
                <div class="xam-stick"></div>
            </div>
            <button class="btn-xam" onclick="lacXam()">🎋 Lắc xăm xin quẻ</button>
            <div style="font-size:12px;color:#9a7a5a;margin-top:8px;font-style:italic;">Nhấn vào ống xăm hoặc nút để gieo quẻ</div>

            <div class="que-result" id="queResult">
                <div class="que-so"  id="queSo"></div>
                <div><span class="que-loai" id="queLoai"></span></div>
                <div class="que-name" id="queName"></div>
                <div class="que-divider"></div>
                <span class="que-label">📜 Lời quẻ</span>
                <span class="que-value" id="queLoi"></span>
                <span class="que-label">💼 Sự nghiệp & May mắn</span>
                <span class="que-value" id="queSuNghiep"></span>
                <span class="que-label">👘 Trang phục hợp mệnh</span>
                <span class="que-value" id="queTrangPhuc"></span>
                <span class="que-label">🌸 Lời khuyên</span>
                <span class="que-value" id="queLoiKhuyen"></span>
                <br>
                <button class="btn-xam-lai" onclick="lacLai()">🔄 Xin quẻ lại</button>
            </div>
        </div>
    </div>

    <!-- STORY -->
    <div class="story-card">
        <h5>🌺 Câu chuyện về áo dài Việt Nam</h5>
        <p>Áo dài – không chỉ là trang phục, đó là linh hồn của người phụ nữ Việt. Từ những tà áo ngũ thân triều Nguyễn đến những thiết kế cách tân ngày nay, áo dài vẫn giữ nguyên vẻ đẹp duyên dáng và nét thanh tao đặc trưng. Mỗi chiếc áo dài là một câu chuyện – về quê hương, về ký ức, về bản sắc dân tộc không thể nào quên. ✨</p>
    </div>

</div>

<!-- ═══ MODAL GIỎ HÀNG ═══ -->
<div class="modal fade" id="modalGio" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px;border:1px solid var(--vang);background:var(--kem);">
            <div class="modal-header" style="background:linear-gradient(135deg,var(--do-dam),var(--do));border-radius:18px 18px 0 0;border:none;">
                <h5 class="modal-title" style="color:var(--vang-nhat);font-family:'Playfair Display',serif;">🛒 Giỏ hàng của bạn</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <?php if (empty($_SESSION['gio_hang'])): ?>
                    <div class="empty-state">🛒 Giỏ hàng trống. Hãy chọn sản phẩm yêu thích!</div>
                <?php else: ?>
                    <table class="table gio-table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th class="text-center">Số lượng</th>
                                <th class="text-end">Thành tiền</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $tong = 0; foreach ($_SESSION['gio_hang'] as $item): $thanh_tien = $item['gia'] * $item['so_luong']; $tong += $thanh_tien; ?>
                            <tr>
                                <td><?= sach($item['ten']) ?></td>
                                <td class="text-center">
                                    <form method="POST" class="d-inline-flex align-items-center gap-1">
                                        <input type="hidden" name="id_sp" value="<?= (int)$item['id'] ?>">
                                        <input type="hidden" name="action_gio" value="cap_nhat">
                                        <input type="number" name="so_luong" value="<?= $item['so_luong'] ?>" min="1" max="99"
                                               style="width:60px;border-radius:8px;border:1px solid #ddd;text-align:center;padding:4px;">
                                        <button type="submit" style="background:var(--vang);border:none;border-radius:6px;padding:4px 8px;font-size:11px;font-weight:700;color:var(--do-dam);cursor:pointer;">Cập nhật</button>
                                    </form>
                                </td>
                                <td class="text-end" style="color:var(--do);font-weight:700;"><?= number_format($thanh_tien) ?> đ</td>
                                <td class="text-center">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id_sp" value="<?= (int)$item['id'] ?>">
                                        <input type="hidden" name="action_gio" value="xoa">
                                        <button type="submit" style="background:none;border:none;color:#dc3545;font-size:18px;cursor:pointer;" title="Xóa">✕</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="text-end fw-bold">Tổng cộng:</td>
                                <td class="text-end gio-tong"><?= number_format($tong) ?> đ</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="text-end mt-3">
                        <a href="dat_hang.php" class="btn-dat-hang d-inline-block text-decoration-none text-white">
                            ✦ Đặt hàng ngay
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// ─── TABS SMOOTH SCROLL ───
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.querySelector(this.getAttribute('href'))
                ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});

// ─── THÊM GIỎ HÀNG AJAX ───
function themGio(btn, id) {
    btn.disabled = true;
    btn.textContent = '⏳ Đang thêm...';
    fetch(`?action=them_gio&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                btn.textContent = '✓ Đã thêm!';
                btn.classList.add('added');
                document.getElementById('badgeSl').textContent = data.tong;
                setTimeout(() => {
                    btn.textContent = '🛒 Thêm vào giỏ';
                    btn.classList.remove('added');
                    btn.disabled = false;
                }, 2000);
            } else {
                btn.textContent = '❌ ' + data.msg;
                btn.disabled = false;
            }
        })
        .catch(() => { btn.textContent = '❌ Lỗi kết nối'; btn.disabled = false; });
}

// ─── MINI GAME MÀU ───
function chonMau(el) {
    document.querySelectorAll('.color-option').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
    const r = document.getElementById('gameResult');
    r.style.display = 'block';
    r.innerHTML = '🎨 ' + el.dataset.msg;
}

// ─── XIN XĂM GIEO QUẺ ───
const QUE = [
    { so:1,  loai:'thuong', ten:'Quẻ Đại Cát',    loi:'Trời đất hanh thông, vạn sự như ý. Mọi việc bạn đang theo đuổi đều sẽ thành công rực rỡ.', su_nghiep:'Vận may đang đến rất gần. Hãy mạnh dạn tiến lên, cơ hội tốt sẽ không chờ đợi.', trang_phuc:'Áo dài đỏ son hoặc vàng rực — màu thịnh vượng và may mắn.', loi_khuyen:'Tin vào bản thân và hành động quyết đoán. Ngôi sao đang chiếu rọi trên bạn! ⭐' },
    { so:7,  loai:'thuong', ten:'Quẻ Cát Tường',  loi:'Mây tan trăng hiện, vận hội đang đến. Khó khăn qua đi, bình yên và hạnh phúc ở phía trước.', su_nghiep:'Công việc hanh thông. Nên nhờ người bề trên chỉ dẫn sẽ thêm thuận lợi.', trang_phuc:'Áo tứ thân xanh ngọc hoặc hồng phấn — mang duyên khởi và may mắn.', loi_khuyen:'Giữ tâm bình thản, đối nhân xử thế chân thành. Quý nhân đang đứng bên bạn! 🌸' },
    { so:15, loai:'trung',  ten:'Quẻ Bình Thường', loi:'Bước đi từng bước, chớ vội vàng. Mọi việc cần thêm thời gian và kiên nhẫn mới thành tựu.', su_nghiep:'Vận trình bình ổn. Nên tích lũy thực lực và chờ thời cơ chín muồi.', trang_phuc:'Áo dài trắng ngà hoặc màu be — thanh lịch, tao nhã.', loi_khuyen:'Kiên trì là chìa khóa. Đừng nản lòng — hạt giống tốt rồi cũng sẽ nở hoa! 🌿' },
    { so:33, loai:'trung',  ten:'Quẻ Trung Bình',  loi:'Nửa mây nửa nắng, vận hội chưa rõ. Cần thêm nỗ lực và tu tâm dưỡng đức.', su_nghiep:'Có thể gặp trở ngại nhỏ. Cẩn thận trong lời nói và quyết định.', trang_phuc:'Áo dài tím nhạt hoặc xanh nước biển — giúp bình tĩnh tinh thần.', loi_khuyen:'Hãy học cách buông bỏ. Nhẹ nhàng hơn, hạnh phúc hơn! 💜' },
    { so:42, loai:'ha',     ten:'Quẻ Tiểu Hung',   loi:'Gió ngược chiều, bước đường gian nan. Cần thận trọng trong mọi quyết định lớn.', su_nghiep:'Chưa phải lúc mạo hiểm. Tránh đầu tư lớn trong giai đoạn này.', trang_phuc:'Áo dài đen cách tân — mạnh mẽ, giúp bạn tự tin vượt thử thách.', loi_khuyen:'Nhẫn nại là vũ khí tốt nhất. Mưa rồi sẽ tạnh! 🖤' },
    { so:56, loai:'thuong', ten:'Quẻ Hồng Phúc',   loi:'Phúc lộc song toàn, tình duyên khởi sắc. Thời điểm tuyệt vời để bắt đầu điều mới.', su_nghiep:'Vận may đỉnh cao! Mọi kế hoạch đều có khả năng thành công cao.', trang_phuc:'Áo dài đỏ đô viền vàng — phú quý, sang trọng.', loi_khuyen:'Đây là thời điểm vàng! Hãy sống hết mình và chia sẻ niềm vui! ✨' },
    { so:68, loai:'trung',  ten:'Quẻ Bình An',      loi:'Sóng lặng gió yên, cuộc sống bình yên. Không có biến cố lớn, gia đình thuận hòa.', su_nghiep:'Công việc ổn định. Đây là lúc tích lũy và xây dựng nền tảng.', trang_phuc:'Áo tứ thân xanh lá non — tươi mới, mang sinh khí mới.', loi_khuyen:'Trân trọng những gì đang có. Bình yên chính là hạnh phúc! 🌿' },
    { so:79, loai:'ha',     ten:'Quẻ Cảnh Giác',    loi:'Mây đen che trăng, cần đề phòng tiểu nhân. Thận trọng trong giao tiếp và ký kết.', su_nghiep:'Tránh tranh chấp, không nên khởi kiện. Giải quyết bằng sự bình tĩnh.', trang_phuc:'Áo dài nâu trầm hoặc xám than — giúp tránh vận xui.', loi_khuyen:'Im lặng là vàng lúc này. Quan sát nhiều, nói ít! 🍂' },
    { so:88, loai:'thuong', ten:'Quẻ Song Hỉ',      loi:'Đôi hỉ lâm môn, may mắn kép đến. Có tin vui cả trong tình duyên lẫn sự nghiệp.', su_nghiep:'Cơ hội thăng tiến đang gõ cửa. Hãy chuẩn bị thật tốt!', trang_phuc:'Áo dài hồng đào hoặc đỏ son — màu hỷ sự và tình duyên.', loi_khuyen:'Hãy mở lòng đón nhận điều tốt đẹp. Hạnh phúc đang gõ cửa! 💕' },
    { so:99, loai:'thuong', ten:'Quẻ Đại Phát',     loi:'Rồng bay phượng múa, vận đại phát tài. Quẻ hiếm gặp nhất, báo hiệu vận may phi thường.', su_nghiep:'Tài lộc dồi dào, sự nghiệp thăng tiến vượt bậc. Mọi kế hoạch đều thành công ngoài mong đợi.', trang_phuc:'Áo dài vàng gold viền đỏ son — màu hoàng gia và đại phú.', loi_khuyen:'Bạn đang ở đỉnh vận may! Hãy chia sẻ phúc lộc với mọi người! 👑' }
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
        setTimeout(() => {
            sticks[idx].classList.remove('falling');
            const que = QUE[Math.floor(Math.random() * QUE.length)];
            document.getElementById('queSo').textContent       = 'Quẻ số ' + que.so;
            document.getElementById('queName').textContent     = que.ten;
            document.getElementById('queLoi').textContent      = que.loi;
            document.getElementById('queSuNghiep').textContent = que.su_nghiep;
            document.getElementById('queTrangPhuc').textContent= que.trang_phuc;
            document.getElementById('queLoiKhuyen').textContent= que.loi_khuyen;
            const loaiEl = document.getElementById('queLoai');
            const loaiMap = { thuong:['⭐ Thượng thượng','thuong'], trung:['🌿 Trung bình','trung'], ha:['⚠️ Hạ hạ','ha'] };
            loaiEl.textContent = loaiMap[que.loai][0];
            loaiEl.className   = 'que-loai ' + loaiMap[que.loai][1];
            const r = document.getElementById('queResult');
            r.style.display = 'block';
            r.scrollIntoView({ behavior:'smooth', block:'nearest' });
            dangLac = false;
        }, 500);
    }, 900);
}
function lacLai() {
    document.getElementById('queResult').style.display = 'none';
}
</script>
</body>
</html>