<?php
session_start();

// --- PHẦN LOGIC PHP GIỮ NGUYÊN ---
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Xử lý THÊM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $id = $_POST['id'] ?? 'SP001';
    $size = $_POST['size'] ?? 'M';
    $key = $id . '_' . $size;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    if ($quantity < 1) $quantity = 1;
    $item = [
        'id' => $id, 'name' => $_POST['name'] ?? 'Sản phẩm',
        'image' => $_POST['image'] ?? 'https://via.placeholder.com/80',
        'price' => isset($_POST['price']) ? (float)$_POST['price'] : 0,
        'size' => $size, 'quantity' => $quantity
    ];
    if (isset($_SESSION['cart'][$key])) $_SESSION['cart'][$key]['quantity'] += $item['quantity'];
    else $_SESSION['cart'][$key] = $item;
    header('Location: giohang.php'); exit();
}
// Xử lý CẬP NHẬT
if (isset($_GET['action']) && $_GET['action'] == 'update') {
    $key = $_GET['key']; $qty = (int)$_GET['qty'];
    if (isset($_SESSION['cart'][$key])) {
        if ($qty > 0) $_SESSION['cart'][$key]['quantity'] = $qty;
        else unset($_SESSION['cart'][$key]);
    }
    header('Location: giohang.php'); exit();
}
// Xử lý XÓA
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    unset($_SESSION['cart'][$_GET['key']]);
    header('Location: giohang.php'); exit();
}

// Tính tổng tiền mặc định (cho PHP hiển thị lúc đầu)
$total_php = 0;
$count_php = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_php += $item['price'] * $item['quantity'];
    $count_php += $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ Hàng - Vân Y Các</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/giohang.css">
</head>
<body>

    <div class="cart-header mb-4">
        <div class="container d-flex justify-content-between align-items-center">
            <h3 class="mb-0 fw-bold"><i class="fas fa-shopping-cart me-2"></i>Giỏ Hàng</h3>
            <a href="chitiet_sanpham.php" class="btn btn-outline-light rounded-pill btn-sm">Tiếp tục mua</a>
        </div>
    </div>

    <div class="container pb-5">
        <?php if (empty($_SESSION['cart'])): ?>
            <div class="text-center py-5">
                <img src="https://cdn-icons-png.flaticon.com/512/2038/2038854.png" width="100" style="opacity: 0.5;">
                <h4 class="mt-3 text-muted">Giỏ hàng trống</h4>
                <a href="chitiet_sanpham.php" class="btn btn-danger mt-3">Mua ngay</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="custom-card p-3 mb-3">
                        <div class="d-flex align-items-center border-bottom pb-3 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="checkAll" checked>
                                <label class="form-check-label fw-bold ms-2" for="checkAll">Chọn Tất Cả (<?php echo count($_SESSION['cart']); ?> sản phẩm)</label>
                            </div>
                        </div>

                        <?php foreach ($_SESSION['cart'] as $key => $item): 
                            $line_total = $item['price'] * $item['quantity'];
                        ?>
                        <div class="row align-items-center mb-4 cart-item">
                            <div class="col-md-5 d-flex align-items-center">
                                <div class="form-check me-3">
                                    <input class="form-check-input item-checkbox" type="checkbox" 
                                           data-price="<?php echo $line_total; ?>" 
                                           name="selected_items[]" 
                                           value="<?php echo $key; ?>" 
                                           checked>
                                </div>
                                <img src="<?php echo $item['image']; ?>" class="cart-img-thumb me-3">
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark small"><?php echo $item['name']; ?></h6>
                                    <span class="badge bg-light text-secondary border">Size: <?php echo $item['size']; ?></span>
                                </div>
                            </div>
                            
                            <div class="col-md-2 text-center d-none d-md-block text-muted small">
                                <?php echo number_format($item['price'], 0, ',', '.'); ?>₫
                            </div>

                            <div class="col-md-3 d-flex justify-content-center">
                                <div class="qty-container">
                                    <a href="giohang.php?action=update&key=<?php echo $key; ?>&qty=<?php echo $item['quantity'] - 1; ?>" class="btn-qty">-</a>
                                    <input type="text" class="qty-input" value="<?php echo $item['quantity']; ?>" readonly>
                                    <a href="giohang.php?action=update&key=<?php echo $key; ?>&qty=<?php echo $item['quantity'] + 1; ?>" class="btn-qty">+</a>
                                </div>
                            </div>

                            <div class="col-md-2 text-end">
                                <div class="fw-bold text-danger mb-2"><?php echo number_format($line_total, 0, ',', '.'); ?>₫</div>
                                <a href="giohang.php?action=delete&key=<?php echo $key; ?>" class="text-secondary small" onclick="return confirm('Xóa nhé?')"><i class="fas fa-trash-alt"></i> Xóa</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="custom-card summary-card p-4">
                        <h5 class="fw-bold mb-4" style="color: var(--brand-red);">Thanh Toán</h5>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Đã chọn:</span>
                            <span class="fw-bold" id="selected-count"><?php echo count($_SESSION['cart']); ?> món</span>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="text-muted">Tổng tiền:</span>
                            <span class="fw-bold fs-4 text-danger" id="total-money">
                                <?php echo number_format($total_php, 0, ',', '.'); ?>₫
                            </span>
                        </div>

                        <form action="thanhtoan.php" method="POST" id="checkout-form">
                            <button type="button" class="btn-checkout" id="btn-buy">
                                MUA HÀNG NGAY
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script src="public/js/giohang.js"></script>
</body>
</html>