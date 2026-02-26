<?php
session_start();
include 'config/db.php';
include 'resources/views/layouts/header.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Áo Nhật Bình - Vân Y Các</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/bosuutap.css">
</head>
<?php

// 1. Kết nối database
$conn = new mysqli("localhost", "root", "", "van_y_cac");
$conn->set_charset("utf8mb4");

// 2. Viết lệnh gọi (Query) lấy 12 sản phẩm mới nhất
$sql = "SELECT id, ten_vi AS name, gia_ban AS price, duong_dan AS img, 'CỔ PHỤC' AS tag FROM san_pham ORDER BY id DESC LIMIT 12";
$result = $conn->query($sql);

$products = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
?>

<section id="bo-suu-tap" class="collection">
    <div class="container">
        <div class="section-header">
            <span class="sub-title">Vân Y Các</span>
            <h2 class="main-title">TINH HOA DI SẢN</h2>
            <div class="dividing-line"></div>
        </div>

        <div class="collection-grid">
            <?php foreach ($products as $index => $item): ?>
                <div class="product-card" style="animation-delay: <?php echo ($index % 4) * 0.15; ?>s">
                    <div class="product-image-wrapper">
                        <?php if($index < 3): ?>
                            <div class="product-badge">Tuyệt tác</div>
                        <?php endif; ?>

                        <img src="assets/images/<?php echo $item['img']; ?>" 
                             onerror="this.src='https://via.placeholder.com/400x550?text=Van+Y+Cac'" 
                             alt="<?php echo $item['name']; ?>" 
                             class="product-img">
                        
                        <div class="product-actions">
                            <a href="gio-hang.php?action=add&id=<?php echo $item['id']; ?>" class="action-btn text-decoration-none">
                                MUA NGAY
                            </a>
                        </div>
                    </div>
                    <div class="product-content">
                        <p class="category-tag"><?php echo $item['tag']; ?></p>
                        <h3 class="product-name"><?php echo $item['name']; ?></h3>
                        <div class="price-box">
                            <span class="price-val"><?php echo number_format($item['price'], 0, ',', '.'); ?> ₫</span>
                        </div>
                    </div>
                </div>
            <?php endforeach;
             ?>
        </div>
    </div>
</section>
<?php
// 3. Gọi giao diện Footer
include 'resources/views/layouts/footer.php';
?>