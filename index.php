<?php
session_start();
include 'config/db.php';

// ===== SẢN PHẨM MỚI NHẤT (8 sp) =====
$sql_sp = "SELECT sp.id, sp.ten_vi AS name, sp.gia_ban AS price,
                  sp.gia_goc, sp.duong_dan AS img, sp.noi_bat,
                  dm.ten_danh_muc
           FROM san_pham sp
           LEFT JOIN danh_muc dm ON sp.id_danh_muc = dm.id
           WHERE sp.trang_thai = 1
           ORDER BY sp.id DESC LIMIT 8";
$result = $conn->query($sql_sp);
$featured_products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $featured_products[] = $row;
    }
}

// ===== DANH MỤC CON để hiện card ảnh (lấy 6 cái) =====
$sql_dm = "SELECT dm.id, dm.ten_danh_muc, dm.slug, dm.hinh_anh,
                  dm_cha.ten_danh_muc AS ten_cha
           FROM danh_muc dm
           LEFT JOIN danh_muc dm_cha ON dm.id_cha = dm_cha.id
           WHERE dm.id_cha IS NOT NULL AND dm.trang_thai = 1
           ORDER BY dm.id_cha ASC, dm.thu_tu ASC
           LIMIT 6";
$result_dm = $conn->query($sql_dm);
$danh_muc_hien = [];
while ($row = $result_dm->fetch_assoc()) {
    $danh_muc_hien[] = $row;
}

include 'resources/views/layouts/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<link rel="stylesheet" href="public/css/home.css">

<header class="hero-banner">
    <div class="overlay"></div>
    <div class="hero-content" data-aos="fade-up" data-aos-duration="1500">
        <h1 class="hero-title">Tinh Hoa Cổ Phục Việt</h1>
        <p class="hero-subtitle">Khôi phục vẻ đẹp truyền thống - Kết nối giá trị hiện đại</p>
        <a href="#shop" class="btn btn-warning btn-hero text-dark fw-bold px-5 py-3 rounded-pill shadow-lg">Khám Phá Ngay</a>
    </div>
</header>

<!-- ===== TÍNH NĂNG ===== -->
<div class="container my-5 pt-4">
    <div class="row text-center g-4">
        <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
            <div class="feature-box p-4 rounded shadow-sm bg-white h-100">
                <i class="fas fa-tape fs-1 text-danger mb-3"></i>
                <h5 class="fw-bold text-dark">May Đo Chuẩn Xác</h5>
                <p class="text-muted small mb-0">Thiết kế theo số đo riêng, tôn vinh vóc dáng người Việt.</p>
            </div>
        </div>
        <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
            <div class="feature-box p-4 rounded shadow-sm bg-white h-100">
                <i class="fas fa-gem fs-1 text-danger mb-3"></i>
                <h5 class="fw-bold text-dark">Chất Liệu Cao Cấp</h5>
                <p class="text-muted small mb-0">Sử dụng tơ tằm, gấm lụa truyền thống tốt nhất.</p>
            </div>
        </div>
        <div class="col-md-3" data-aos="fade-up" data-aos-delay="300">
            <div class="feature-box p-4 rounded shadow-sm bg-white h-100">
                <i class="fas fa-history fs-1 text-danger mb-3"></i>
                <h5 class="fw-bold text-dark">Phục Dựng Tỉ Mỉ</h5>
                <p class="text-muted small mb-0">Nghiên cứu kỹ lưỡng từ các tư liệu lịch sử cổ xưa.</p>
            </div>
        </div>
        <div class="col-md-3" data-aos="fade-up" data-aos-delay="400">
            <div class="feature-box p-4 rounded shadow-sm bg-white h-100">
                <i class="fas fa-truck fs-1 text-danger mb-3"></i>
                <h5 class="fw-bold text-dark">Giao Hàng Tận Nơi</h5>
                <p class="text-muted small mb-0">Đóng gói cẩn thận, vận chuyển an toàn toàn quốc.</p>
            </div>
        </div>
    </div>
</div>

