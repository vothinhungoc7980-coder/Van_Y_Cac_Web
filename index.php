<?php
session_start();
include 'config/db.php';
include 'resources/views/layouts/header.php';
?>

<section class="hero-banner text-white text-center">
    <div class="hero-overlay"></div>
    <div class="position-relative z-1 container">
        <h1 class="display-3 fw-bold mb-3">Tinh Hoa Cổ Phục Việt</h1>
        <p class="fs-4 mb-4">Khám phá vẻ đẹp ngàn năm - Nghe AI kể chuyện lịch sử</p>
        <a href="#shop" class="btn btn-warning btn-lg fw-bold px-5">Mua Ngay</a>
    </div>
</section>

<div class="container my-5" id="shop">
    <div class="text-center mb-5">
        <h2 class="text-uppercase fw-bold text-danger">Sản Phẩm Nổi Bật</h2>
        <div class="bg-warning mx-auto" style="width: 60px; height: 3px;"></div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card card-product h-100">
                <img src="https://i.pinimg.com/736x/2c/38/54/2c38548842c00236894c927653245455.jpg" class="card-img-top" alt="Áo Nhật Bình" style="height: 300px; object-fit: cover;">
                <div class="card-body text-center">
                    <h5 class="card-title fw-bold">Áo Nhật Bình</h5>
                    <p class="text-muted small">Gấm thêu cao cấp</p>
                    <h6 class="text-danger fw-bold">1.500.000 đ</h6>
                    <a href="chitietsp.php" class="btn btn-primary">Xem chi tiết</a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card card-product h-100">
                <img src="https://i.pinimg.com/564x/0a/73/45/0a734568e646f906f368953112002302.jpg" class="card-img-top" alt="Áo Tấc" style="height: 300px; object-fit: cover;">
                <div class="card-body text-center">
                    <h5 class="card-title fw-bold">Áo Tấc Xanh</h5>
                    <p class="text-muted small">Lụa tơ tằm</p>
                    <h6 class="text-danger fw-bold">850.000 đ</h6>
                    <a href="chitietsp.php" class="btn btn-primary">Xem chi tiết</a>
                </div>
            </div>
        </div>

        </div>
</div>

<?php
// 3. Gọi giao diện Footer
include 'resources/views/layouts/footer.php';
?>