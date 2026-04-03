<?php
session_start();
include 'config/db.php';
// CẬP NHẬT: Chỉ lấy những sản phẩm có noi_bat = 1
$sql_sp = "SELECT sp.id, 
                  sp.ten_vi AS name, 
                  sp.gia_ban AS price, 
                  sp.gia_goc, 
                  sp.duong_dan AS img, 
                  dm.ten_danh_muc
           FROM san_pham sp
           LEFT JOIN danh_muc dm ON sp.id_danh_muc = dm.id
           WHERE sp.trang_thai = 1 AND sp.noi_bat = 1
           ORDER BY sp.id DESC LIMIT 10";
$result = $conn->query($sql_sp);
$featured_products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $featured_products[] = $row;
    }
}

// Lấy 9 danh mục hiển thị Slide
$sql_dm9 = "SELECT dm.id, dm.ten_danh_muc, dm.slug, dm.hinh_anh, dm_cha.ten_danh_muc AS ten_cha
            FROM danh_muc dm
            LEFT JOIN danh_muc dm_cha ON dm.id_cha = dm_cha.id
            WHERE dm.id_cha IS NOT NULL AND dm.trang_thai = 1
            ORDER BY dm.id_cha ASC, dm.thu_tu ASC LIMIT 9";
$result_dm9 = $conn->query($sql_dm9);

$anh_mac_dinh = [
    'ao-nhat-binh' => 'image/cophuc.jpg',
    'giao-linh'    => 'image/ao-giao-linh.jpg',
    'ao-tac'       => 'image/aotac.jpg',
    'tu-than'      => 'image/tuthan.jpg',
    'ngu-than'     => 'image/nguthan1.jpg',
    'vien-linh'    => 'image/vienlinh1.jpg',
    'ao-yem'       => 'image/yem1.jpg',
    'ao-dai'       => 'image/dai1.jpg',
    'ao-ba-ba'     => 'image/baba1.png',
];

