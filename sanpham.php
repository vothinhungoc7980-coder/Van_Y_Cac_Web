<?php
session_start();
include 'config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: bosuutap.php'); exit; }

$sp = $conn->query("
    SELECT sp.*, dm.ten_danh_muc, dm.slug AS dm_slug,
           dm_cha.ten_danh_muc AS ten_dm_cha, dm_cha.slug AS slug_cha
    FROM san_pham sp
    LEFT JOIN danh_muc dm ON sp.id_danh_muc = dm.id
    LEFT JOIN danh_muc dm_cha ON dm.id_cha = dm_cha.id
    WHERE sp.id = $id AND sp.trang_thai = 1 LIMIT 1
")->fetch_assoc();

if (!$sp) { header('Location: bosuutap.php'); exit; }

$conn->query("UPDATE san_pham SET luot_xem = luot_xem + 1 WHERE id = $id");

$sp_lq = $conn->query("
    SELECT id, ten_vi, gia_ban, gia_goc, duong_dan, noi_bat
    FROM san_pham WHERE id_danh_muc = {$sp['id_danh_muc']} AND id != $id AND trang_thai = 1
    ORDER BY da_ban DESC LIMIT 4
");

$dg_list = $conn->query("
    SELECT * FROM danh_gia WHERE id_san_pham=$id AND trang_thai='Đã duyệt'
    ORDER BY ngay_tao DESC LIMIT 10
");
$dg_tq = $conn->query("
    SELECT COUNT(*) as tong, AVG(so_sao) as avg,
           SUM(so_sao=5) s5, SUM(so_sao=4) s4, SUM(so_sao=3) s3,
           SUM(so_sao=2) s2, SUM(so_sao=1) s1
    FROM danh_gia WHERE id_san_pham=$id AND trang_thai='Đã duyệt'
")->fetch_assoc();

$avg_sao  = round($dg_tq['avg'] ?? 0, 1);
$tong_dg  = (int)($dg_tq['tong'] ?? 0);
$pct_giam = ($sp['gia_goc'] && $sp['gia_goc'] > $sp['gia_ban'])
    ? round(($sp['gia_goc'] - $sp['gia_ban']) / $sp['gia_goc'] * 100) : 0;

include 'resources/views/layouts/header.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400&family=EB+Garamond:wght@400;500&display=swap" rel="stylesheet">
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<style>
:root{--cr:#8B0000;--cr2:#5C0000;--go:#C9A84C;--pa:#FAF6EE;--ink:#1A0A0A;--mu:#6B6B6B;--bd:#E8E1D5;--fd:'Cormorant Garamond',Georgia,serif;--fb:'EB Garamond',Georgia,serif;}
*{box-sizing:border-box;}
body{font-family:var(--fb);background:var(--pa);color:var(--ink);}

/* BREADCRUMB */
.bc{background:#F0E8D8;border-bottom:1px solid var(--bd);padding:11px 0;font-size:.8rem;color:var(--mu);}
.bc a{color:var(--cr);text-decoration:none;}.bc a:hover{color:var(--go);}
.bc .sep{margin:0 8px;color:#ccc;}.bc .cur{color:var(--ink);font-weight:600;}

/* MAIN */
.sp-wrap{max-width:1160px;margin:0 auto;padding:48px 20px 72px;}
.sp-grid{display:grid;grid-template-columns:1fr 1fr;gap:56px;align-items:start;}

/* GALLERY */
.gallery{position:sticky;top:20px;}
.main-img-box{position:relative;background:#fff;border:1px solid var(--bd);border-radius:4px;overflow:hidden;aspect-ratio:3/4;cursor:zoom-in;}
.main-img-box img{width:100%;height:100%;object-fit:contain;padding:16px;transition:transform .5s;}
.main-img-box:hover img{transform:scale(1.05);}
.badge-wrap{position:absolute;top:14px;left:14px;display:flex;flex-direction:column;gap:6px;z-index:2;}
.bdg{padding:4px 12px;border-radius:2px;font-size:.65rem;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;}
.bdg-hot{background:var(--cr);color:#FFD700;}.bdg-sale{background:var(--go);color:var(--cr2);}
.zoom-ico{position:absolute;bottom:12px;right:12px;background:rgba(255,255,255,.9);border:1px solid var(--bd);border-radius:50%;width:34px;height:34px;display:flex;align-items:center;justify-content:center;color:var(--mu);cursor:pointer;font-size:.8rem;transition:all .2s;}
.zoom-ico:hover{background:var(--cr);color:#fff;border-color:var(--cr);}

/* INFO */
.sp-info{display:flex;flex-direction:column;gap:22px;}
.cat-tag{font-size:.7rem;letter-spacing:3px;text-transform:uppercase;color:var(--go);}
.cat-tag a{color:var(--go);text-decoration:none;}.cat-tag a:hover{color:var(--cr);}
.sp-title{font-family:var(--fd);font-size:clamp(1.8rem,3vw,2.5rem);font-weight:700;color:var(--cr2);line-height:1.2;}
.rating-row{display:flex;align-items:center;gap:10px;}
.stars .fa-star{color:#FFD700;font-size:.88rem;}.stars .empty{color:#d0c4b0;}
.rat-txt{font-size:.82rem;color:var(--mu);}
.rat-txt a{color:var(--cr);text-decoration:none;}
.orn{display:flex;align-items:center;gap:10px;color:var(--go);}
.orn::before,.orn::after{content:'';flex:1;height:1px;background:var(--bd);}

/* GIÁ */
.price-block{display:flex;align-items:baseline;gap:14px;flex-wrap:wrap;}
.price-main{font-family:var(--fd);font-size:2rem;font-weight:700;color:var(--cr);}
.price-old{font-size:1rem;color:#aaa;text-decoration:line-through;}
.price-save{background:#FFF3CD;color:#856404;font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:2px;letter-spacing:.5px;}

/* STOCK */
.stock-row{display:flex;align-items:center;gap:10px;font-size:.85rem;}
.stock-dot{width:8px;height:8px;border-radius:50%;display:inline-block;}
.dot-ok{background:#22c55e;}.dot-low{background:#f59e0b;}.dot-out{background:#ef4444;}
.stock-label{color:var(--mu);}

/* SECTION GHI CHÚ */
.info-box{background:#fff;border:1px solid var(--bd);border-radius:4px;padding:20px;}
.info-box-title{font-family:var(--fd);font-size:1rem;font-weight:700;color:var(--cr2);margin-bottom:12px;letter-spacing:.5px;}

/* SIZE TABLE */
.size-table{width:100%;border-collapse:collapse;font-size:.82rem;}
.size-table th{background:var(--cr2);color:#fff;padding:8px 12px;text-align:center;font-weight:600;letter-spacing:.5px;}
.size-table td{padding:7px 12px;text-align:center;border-bottom:1px solid var(--bd);}
.size-table tr:nth-child(even) td{background:#FAF6EE;}
.size-table tr:hover td{background:#F0E8D8;}

/* BUTTONS */
.btn-row{display:flex;gap:12px;flex-wrap:wrap;}
.btn-add,.btn-buy{padding:14px 28px;border-radius:3px;font-family:var(--fd);font-size:1rem;font-weight:700;letter-spacing:1px;cursor:pointer;transition:all .25s;text-decoration:none;display:inline-flex;align-items:center;gap:8px;border:2px solid transparent;}
.btn-add{background:#fff;color:var(--cr);border-color:var(--cr);}
.btn-add:hover{background:var(--cr);color:#fff;}
.btn-buy{background:var(--cr);color:#fff;border-color:var(--cr);}
.btn-buy:hover{background:var(--cr2);border-color:var(--cr2);}
.btn-wishlist{width:48px;height:48px;border-radius:3px;border:2px solid var(--bd);background:#fff;color:var(--mu);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1rem;transition:all .25s;}
.btn-wishlist:hover{border-color:var(--cr);color:var(--cr);}

/* QTY */
.qty-row{display:flex;align-items:center;gap:10px;}
.qty-label{font-size:.82rem;color:var(--mu);min-width:70px;}
.qty-ctrl{display:flex;align-items:center;border:1px solid var(--bd);border-radius:3px;overflow:hidden;}
.qty-btn{width:36px;height:36px;background:#F0E8D8;border:none;cursor:pointer;font-size:1rem;color:var(--cr2);transition:background .2s;}
.qty-btn:hover{background:var(--cr);color:#fff;}
.qty-input{width:50px;height:36px;border:none;border-left:1px solid var(--bd);border-right:1px solid var(--bd);text-align:center;font-family:var(--fb);font-size:.95rem;outline:none;}

/* META */
.meta-list{display:flex;flex-direction:column;gap:6px;}
.meta-item{display:flex;gap:8px;font-size:.82rem;}
.meta-k{color:var(--mu);min-width:90px;}
.meta-v{color:var(--ink);font-weight:500;}
.meta-v a{color:var(--cr);text-decoration:none;}

/* TABS */
.tabs-section{max-width:1160px;margin:0 auto;padding:0 20px 72px;}
.tab-nav{display:flex;border-bottom:2px solid var(--bd);margin-bottom:32px;}
.tab-btn{padding:12px 24px;background:none;border:none;font-family:var(--fd);font-size:1rem;font-weight:600;color:var(--mu);cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px;transition:all .2s;letter-spacing:.5px;}
.tab-btn.active{color:var(--cr);border-bottom-color:var(--cr);}
.tab-btn:hover{color:var(--cr);}
.tab-pane{display:none;}.tab-pane.active{display:block;}

/* MÔ TẢ */
.mo-ta-content{font-size:.95rem;line-height:1.9;color:#444;}
.mo-ta-content p{margin-bottom:12px;}

/* ĐÁNH GIÁ */
.rating-summary{display:grid;grid-template-columns:160px 1fr;gap:24px;align-items:center;background:#fff;border:1px solid var(--bd);border-radius:4px;padding:24px;margin-bottom:28px;}
.rating-big{text-align:center;}
.rating-num{font-family:var(--fd);font-size:3.5rem;font-weight:700;color:var(--cr);line-height:1;}
.rating-of{font-size:.8rem;color:var(--mu);margin-top:4px;}
.rating-bars{display:flex;flex-direction:column;gap:6px;}
.bar-row{display:flex;align-items:center;gap:10px;font-size:.8rem;}
.bar-star{min-width:30px;color:var(--mu);text-align:right;}
.bar-track{flex:1;background:#F0E8D8;border-radius:2px;height:8px;overflow:hidden;}
.bar-fill{height:100%;background:var(--go);border-radius:2px;transition:width .5s;}
.bar-count{min-width:20px;color:var(--mu);}

.review-list{display:flex;flex-direction:column;gap:20px;}
.review-item{background:#fff;border:1px solid var(--bd);border-radius:4px;padding:20px;}
.rv-head{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;}
.rv-name{font-weight:700;font-size:.9rem;}
.rv-date{font-size:.75rem;color:var(--mu);}
.rv-stars .fa-star{color:#FFD700;font-size:.8rem;}.rv-stars .empty{color:#d0c4b0;}
.rv-text{font-size:.88rem;line-height:1.7;color:#444;margin-top:8px;}
.rv-reply{background:#FFF8EE;border-left:3px solid var(--go);padding:10px 14px;margin-top:12px;font-size:.82rem;color:#555;}
.rv-reply strong{color:var(--cr2);}

/* SP LIÊN QUAN */
.related-section{background:#F0E8D8;padding:56px 0;}
.related-wrap{max-width:1160px;margin:0 auto;padding:0 20px;}
.sec-title{font-family:var(--fd);font-size:1.8rem;font-weight:700;color:var(--cr2);margin-bottom:4px;}
.sec-line{width:60px;height:2px;background:var(--go);margin-bottom:32px;}
.related-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;}
.rel-card{background:#fff;border-radius:4px;overflow:hidden;border:1px solid var(--bd);transition:transform .3s,box-shadow .3s;text-decoration:none;color:inherit;display:block;}
.rel-card:hover{transform:translateY(-4px);box-shadow:0 12px 30px rgba(139,0,0,.12);}
.rel-img-wrap{height:220px;overflow:hidden;background:#FAF6EE;position:relative;}
.rel-img-wrap img{width:100%;height:100%;object-fit:contain;padding:10px;transition:transform .4s;}
.rel-card:hover .rel-img-wrap img{transform:scale(1.06);}
.rel-body{padding:14px;}
.rel-name{font-family:var(--fd);font-size:.95rem;font-weight:700;margin-bottom:6px;line-height:1.3;}
.rel-price{color:var(--cr);font-family:var(--fd);font-size:1rem;font-weight:700;}

/* LIGHTBOX */
.lightbox{display:none;position:fixed;inset:0;background:rgba(0,0,0,.88);z-index:9999;align-items:center;justify-content:center;}
.lightbox.open{display:flex;}
.lightbox img{max-width:90vw;max-height:90vh;object-fit:contain;border-radius:4px;}
.lightbox-close{position:absolute;top:20px;right:24px;color:#fff;font-size:2rem;cursor:pointer;line-height:1;}

/* RESPONSIVE */
@media(max-width:900px){.sp-grid{grid-template-columns:1fr;gap:32px;}.gallery{position:static;}.related-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:500px){.related-grid{grid-template-columns:1fr;}.rating-summary{grid-template-columns:1fr;}}
</style>

<!-- BREADCRUMB -->
<div class="bc">
<div class="container">
  <a href="index.php">Trang chủ</a>
  <span class="sep">/</span>
  <?php if ($sp['ten_dm_cha']): ?>
  <a href="bosuutap.php?danh_muc=<?= $sp['id_danh_muc'] ?>"><?= htmlspecialchars($sp['ten_dm_cha']) ?></a>
  <span class="sep">/</span>
  <?php endif; ?>
  <a href="bosuutap.php?danh_muc=<?= $sp['id_danh_muc'] ?>"><?= htmlspecialchars($sp['ten_danh_muc'] ?? '') ?></a>
  <span class="sep">/</span>
  <span class="cur"><?= htmlspecialchars($sp['ten_vi']) ?></span>
</div>
</div>

<!-- MAIN DETAIL -->
<div class="sp-wrap">
<div class="sp-grid" data-aos="fade-up">

  <!-- GALLERY -->
  <div class="gallery">
    <div class="main-img-box" id="mainImgBox" onclick="openLightbox()">
      <div class="badge-wrap">
        <?php if ($sp['noi_bat']): ?><span class="bdg bdg-hot">Nổi Bật</span><?php endif; ?>
        <?php if ($pct_giam): ?><span class="bdg bdg-sale">-<?= $pct_giam ?>%</span><?php endif; ?>
      </div>
      <img id="mainImg"
           src="image/<?= htmlspecialchars($sp['duong_dan'] ?? 'no-image.jpg') ?>"
           onerror="this.src='https://via.placeholder.com/500x667?text=Vân+Y+Các'"
           alt="<?= htmlspecialchars($sp['ten_vi']) ?>">
      <div class="zoom-ico"><i class="fas fa-search-plus"></i></div>
    </div>
  </div>

  <!-- INFO -->
  <div class="sp-info">

    <!-- Category -->
    <div class="cat-tag">
      <?php if ($sp['ten_dm_cha']): ?>
        <a href="bosuutap.php?slug=<?= $sp['slug_cha'] ?>"><?= htmlspecialchars($sp['ten_dm_cha']) ?></a>
        <span class="sep" style="margin:0 6px;color:#ccc">·</span>
      <?php endif; ?>
      <a href="bosuutap.php?danh_muc=<?= $sp['id_danh_muc'] ?>"><?= htmlspecialchars($sp['ten_danh_muc'] ?? '') ?></a>
    </div>

    <!-- Tên -->
    <h1 class="sp-title"><?= htmlspecialchars($sp['ten_vi']) ?></h1>

    <!-- Rating -->
    <div class="rating-row">
      <div class="stars">
        <?php for ($i=1;$i<=5;$i++): ?>
          <i class="fas fa-star <?= $i <= round($avg_sao) ? '' : 'empty' ?>"></i>
        <?php endfor; ?>
      </div>
      <span class="rat-txt">
        <?= $avg_sao ?> / 5
        <a href="#reviews">(<?= $tong_dg ?> đánh giá)</a>
      </span>
      <span style="font-size:.78rem;color:var(--mu)">· <?= number_format($sp['luot_xem']) ?> lượt xem</span>
    </div>

    <div class="orn"><i class="fas fa-seedling"></i></div>

    <!-- Giá -->
    <div class="price-block">
      <span class="price-main"><?= number_format($sp['gia_ban'],0,',','.') ?> ₫</span>
      <?php if ($sp['gia_goc'] && $sp['gia_goc'] > $sp['gia_ban']): ?>
      <span class="price-old"><?= number_format($sp['gia_goc'],0,',','.') ?> ₫</span>
      <span class="price-save">Tiết kiệm <?= $pct_giam ?>%</span>
      <?php endif; ?>
    </div>

    <!-- Tồn kho -->
    <div class="stock-row">
      <?php if ($sp['so_luong_ton'] <= 0): ?>
        <span class="stock-dot dot-out"></span><span class="stock-label">Hết hàng</span>
      <?php elseif ($sp['so_luong_ton'] <= 5): ?>
        <span class="stock-dot dot-low"></span><span class="stock-label">Còn <?= $sp['so_luong_ton'] ?> sản phẩm — sắp hết</span>
      <?php else: ?>
        <span class="stock-dot dot-ok"></span><span class="stock-label">Còn hàng (<?= $sp['so_luong_ton'] ?> sp)</span>
      <?php endif; ?>
      <span style="font-size:.78rem;color:var(--mu)">· Đã bán: <?= number_format($sp['da_ban']) ?></span>
    </div>

    <!-- Mô tả ngắn -->
    <?php if ($sp['mo_ta_ngan']): ?>
    <p style="font-size:.92rem;line-height:1.8;color:#555;border-left:3px solid var(--go);padding-left:14px;">
      <?= htmlspecialchars($sp['mo_ta_ngan']) ?>
    </p>
    <?php endif; ?>

    <!-- Số lượng -->
    <?php if ($sp['so_luong_ton'] > 0): ?>
    <div class="qty-row">
      <span class="qty-label">Số lượng:</span>
      <div class="qty-ctrl">
        <button class="qty-btn" onclick="changeQty(-1)">−</button>
        <input type="number" class="qty-input" id="qty" value="1" min="1" max="<?= $sp['so_luong_ton'] ?>">
        <button class="qty-btn" onclick="changeQty(1)">+</button>
      </div>
    </div>
    <?php endif; ?>

    <!-- Buttons -->
    <div class="btn-row">
      <?php if ($sp['so_luong_ton'] > 0): ?>
      <a href="#" id="btnCart" class="btn-add" onclick="addToCart(<?= $sp['id'] ?>); return false;">
        <i class="fas fa-shopping-bag"></i> Thêm Vào Giỏ
      </a>
      <a href="giohang.php?action=buy_now&id=<?= $sp['id'] ?>" class="btn-buy">
        <i class="fas fa-bolt"></i> Mua Ngay
      </a>
      <?php else: ?>
      <button class="btn-add" disabled style="opacity:.5;cursor:not-allowed;">Hết Hàng</button>
      <?php endif; ?>
      <button class="btn-wishlist" title="Yêu thích"><i class="far fa-heart"></i></button>
    </div>

    <!-- Bảng size -->
    <div class="info-box" data-aos="fade-up" data-aos-delay="100">
      <div class="info-box-title"><i class="fas fa-ruler-combined me-2" style="color:var(--go)"></i>Bảng Số Đo & Size</div>
      <table class="size-table">
        <thead>
          <tr><th>Size</th><th>Ngực (cm)</th><th>Eo (cm)</th><th>Hông (cm)</th><th>Chiều cao (cm)</th><th>Cân nặng (kg)</th></tr>
        </thead>
        <tbody>
          <tr><td>S</td><td>80–84</td><td>62–66</td><td>86–90</td><td>152–158</td><td>45–52</td></tr>
          <tr><td>M</td><td>85–89</td><td>67–71</td><td>91–95</td><td>158–163</td><td>52–58</td></tr>
          <tr><td>L</td><td>90–94</td><td>72–76</td><td>96–100</td><td>163–168</td><td>58–65</td></tr>
          <tr><td>XL</td><td>95–99</td><td>77–81</td><td>101–105</td><td>168–173</td><td>65–72</td></tr>
          <tr><td>2XL</td><td>100–104</td><td>82–86</td><td>106–110</td><td>173–178</td><td>72–80</td></tr>
          <tr><td>May Đo</td><td colspan="5" style="text-align:left;color:var(--cr);font-style:italic">Liên hệ để được tư vấn may đo theo số đo riêng</td></tr>
        </tbody>
      </table>
      <p style="font-size:.75rem;color:var(--mu);margin-top:8px;"><i class="fas fa-info-circle me-1"></i>Số đo có thể chênh lệch ±2cm. Nên đo kỹ trước khi đặt hàng.</p>
    </div>

    <!-- Meta info -->
    <div class="meta-list">
      <?php if ($sp['ten_danh_muc']): ?>
      <div class="meta-item">
        <span class="meta-k">Danh mục:</span>
        <span class="meta-v"><a href="bosuutap.php?danh_muc=<?= $sp['id_danh_muc'] ?>"><?= htmlspecialchars($sp['ten_danh_muc']) ?></a></span>
      </div>
      <?php endif; ?>
      <div class="meta-item">
        <span class="meta-k">Chất liệu:</span>
        <span class="meta-v">Tơ tằm, gấm lụa cao cấp</span>
      </div>
      <div class="meta-item">
        <span class="meta-k">Xuất xứ:</span>
        <span class="meta-v">Việt Nam — Thủ công truyền thống</span>
      </div>
      <div class="meta-item">
        <span class="meta-k">Bảo quản:</span>
        <span class="meta-v">Giặt khô, không ngâm nước</span>
      </div>
      <?php if ($sp['slug']): ?>
      <div class="meta-item">
        <span class="meta-k">Mã SP:</span>
        <span class="meta-v" style="font-size:.78rem;color:var(--mu)"><?= htmlspecialchars($sp['slug']) ?></span>
      </div>
      <?php endif; ?>
    </div>

  </div><!-- /.sp-info -->
</div><!-- /.sp-grid -->
</div><!-- /.sp-wrap -->

<!-- TABS: Mô tả / Đánh giá -->
<div class="tabs-section" id="reviews">
  <div class="tab-nav">
    <button class="tab-btn active" onclick="switchTab('mo-ta', this)">Mô Tả Chi Tiết</button>
    <button class="tab-btn" onclick="switchTab('danh-gia', this)">Đánh Giá (<?= $tong_dg ?>)</button>
    <button class="tab-btn" onclick="switchTab('chinh-sach', this)">Chính Sách & Bảo Hành</button>
  </div>

  <!-- Mô tả -->
  <div class="tab-pane active" id="tab-mo-ta">
    <div class="mo-ta-content">
      <?php if ($sp['mo_ta']): ?>
        <?= nl2br(htmlspecialchars($sp['mo_ta'])) ?>
      <?php else: ?>
        <p>Sản phẩm <strong><?= htmlspecialchars($sp['ten_vi']) ?></strong> được chế tác tỉ mỉ từ chất liệu truyền thống cao cấp, tái hiện vẻ đẹp trang phục cổ Việt Nam.</p>
        <p>Mỗi sản phẩm đều được may thủ công bởi các nghệ nhân lành nghề, đảm bảo độ chính xác về kiểu dáng lịch sử và chất lượng vải vóc.</p>
        <p>Phù hợp cho các buổi chụp ảnh nghệ thuật, lễ hội văn hóa, sự kiện truyền thống và bộ sưu tập cá nhân.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Đánh giá -->
  <div class="tab-pane" id="tab-danh-gia">
    <?php if ($tong_dg > 0): ?>
    <div class="rating-summary">
      <div class="rating-big">
        <div class="rating-num"><?= $avg_sao ?></div>
        <div class="stars" style="margin:6px 0">
          <?php for ($i=1;$i<=5;$i++): ?>
            <i class="fas fa-star <?= $i<=round($avg_sao)?'':'empty' ?>" style="font-size:.9rem"></i>
          <?php endfor; ?>
        </div>
        <div class="rating-of"><?= $tong_dg ?> đánh giá</div>
      </div>
      <div class="rating-bars">
        <?php foreach ([5,4,3,2,1] as $s):
          $cnt = (int)($dg_tq["s$s"] ?? 0);
          $pct = $tong_dg > 0 ? round($cnt/$tong_dg*100) : 0;
        ?>
        <div class="bar-row">
          <span class="bar-star"><?= $s ?> <i class="fas fa-star" style="color:#FFD700;font-size:.7rem"></i></span>
          <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
          <span class="bar-count"><?= $cnt ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="review-list">
    <?php if ($dg_list && $dg_list->num_rows > 0): ?>
      <?php while ($dg = $dg_list->fetch_assoc()): ?>
      <div class="review-item">
        <div class="rv-head">
          <div>
            <div class="rv-name"><?= htmlspecialchars($dg['ho_ten']) ?></div>
            <div class="rv-stars">
              <?php for ($i=1;$i<=5;$i++): ?>
                <i class="fas fa-star <?= $i<=$dg['so_sao']?'':'empty' ?>"></i>
              <?php endfor; ?>
            </div>
          </div>
          <div class="rv-date"><?= date('d/m/Y', strtotime($dg['ngay_tao'])) ?></div>
        </div>
        <?php if ($dg['noi_dung']): ?>
        <div class="rv-text"><?= nl2br(htmlspecialchars($dg['noi_dung'])) ?></div>
        <?php endif; ?>
        <?php if ($dg['phan_hoi_admin']): ?>
        <div class="rv-reply"><strong>Vân Y Các phản hồi:</strong> <?= nl2br(htmlspecialchars($dg['phan_hoi_admin'])) ?></div>
        <?php endif; ?>
      </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="color:var(--mu);text-align:center;padding:32px">Chưa có đánh giá nào. Hãy là người đầu tiên đánh giá sản phẩm này!</p>
    <?php endif; ?>
    </div>
  </div>

  <!-- Chính sách -->
  <div class="tab-pane" id="tab-chinh-sach">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px">
      <?php
      $cs = [
        ['fas fa-undo-alt','Đổi Trả 7 Ngày','Đổi trả miễn phí trong 7 ngày nếu sản phẩm lỗi từ nhà sản xuất.'],
        ['fas fa-shield-alt','Bảo Hành Chất Lượng','Cam kết đúng mẫu, đúng chất liệu như mô tả. Hoàn tiền 100% nếu không đúng.'],
        ['fas fa-truck','Giao Hàng Toàn Quốc','Giao hàng 3–7 ngày. Miễn phí ship đơn từ 500.000₫.'],
        ['fas fa-headset','Tư Vấn 24/7','Hỗ trợ tư vấn chọn size, chất liệu và may đo theo yêu cầu.'],
      ];
      foreach ($cs as $c): ?>
      <div style="background:#fff;border:1px solid var(--bd);border-radius:4px;padding:20px">
        <i class="<?= $c[0] ?>" style="color:var(--go);font-size:1.4rem;margin-bottom:10px;display:block"></i>
        <div style="font-family:var(--fd);font-size:1rem;font-weight:700;color:var(--cr2);margin-bottom:6px"><?= $c[1] ?></div>
        <div style="font-size:.85rem;color:#555;line-height:1.6"><?= $c[2] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- SẢN PHẨM LIÊN QUAN -->
<?php if ($sp_lq && $sp_lq->num_rows > 0): ?>
<div class="related-section">
  <div class="related-wrap">
    <h2 class="sec-title" data-aos="fade-up">Sản Phẩm Liên Quan</h2>
    <div class="sec-line"></div>
    <div class="related-grid">
      <?php while ($r = $sp_lq->fetch_assoc()): ?>
      <a href="sanpham.php?id=<?= $r['id'] ?>" class="rel-card" data-aos="fade-up">
        <div class="rel-img-wrap">
          <?php if ($r['noi_bat']): ?><span style="position:absolute;top:10px;left:10px;background:var(--cr);color:#FFD700;font-size:.6rem;font-weight:800;padding:3px 8px;border-radius:2px;letter-spacing:1px;text-transform:uppercase;z-index:1">NỔI BẬT</span><?php endif; ?>
          <img src="image/<?= htmlspecialchars($r['duong_dan'] ?? 'no-image.jpg') ?>"
               onerror="this.src='https://via.placeholder.com/300x400?text=Vân+Y+Các'"
               alt="<?= htmlspecialchars($r['ten_vi']) ?>">
        </div>
        <div class="rel-body">
          <div class="rel-name"><?= htmlspecialchars($r['ten_vi']) ?></div>
          <div class="rel-price">
            <?= number_format($r['gia_ban'],0,',','.') ?> ₫
            <?php if ($r['gia_goc'] && $r['gia_goc']>$r['gia_ban']): ?>
            <span style="font-size:.8rem;color:#aaa;text-decoration:line-through;margin-left:6px"><?= number_format($r['gia_goc'],0,',','.') ?> ₫</span>
            <?php endif; ?>
          </div>
        </div>
      </a>
      <?php endwhile; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- LIGHTBOX -->
<div class="lightbox" id="lightbox" onclick="closeLightbox()">
  <span class="lightbox-close" onclick="closeLightbox()">×</span>
  <img id="lightboxImg" src="" alt="">
</div>

<?php include 'resources/views/layouts/footer.php'; ?>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init({duration:700,once:true});

function changeQty(d){
  const i=document.getElementById('qty');
  const max=parseInt(i.max)||99;
  let v=parseInt(i.value)||1;
  v=Math.min(Math.max(v+d,1),max);
  i.value=v;
}

function addToCart(id){
  const qty=document.getElementById('qty')?.value||1;
  window.location.href=`giohang.php?action=add&id=${id}&qty=${qty}`;
}

function switchTab(name,btn){
  document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById('tab-'+name).classList.add('active');
  btn.classList.add('active');
}

function openLightbox(){
  const src=document.getElementById('mainImg').src;
  document.getElementById('lightboxImg').src=src;
  document.getElementById('lightbox').classList.add('open');
}
function closeLightbox(){
  document.getElementById('lightbox').classList.remove('open');
}
document.addEventListener('keydown',e=>{ if(e.key==='Escape') closeLightbox(); });

// Wishlist toggle
document.querySelector('.btn-wishlist')?.addEventListener('click',function(){
  const ico=this.querySelector('i');
  ico.classList.toggle('far'); ico.classList.toggle('fas');
  this.style.color=ico.classList.contains('fas')?'var(--cr)':'';
  this.style.borderColor=ico.classList.contains('fas')?'var(--cr)':'';
});
</script>