<!-- ===== KHÁM PHÁ DANH MỤC — CAROUSEL ===== -->
<div class="dm-carousel-section py-5">
    <div class="text-center mb-5" data-aos="fade-up">
        <h2 class="text-uppercase fw-bold text-danger title-custom">Khám Phá Bộ Sưu Tập</h2>
        <div class="bg-warning mx-auto mt-2" style="width: 80px; height: 3px;"></div>
    </div>

    <!-- Swiper danh mục — centeredSlides + scale effect -->
    <div class="swiper dmSwiper" data-aos="fade-up" data-aos-duration="800">
        <div class="swiper-wrapper">
            <?php
            // Lấy toàn bộ 9 danh mục con từ $danh_muc_hien
            // (biến này đã được query lấy 6, cần lấy lại toàn bộ 9)
            $sql_dm9 = "SELECT dm.id, dm.ten_danh_muc, dm.slug, dm.hinh_anh,
                               dm_cha.ten_danh_muc AS ten_cha
                        FROM danh_muc dm
                        LEFT JOIN danh_muc dm_cha ON dm.id_cha = dm_cha.id
                        WHERE dm.id_cha IS NOT NULL AND dm.trang_thai = 1
                        ORDER BY dm.id_cha ASC, dm.thu_tu ASC";
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

            while ($dm = $result_dm9->fetch_assoc()):
                $anh = $dm['hinh_anh']
                    ? 'images/' . $dm['hinh_anh']
                    : ($anh_mac_dinh[$dm['slug']] ?? 'image/cophuc.jpg');
            ?>
            <div class="swiper-slide dm-slide">
                <a href="bosuutap.php?danh_muc=<?= $dm['id'] ?>" class="dm-slide-link">
                    <div class="dm-slide-img-wrap">
                        <img src="<?= $anh ?>"
                             alt="<?= htmlspecialchars($dm['ten_danh_muc']) ?>"
                             class="dm-slide-img">
                        <div class="dm-slide-overlay">
                            <div class="dm-slide-text">
                                <?php if ($dm['ten_cha']): ?>
                                <span class="dm-slide-parent"><?= htmlspecialchars($dm['ten_cha']) ?></span>
                                <?php endif; ?>
                                <h4 class="dm-slide-name"><?= htmlspecialchars($dm['ten_danh_muc']) ?></h4>
                                <span class="dm-slide-cta">Khám Phá <i class="fas fa-arrow-right ms-1"></i></span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Navigation -->
        <div class="swiper-button-next dm-nav-next"></div>
        <div class="swiper-button-prev dm-nav-prev"></div>
    </div>

    <div class="text-center mt-5" data-aos="fade-up">
        <a href="bosuutap.php" class="btn btn-outline-danger rounded-pill px-5 py-2">
            Xem Tất Cả Sản Phẩm <i class="fas fa-arrow-right ms-2"></i>
        </a>
    </div>
</div>

<style>
/* ===== DANH MỤC CAROUSEL ===== */
.dm-carousel-section {
    background: #fff;       /* Nền trắng, không tối */
    overflow: hidden;
    position: relative;
    padding: 60px 0;
}

.dmSwiper {
    width: 100%;
    padding: 30px 0 40px !important;
    overflow: visible !important;   /* Slide tràn ra ngoài để thấy 2 bên */
}

/* Clip overflow chỉ ở trục ngang */
.dm-carousel-section { overflow: hidden; }

/* Tất cả slides mặc định: hơi mờ + nhỏ hơn */
.dm-slide {
    width: 320px !important;
    transition: transform 0.5s ease, filter 0.5s ease;
    filter: brightness(0.55);   /* Tối nhẹ — vẫn nhìn thấy ảnh, không tối thui */
    transform: scale(0.88);
    cursor: pointer;
}

/* Slide GIỮA — sáng 100%, to nhất */
.dm-slide.swiper-slide-active {
    filter: brightness(1) !important;
    transform: scale(1.06) !important;
    z-index: 10;
}

/* Slide LIỀN KỀ giữa — sáng 75% */
.dm-slide.swiper-slide-prev,
.dm-slide.swiper-slide-next {
    filter: brightness(0.75);
    transform: scale(0.94);
}

