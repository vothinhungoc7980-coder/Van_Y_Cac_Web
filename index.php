<?php
session_start();
include 'config/db.php';

// KÉO DỮ LIỆU TỪ DATABASE CHO CAROUSEL (Lấy 8 sản phẩm mới nhất)
$sql = "SELECT id, ten_vi AS name, gia_ban AS price, duong_dan AS img FROM san_pham ORDER BY id DESC LIMIT 8";
$result = $conn->query($sql);
$featured_products = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $featured_products[] = $row;
    }
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

<div class="container my-5 py-4">
    <div class="text-center mb-5" data-aos="fade-up">
        <h2 class="text-uppercase fw-bold text-danger title-custom">Khám Phá Thời Kỳ</h2>
        <div class="bg-warning mx-auto mt-2" style="width: 80px; height: 3px;"></div>
    </div>
    
    <div class="row g-4 justify-content-center">
        <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
            <div class="category-card rounded shadow overflow-hidden position-relative bg-light">
                <img src="image/cophuc.jpg" class="w-100 img-contain" alt="Triều Nguyễn">
                <div class="category-overlay d-flex align-items-center justify-content-center">
                    <h5 class="text-white fw-bold mb-0">Triều Nguyễn</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
            <div class="category-card rounded shadow overflow-hidden position-relative bg-light">
                <img src="image/ao-giao-linh.jpg" class="w-100 img-contain" alt="Triều Lê">
                <div class="category-overlay d-flex align-items-center justify-content-center">
                    <h5 class="text-white fw-bold mb-0">Triều Lê Trung Hưng</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3" data-aos="fade-up" data-aos-delay="300">
            <div class="category-card rounded shadow overflow-hidden position-relative bg-light">
                <img src="image/tuthan.jpg" class="w-100 img-contain" alt="Dân Gian">
                <div class="category-overlay d-flex align-items-center justify-content-center">
                    <h5 class="text-white fw-bold mb-0">Dân Gian</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bg-light py-5 border-top border-bottom" id="shop">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4" data-aos="fade-right">
            <div>
                <h2 class="text-uppercase fw-bold text-danger title-custom mb-1">Bộ Sưu Tập Mới Nhất</h2>
                <div class="bg-warning mt-2" style="width: 80px; height: 3px;"></div>
            </div>
            <a href="bosuutap.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Xem tất cả <i class="fas fa-arrow-right ms-1"></i></a>
        </div>

        <div class="swiper productSwiper" data-aos="fade-up" data-aos-duration="1000">
            <div class="swiper-wrapper py-3">
                
                <?php if(!empty($featured_products)): ?>
                    <?php foreach($featured_products as $item): ?>
                    <div class="swiper-slide">
                        <div class="card card-product h-100">
                            <img src="images/<?php echo htmlspecialchars($item['img']); ?>" 
                              
                                 class="card-img-top img-contain p-2" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            
                            <div class="card-body text-center d-flex flex-column">
                                <h6 class="card-title fw-bold flex-grow-1 text-dark"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <h5 class="text-danger fw-bold my-2"><?php echo number_format($item['price'], 0, ',', '.'); ?> ₫</h5>
                                <a href="giohang.php?action=add&id=<?php echo $item['id']; ?>" class="btn btn-custom mt-auto w-100 rounded-pill">Thêm vào giỏ</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center w-100">Chưa có sản phẩm nào trong cơ sở dữ liệu.</p>
                <?php endif; ?>

            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-next" style="color: #8B0000; top: 40%;"></div>
            <div class="swiper-button-prev" style="color: #8B0000; top: 40%;"></div>
        </div>
    </div>
</div>

<div class="container-fluid py-5 my-5" style="background: linear-gradient(rgba(139, 0, 0, 0.85), rgba(139, 0, 0, 0.85)), url('image/dainam.jpg') fixed center center; background-size: cover;">
    <div class="container text-center text-white py-5" data-aos="zoom-in-up">
        <h2 class="fw-bold text-warning mb-4 title-custom">Về Vân Y Các</h2>
        <div class="bg-warning mx-auto mb-4" style="width: 80px; height: 2px;"></div>
        <p class="lead w-75 mx-auto fs-6" style="line-height: 1.8;">
            Chúng tôi không chỉ may trang phục, chúng tôi đang dệt lại những trang sử vàng son của dân tộc. Mỗi đường kim mũi chỉ tại Vân Y Các đều được nghiên cứu kỹ lưỡng từ tư liệu lịch sử, mang đến cho bạn những bộ Việt Phục chuẩn xác, tinh tế và đầy tự hào.
        </p>
        <a href="bosuutap.php" class="btn btn-outline-warning btn-lg mt-4 rounded-pill px-4 fw-bold">Xem Toàn Bộ Sưu Tập</a>
    </div>
</div>

<?php
// Gọi giao diện Footer
include 'resources/views/layouts/footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="public/js/index.js"></script>