include 'resources/views/layouts/header.php';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<style>
/* =====================
   HERO BANNER
====================== */
.hero-banner {
    position: relative; height: 90vh; min-height: 600px;
    display: flex; align-items: center; justify-content: center;
    text-align: center;
    background: url('./image/banner.jpg') center/cover fixed;
}
.hero-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to bottom, rgba(139,0,0,0.4) 0%, rgba(26,10,10,0.85) 100%);
}
.hero-content {
    position: relative; z-index: 2; color: #fff; max-width: 900px; padding: 0 20px;
}
.hero-title {
    font-family: 'Cormorant Garamond', serif; font-size: 4.5rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 4px;
    background: linear-gradient(to right, #FFD700, #FCA5A5, #FFD700);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    text-shadow: 0 10px 20px rgba(0,0,0,0.5); margin-bottom: 20px;
}
.hero-subtitle {
    font-family: 'EB Garamond', serif; font-size: 1.4rem; font-style: italic; color: #E8E1D5;
    margin-bottom: 40px; letter-spacing: 1px;
}
.btn-hero {
    background: linear-gradient(135deg, #C9A84C, #A17F2C); color: #1A0A0A;
    border: none; padding: 14px 40px; font-size: 1.1rem; font-weight: 700;
    border-radius: 50px; text-transform: uppercase; letter-spacing: 2px;
    transition: all 0.3s ease; box-shadow: 0 8px 25px rgba(201,168,76,0.4);
}
.btn-hero:hover {
    transform: translateY(-5px); box-shadow: 0 12px 30px rgba(201,168,76,0.6); color: #fff;
}

/* =====================
   TÍNH NĂNG
====================== */
.feature-box {
    padding: 35px 20px; border-radius: 12px; background: #fff;
    box-shadow: 0 10px 30px rgba(0,0,0,0.04); transition: all 0.3s ease;
    border-bottom: 3px solid transparent;
}
.feature-box:hover {
    transform: translateY(-10px); border-color: #8B0000;
    box-shadow: 0 15px 40px rgba(139,0,0,0.1);
}
.feature-box i {
    font-size: 2.5rem; color: #C9A84C; margin-bottom: 20px;
    transition: transform 0.4s ease;
}
.feature-box:hover i { transform: scale(1.2) rotate(5deg); color: #8B0000; }

/* =====================
   TIÊU ĐỀ SECTION
====================== */
.section-title {
    font-family: 'Cormorant Garamond', serif; font-size: 2.5rem; font-weight: 700;
    color: #8B0000; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 10px;
}
.section-line {
    width: 80px; height: 3px; background: #C9A84C; margin: 0 auto 40px; border-radius: 2px;
}

/* =====================
   DANH MỤC CAROUSEL
====================== */
.dm-carousel-section { padding: 80px 0; background: #FAF6EE; overflow: hidden; }
.dmSwiper { padding: 40px 0; overflow: visible !important; }
.dm-slide {
    transition: all 0.5s ease; filter: brightness(0.6) grayscale(20%); transform: scale(0.85);
    border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}
.dm-slide.swiper-slide-active {
    filter: brightness(1) grayscale(0%); transform: scale(1.05); z-index: 10;
    box-shadow: 0 20px 50px rgba(139,0,0,0.2);
}
.dm-slide-img { width: 100%; height: 420px; object-fit: cover; transition: transform 0.8s ease; }
.dm-slide:hover .dm-slide-img { transform: scale(1.08); }
.dm-slide-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(26,10,10,0.9) 0%, rgba(0,0,0,0) 60%);
    display: flex; align-items: flex-end; padding: 30px 20px;
}
.dm-slide-name { font-family: 'Cormorant Garamond', serif; color: #fff; font-size: 1.6rem; font-weight: 700; margin: 5px 0; }
.dm-slide-parent { color: #C9A84C; font-size: 0.7rem; letter-spacing: 2px; text-transform: uppercase; }

/* =====================
   SẢN PHẨM NỔI BẬT
====================== */
.product-section { padding: 80px 0; background: #fff; }
.productSwiper { padding: 20px 10px 60px 10px; }
.prod-card {
    background: #fff; border: 1px solid #E8E1D5; border-radius: 8px; overflow: hidden;
    transition: all 0.3s ease; position: relative;
}
.prod-card:hover {
    box-shadow: 0 15px 35px rgba(139,0,0,0.08); transform: translateY(-8px); border-color: #C9A84C;
}
.prod-img-wrap {
    height: 340px; overflow: hidden; position: relative; background: #F9F9F9;
}
.prod-img {
    width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;
}
.prod-card:hover .prod-img { transform: scale(1.05); }
.prod-overlay {
    position: absolute; inset: 0; background: rgba(26,10,10,0.4);
    display: flex; align-items: center; justify-content: center; gap: 15px;
    opacity: 0; transition: opacity 0.3s ease;
}
.prod-card:hover .prod-overlay { opacity: 1; }
.prod-action-btn {
    width: 45px; height: 45px; border-radius: 50%; background: #fff; color: #8B0000;
    display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
    text-decoration: none; transform: translateY(20px); transition: all 0.3s ease;
}
.prod-action-btn:hover { background: #8B0000; color: #fff; }
.prod-card:hover .prod-action-btn { transform: translateY(0); }
.prod-info { padding: 20px 15px; text-align: center; }
.prod-cate { font-size: 0.7rem; color: #C9A84C; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 8px; display: block;}
.prod-name { font-family: 'EB Garamond', serif; font-size: 1.15rem; font-weight: 600; color: #1A0A0A; text-decoration: none; margin-bottom: 10px; display: block;}
.prod-name:hover { color: #8B0000; }
.prod-price { font-family: 'Cormorant Garamond', serif; font-size: 1.4rem; font-weight: 700; color: #8B0000; }
.prod-price-old { font-size: 0.9rem; color: #999; text-decoration: line-through; margin-left: 8px; font-weight: 400; }

/* Nút điều hướng Swiper Sản phẩm */
.swiper-button-next, .swiper-button-prev { color: #8B0000 !important; background: rgba(255,255,255,0.8); width: 40px !important; height: 40px !important; border-radius: 50%; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.swiper-button-next::after, .swiper-button-prev::after { font-size: 1.2rem !important; font-weight: bold; }
.swiper-pagination-bullet-active { background: #8B0000 !important; }

/* =====================
   ABOUT VÂN Y CÁC
====================== */
.about-section {
    background: linear-gradient(rgba(26,10,10,0.85), rgba(26,10,10,0.85)), url('./image/banner.jpg') fixed center/cover;
    padding: 100px 0; color: #fff; text-align: center;
}
.about-text {
    font-family: 'EB Garamond', serif; font-size: 1.2rem; line-height: 1.8; color: #E8E1D5;
    max-width: 800px; margin: 0 auto 30px; font-style: italic;
}
@media(max-width: 768px) {
    .hero-title { font-size: 2.8rem; }
    .hero-subtitle { font-size: 1.1rem; }
    .dm-slide-img { height: 320px; }
}
</style>

<div id="blossom-container"></div>

<header class="hero-banner">
    <div class="hero-overlay"></div>
    <div class="hero-content" data-aos="zoom-in" data-aos-duration="1500">
        <h1 class="hero-title">Tinh Hoa Cổ Phục Việt</h1>
        <p class="hero-subtitle">Khôi phục vẻ đẹp truyền thống - Kết nối giá trị hiện đại</p>
        <a href="bosuutap.php" class="btn-hero text-decoration-none">Khám Phá Bộ Sưu Tập</a>
    </div>
</header>

<div class="container my-5 py-5">
    <div class="row text-center g-4">
        <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
            <div class="feature-box h-100">
                <i class="fas fa-tape"></i>
                <h5 class="fw-bold" style="color:#5C0000">May Đo Chuẩn Xác</h5>
                <p class="text-muted small mb-0">Thiết kế theo số đo riêng, tôn vinh vóc dáng người Việt.</p>
            </div>
        </div>
        <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
            <div class="feature-box h-100">
                <i class="fas fa-gem"></i>
                <h5 class="fw-bold" style="color:#5C0000">Chất Liệu Cao Cấp</h5>
                <p class="text-muted small mb-0">Sử dụng tơ tằm, gấm lụa truyền thống tốt nhất.</p>
            </div>
        </div>
        <div class="col-md-3" data-aos="fade-up" data-aos-delay="300">
            <div class="feature-box h-100">
                <i class="fas fa-scroll"></i>
                <h5 class="fw-bold" style="color:#5C0000">Phục Dựng Tỉ Mỉ</h5>
                <p class="text-muted small mb-0">Nghiên cứu sát sườn từ các tư liệu lịch sử cổ xưa.</p>
            </div>
        </div>
        <div class="col-md-3" data-aos="fade-up" data-aos-delay="400">
            <div class="feature-box h-100">
                <i class="fas fa-gift"></i>
                <h5 class="fw-bold" style="color:#5C0000">Đóng Gói Sang Trọng</h5>
                <p class="text-muted small mb-0">Hộp quà tinh tế, an toàn vận chuyển toàn quốc.</p>
            </div>
        </div>
    </div>
</div>

<div class="dm-carousel-section">
    <div class="container text-center" data-aos="fade-up">
        <h2 class="section-title">Danh Mục Sản Phẩm</h2>
        <div class="section-line"></div>
    </div>
    
    <div class="swiper dmSwiper" data-aos="fade-up" data-aos-duration="1000">
        <div class="swiper-wrapper">
            <?php while ($dm = $result_dm9->fetch_assoc()):
                $anh = $dm['hinh_anh'] ? 'image/' . $dm['hinh_anh'] : ($anh_mac_dinh[$dm['slug']] ?? 'image/cophuc.jpg');
            ?>
            <div class="swiper-slide dm-slide">
                <a href="bosuutap.php?danh_muc=<?= $dm['id'] ?>" class="text-decoration-none">
                    <img src="<?= $anh ?>" alt="<?= htmlspecialchars($dm['ten_danh_muc']) ?>" class="dm-slide-img">
                    <div class="dm-slide-overlay text-center">
                        <div class="w-100">
                            <?php if ($dm['ten_cha']): ?>
                            <span class="dm-slide-parent"><?= htmlspecialchars($dm['ten_cha']) ?></span>
                            <?php endif; ?>
                            <h4 class="dm-slide-name"><?= htmlspecialchars($dm['ten_danh_muc']) ?></h4>
                        </div>
                    </div>
                </a>
            </div>
            <?php endwhile; ?>
        </div>
        <div class="swiper-button-next dm-nav-next"></div>
        <div class="swiper-button-prev dm-nav-prev"></div>
    </div>
    <div class="text-center mt-4">
        <a href="bosuutap.php" class="btn btn-outline-danger rounded-pill px-4 py-2 fw-bold" style="border-width:2px">
            Xem Toàn Bộ Cửa Hàng <i class="fas fa-arrow-right ms-2"></i>
        </a>
    </div>
</div>

<div class="product-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="section-title">Sản Phẩm Nổi Bật</h2>
            <div class="section-line"></div>
        </div>

        <div class="swiper productSwiper" data-aos="fade-up" data-aos-duration="1000">
            <div class="swiper-wrapper">
                <?php if (!empty($featured_products)): ?>
                    <?php foreach ($featured_products as $item): ?>
                    <div class="swiper-slide">
                        <div class="prod-card">
                            <?php if ($item['gia_goc'] && $item['gia_goc'] > $item['price']): ?>
                            <span class="badge bg-danger position-absolute top-0 start-0 m-3" style="z-index:2; padding: 6px 10px; font-size: 0.7rem;">
                                -<?= round(($item['gia_goc'] - $item['price']) / $item['gia_goc'] * 100) ?>%
                            </span>
                            <?php else: ?>
                            <span class="badge position-absolute top-0 start-0 m-3" style="background:#C9A84C; color:#fff; z-index:2; padding: 6px 10px; font-size: 0.7rem;">
                                HOT
                            </span>
                            <?php endif; ?>

                            <div class="prod-img-wrap">
                                <img src="image/<?= htmlspecialchars($item['img'] ?? 'no-image.jpg') ?>"
                                     onerror="this.src='https://placehold.co/300x450/FAF6EE/8B0000?text=Vân+Y+Các'"
                                     class="prod-img" alt="<?= htmlspecialchars($item['name']) ?>">
                                
                               <div class="prod-overlay">
                                    <a href="sanpham.php?id=<?= $item['id'] ?>" class="prod-action-btn" title="Xem chi tiết"><i class="fas fa-eye"></i></a>
                                </div>
                            </div>

                            <div class="prod-info">
                                <?php if ($item['ten_danh_muc']): ?>
                                <span class="prod-cate"><?= htmlspecialchars($item['ten_danh_muc']) ?></span>
                                <?php endif; ?>
                                <a href="sanpham.php?id=<?= $item['id'] ?>" class="prod-name"><?= htmlspecialchars($item['name']) ?></a>
                                <div class="prod-price">
                                    <?= number_format($item['price'], 0, ',', '.') ?> ₫
                                    <?php if ($item['gia_goc'] && $item['gia_goc'] > $item['price']): ?>
                                    <span class="prod-price-old"><?= number_format($item['gia_goc'], 0, ',', '.') ?> ₫</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="w-100 text-center text-muted py-5">
                        <i class="fas fa-tshirt fa-3x mb-3" style="color:#E8E1D5"></i>
                        <p>Hiện chưa có sản phẩm nổi bật nào.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="swiper-pagination" style="bottom: 0;"></div>
            <div class="swiper-button-next prod-nav-next"></div>
            <div class="swiper-button-prev prod-nav-prev"></div>
        </div>
    </div>
</div>

<div class="about-section">
    <div class="container" data-aos="zoom-in-up">
        <h2 class="section-title text-warning mb-3" style="color: #FFD700 !important;">Về Vân Y Các</h2>
        <div class="section-line" style="background: #FFD700;"></div>
        <p class="about-text">
            "Chúng tôi không chỉ may trang phục, chúng tôi đang dệt lại những trang sử vàng son của dân tộc. 
            Mỗi đường kim mũi chỉ tại Vân Y Các đều được nghiên cứu kỹ lưỡng từ tư liệu lịch sử, 
            mang đến cho bạn những bộ Việt Phục chuẩn xác, tinh tế và đầy tự hào."
        </p>
        <a href="bosuutap.php" class="btn btn-outline-warning rounded-pill px-5 py-2 fw-bold" style="border-width: 2px;">
            Trải Nghiệm Ngay
        </a>
    </div>
</div>

<?php include 'resources/views/layouts/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Khởi tạo hiệu ứng AOS (Cuộn trang)
    AOS.init({ once: true, offset: 50 });

    // 2. Swiper Danh Mục
    new Swiper('.dmSwiper', {
        slidesPerView: 3,
        centeredSlides: true,
        spaceBetween: 30,
        loop: true,
        autoplay: { delay: 3000, disableOnInteraction: false },
        speed: 800,
        navigation: { nextEl: '.dm-nav-next', prevEl: '.dm-nav-prev' },
        breakpoints: {
            0: { slidesPerView: 1.4, spaceBetween: 15 },
            768: { slidesPerView: 2.2, spaceBetween: 20 },
            1024: { slidesPerView: 3, spaceBetween: 30 },
        }
    });

    // 3. Swiper Sản Phẩm Nổi Bật (Fix lỗi)
    new Swiper('.productSwiper', {
        slidesPerView: 4,
        spaceBetween: 25,
        loop: true,
        autoplay: { delay: 4000, disableOnInteraction: false, pauseOnMouseEnter: true },
        speed: 600,
        pagination: { el: '.swiper-pagination', clickable: true },
        navigation: { nextEl: '.prod-nav-next', prevEl: '.prod-nav-prev' },
        breakpoints: {
            0: { slidesPerView: 1, spaceBetween: 15 },
            576: { slidesPerView: 2, spaceBetween: 20 },
            992: { slidesPerView: 3, spaceBetween: 25 },
            1200: { slidesPerView: 4, spaceBetween: 25 },
        }
    });

});
</script>