.dm-slide-link { display: block; text-decoration: none; }

.dm-slide-img-wrap {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    height: 400px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    transition: box-shadow 0.4s;
}
.swiper-slide-active .dm-slide-img-wrap {
    box-shadow: 0 20px 50px rgba(0,0,0,0.25);
}

.dm-slide-img {
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}
.dm-slide:hover .dm-slide-img { transform: scale(1.05); }

/* Overlay gradient chỉ ở phần dưới để hiện tên */
.dm-slide-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 55%);
    display: flex; align-items: flex-end;
    padding: 20px 18px;
}

.dm-slide-text { text-align: center; width: 100%; }
.dm-slide-parent {
    display: block;
    color: #C9A84C;
    font-size: 0.62rem; letter-spacing: 3px;
    text-transform: uppercase; margin-bottom: 5px;
}
.dm-slide-name {
    font-family: 'Cormorant Garamond', Georgia, serif;
    color: #fff; font-size: 1.3rem; font-weight: 700;
    margin-bottom: 8px; line-height: 1.2;
}
.dm-slide-cta {
    display: inline-block; color: #C9A84C;
    font-size: 0.75rem; letter-spacing: 2px; text-transform: uppercase;
    opacity: 0; transform: translateY(6px);
    transition: opacity 0.3s, transform 0.3s;
}
.dm-slide:hover .dm-slide-cta,
.swiper-slide-active .dm-slide-cta {
    opacity: 1; transform: translateY(0);
}

/* Nav buttons */
.dm-nav-next, .dm-nav-prev {
    color: #8B0000 !important;
    background: rgba(255,255,255,0.85);
    width: 42px !important; height: 42px !important;
    border-radius: 50%;
    box-shadow: 0 2px 12px rgba(0,0,0,0.15);
    transition: background 0.25s;
}
.dm-nav-next:hover, .dm-nav-prev:hover { background: #fff; }
.dm-nav-next::after, .dm-nav-prev::after { font-size: 15px !important; font-weight: 900 !important; }

@media (max-width: 768px) {
    .dm-slide { width: 240px !important; }
    .dm-slide-img-wrap { height: 280px; }
    .dm-slide-name { font-size: 1rem; }
}
</style>

<script>
// Khởi tạo Swiper danh mục — chạy ngay (không cần chờ DOMContentLoaded vì script ở cuối)
document.addEventListener('DOMContentLoaded', function () {
    new Swiper('.dmSwiper', {
        // Hiện 3 slides, slide giữa to nhất
        slidesPerView: 3,
        centeredSlides: true,
        spaceBetween: 28,
        loop: true,

        // Tự động chạy
        autoplay: {
            delay: 2800,
            disableOnInteraction: false,
            pauseOnMouseEnter: true,
        },

        // Chuyển động mượt
        speed: 700,
        effect: 'slide',

        // Nút điều hướng
        navigation: {
            nextEl: '.dm-nav-next',
            prevEl: '.dm-nav-prev',
        },

        // Responsive
        breakpoints: {
            0:   { slidesPerView: 1.3, spaceBetween: 16 },
            576: { slidesPerView: 2,   spaceBetween: 20 },
            768: { slidesPerView: 3,   spaceBetween: 28 },
        }
    });
});
</script>

<!-- ===== SẢN PHẨM MỚI NHẤT ===== -->
<div class="bg-light py-5 border-top border-bottom" id="shop">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4" data-aos="fade-right">
            <div>
                <h2 class="text-uppercase fw-bold text-danger title-custom mb-1">Bộ Sưu Tập Mới Nhất</h2>
                <div class="bg-warning mt-2" style="width: 80px; height: 3px;"></div>
            </div>
            <a href="bosuutap.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                Xem tất cả <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="swiper productSwiper" data-aos="fade-up" data-aos-duration="1000">
            <div class="swiper-wrapper py-3">
                <?php if (!empty($featured_products)): ?>
                    <?php foreach ($featured_products as $item): ?>
                    <div class="swiper-slide">
                        <div class="card card-product h-100 position-relative">
                            <?php if ($item['noi_bat']): ?>
                            <span class="badge bg-danger position-absolute top-0 start-0 m-2"
                                  style="font-size:0.65rem;letter-spacing:1px;z-index:1">NỔI BẬT</span>
                            <?php endif; ?>
                            <?php if ($item['gia_goc'] && $item['gia_goc'] > $item['price']): ?>
                            <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2"
                                  style="font-size:0.65rem;z-index:1">
                                -<?= round(($item['gia_goc'] - $item['price']) / $item['gia_goc'] * 100) ?>%
                            </span>
                            <?php endif; ?>

                            <img src="images/<?= htmlspecialchars($item['img'] ?? 'no-image.jpg') ?>"
                                 onerror="this.src='https://via.placeholder.com/300x400?text=Vân+Y+Các'"
                                 class="card-img-top img-contain p-2"
                                 alt="<?= htmlspecialchars($item['name']) ?>">

                            <div class="card-body text-center d-flex flex-column">
                                <?php if ($item['ten_danh_muc']): ?>
                                <small class="text-muted" style="font-size:0.7rem;letter-spacing:1px;text-transform:uppercase">
                                    <?= htmlspecialchars($item['ten_danh_muc']) ?>
                                </small>
                                <?php endif; ?>
                                <h6 class="card-title fw-bold flex-grow-1 text-dark mt-1">
                                    <?= htmlspecialchars($item['name']) ?>
                                </h6>
                                <div class="d-flex align-items-center justify-content-center gap-2 my-2">
                                    <h5 class="text-danger fw-bold mb-0">
                                        <?= number_format($item['price'], 0, ',', '.') ?> ₫
                                    </h5>
                                    <?php if ($item['gia_goc'] && $item['gia_goc'] > $item['price']): ?>
                                    <small class="text-muted text-decoration-line-through">
                                        <?= number_format($item['gia_goc'], 0, ',', '.') ?> ₫
                                    </small>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex gap-2 mt-auto">
                                    <a href="sanpham.php?id=<?= $item['id'] ?>"
                                       class="btn btn-outline-danger btn-sm flex-fill rounded-pill">
                                        <i class="fas fa-eye me-1"></i>Xem
                                    </a>
                                    <a href="giohang.php?action=add&id=<?= $item['id'] ?>"
                                       class="btn btn-custom btn-sm flex-fill rounded-pill">
                                        Thêm giỏ
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="swiper-slide">
                        <p class="text-center w-100 text-muted py-5">Chưa có sản phẩm nào.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-next" style="color: #8B0000; top: 40%;"></div>
            <div class="swiper-button-prev" style="color: #8B0000; top: 40%;"></div>
        </div>
    </div>
</div>

<!-- ===== VỀ VÂN Y CÁC ===== -->
<div class="container-fluid py-5 my-5"
     style="background: linear-gradient(rgba(139,0,0,0.85),rgba(139,0,0,0.85)),
            url('image/dainam.jpg') fixed center center; background-size: cover;">
    <div class="container text-center text-white py-5" data-aos="zoom-in-up">
        <h2 class="fw-bold text-warning mb-4 title-custom">Về Vân Y Các</h2>
        <div class="bg-warning mx-auto mb-4" style="width: 80px; height: 2px;"></div>
        <p class="lead w-75 mx-auto fs-6" style="line-height: 1.8;">
            Chúng tôi không chỉ may trang phục, chúng tôi đang dệt lại những trang sử vàng son của dân tộc.
            Mỗi đường kim mũi chỉ tại Vân Y Các đều được nghiên cứu kỹ lưỡng từ tư liệu lịch sử,
            mang đến cho bạn những bộ Việt Phục chuẩn xác, tinh tế và đầy tự hào.
        </p>
        <a href="bosuutap.php" class="btn btn-outline-warning btn-lg mt-4 rounded-pill px-4 fw-bold">
            Xem Toàn Bộ Sưu Tập
        </a>
    </div>
</div>

<?php include 'resources/views/layouts/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="public/js/index.js"></script>
