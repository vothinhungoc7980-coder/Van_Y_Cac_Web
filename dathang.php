<?php
// Kết nối Database (Giữ nguyên phần logic của bạn)
$conn = new mysqli("localhost", "root", "", "van_y_cac");
mysqli_set_charset($conn, 'utf8mb4');

$sql_categories = "SELECT DISTINCT dm.id, dm.ten_danh_muc 
                   FROM danh_muc dm 
                   INNER JOIN san_pham sp ON dm.id = sp.id_danh_muc 
                   WHERE sp.trang_thai = 1";
$res_categories = $conn->query($sql_categories);
$categories = [];
while($row = $res_categories->fetch_assoc()) { $categories[] = $row; }

$sql_products = "SELECT * FROM san_pham WHERE trang_thai = 1";
$res_products = $conn->query($sql_products);
$products = [];
while($row = $res_products->fetch_assoc()) { $products[$row['id_danh_muc']][] = $row; }
include 'resources/views/layouts/header.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt May Cổ Phục - Vân Y Các</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        :root {
            --primary-red: #8b0000;
            --gold-accent: #d4af37;
            --dark-overlay: rgba(0, 0, 0, 0.7);
        }

        body {
            /* Thay đổi đường dẫn 'background.jpg' bằng ảnh gấm đỏ của bạn */
            background: linear-gradient(var(--dark-overlay), var(--dark-overlay)), 
                        url('./image/banner.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #fff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(212, 175, 55, 0.3);
            margin-top: 50px;
            margin-bottom: 50px;
        }

        h2.title {
            color: var(--gold-accent);
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        /* Tabs styling */
        .nav-tabs { border-bottom: 1px solid var(--gold-accent); }
        .nav-tabs .nav-link { 
            color: #ccc; 
            border: none; 
            background: none;
            transition: 0.3s;
        }
        .nav-tabs .nav-link.active { 
            color: var(--gold-accent) !important; 
            background: none; 
            border-bottom: 3px solid var(--gold-accent);
            font-weight: bold;
        }

        /* Product Card */
        .product-card { 
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            transition: 0.3s;
            border-radius: 12px;
        }
        .product-card:hover { 
            border-color: var(--gold-accent); 
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.2);
        }
        .product-card.selected { 
            border: 2px solid var(--gold-accent); 
            background: rgba(139, 0, 0, 0.6); 
        }

        .img-box { height: 200px; border-radius: 10px 10px 0 0; overflow: hidden; }
        .img-box img { width: 100%; height: 100%; object-fit: cover; }

        /* Form styling */
        .measurement-section { 
            background: rgba(255, 255, 255, 0.95); 
            color: #333;
            padding: 25px; 
            border-radius: 15px; 
            border-top: 5px solid var(--primary-red);
        }
        
        .form-control {
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .form-control:focus {
            border-color: var(--primary-red);
            box-shadow: 0 0 0 0.25 darkred;
        }

        .btn-submit {
            background: var(--primary-red);
            color: gold;
            border: 1px solid var(--gold-accent);
            padding: 12px;
            font-weight: bold;
            letter-spacing: 1px;
            transition: 0.3s;
        }
        .btn-submit:hover {
            background: #a00000;
            color: #fff;
            box-shadow: 0 0 10px var(--gold-accent);
        }

        #displaySelection {
            background: #fff3cd;
            border-left: 5px solid var(--gold-accent);
            color: #856404;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="main-container">
        <div class="text-center mb-5">
            <h2 class="fw-bold title">TINH HOA CỔ PHỤC VIỆT</h2>
            <p style="color: var(--gold-accent)">Khôi phục vẻ đẹp truyền thống - Kết nối giá trị hiện đại</p>
        </div>

        <form action="xu-ly-dat-hang.php" method="POST" id="orderForm">
            <div class="row">
                <div class="col-lg-8">
                    <ul class="nav nav-tabs mb-4" id="productTab">
                        <?php foreach($categories as $index => $cat): ?>
                        <li class="nav-item">
                            <button class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#content-<?php echo $cat['id']; ?>" 
                                    type="button">
                                <?php echo $cat['ten_danh_muc']; ?>
                            </button>
                        </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="tab-content">
                        <?php foreach($categories as $index => $cat): ?>
                        <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>" 
                             id="content-<?php echo $cat['id']; ?>">
                            <div class="row g-4">
                                <?php if(isset($products[$cat['id']])): ?>
                                    <?php foreach($products[$cat['id']] as $sp): ?>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="card product-card h-100" onclick="selectProduct(this, <?php echo $sp['id']; ?>, '<?php echo $sp['ten_vi']; ?>')">
                                            <div class="img-box">
                                                <img src="./image/banner.jpg<?php echo $sp['duong_dan']; ?>" alt="Sản phẩm">
                                            </div>
                                            <div class="card-body p-3 text-center">
                                                <div class="mb-2 fw-semibold"><?php echo $sp['ten_vi']; ?></div>
                                                <div style="color: var(--gold-accent);"><?php echo number_format($sp['gia_ban']); ?> VNĐ</div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="measurement-section shadow-lg">
                        <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--primary-red);">PHIẾU ĐẶT MAY</h5>
                        
                        <div id="displaySelection" class="alert py-2 d-none">
                            <small>Mẫu đã chọn:</small><br><strong id="pName"></strong>
                            <input type="hidden" name="id_san_pham" id="pId" required>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold">Họ tên khách hàng</label>
                            <input type="text" name="ho_ten" class="form-control" placeholder="Nguyễn Văn A" required>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold">Số điện thoại</label>
                            <input type="text" name="so_dien_thoai" class="form-control" placeholder="090..." required>
                        </div>
                        
                      <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="small fw-bold">Chiều Cao (cm)</label>
                        <input type="number" name="chieu_cao" class="form-control" 
                            min="50" max="250" step="0.1" required 
                            oninvalid="this.setCustomValidity('Chiều cao phải từ 50 đến 250 cm')"
                            oninput="this.setCustomValidity('')">
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold">Cân Nặng (kg)</label>
                        <input type="number" name="can_nang" class="form-control" 
                            min="20" max="200" step="0.1" required
                            oninvalid="this.setCustomValidity('Cân nặng phải từ 20 đến 200 kg')"
                            oninput="this.setCustomValidity('')">
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <label class="small">Vòng 1</label>
                        <input type="number" name="vong_1" class="form-control" min="40" max="200">
                    </div>
                    <div class="col-4">
                        <label class="small">Vòng 2</label>
                        <input type="number" name="vong_2" class="form-control" min="40" max="200">
                    </div>
                    <div class="col-4">
                        <label class="small">Vòng 3</label>
                        <input type="number" name="vong_3" class="form-control" min="40" max="200">
                    </div>
                </div>

                       <div class="mt-4">
    <button type="submit" class="btn btn-submit w-100 shadow">GỬI YÊU CẦU ĐẶT MAY</button>
    
    <div class="alert mt-3 text-center" style="background: rgba(212, 175, 55, 0.1); border: 1px solid var(--gold-accent); color: var(--gold-accent);">
        <i class="bi bi-clock-history"></i> 
        <strong>Lưu ý:</strong> Sản phẩm may đo thủ công, dự kiến trả hàng sau <strong>30 ngày</strong> kể từ khi xác nhận số đo.
    </div>
</div>
                        <p class="text-center small text-muted mt-3"><i>Chúng tôi sẽ liên hệ lại để tư vấn chi tiết hơn.</i></p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function selectProduct(element, id, name) {
    // Xóa class selected của tất cả các card
    document.querySelectorAll('.product-card').forEach(c => c.classList.remove('selected'));
    
    // Thêm class cho card được click
    element.classList.add('selected');
    
    // Cập nhật thông tin vào form
    document.getElementById('pId').value = id;
    document.getElementById('pName').innerText = name;
    document.getElementById('displaySelection').classList.remove('d-none');
    
    // Hiệu ứng cuộn mượt đến form trên mobile
    if(window.innerWidth < 992) {
        document.querySelector('.measurement-section').scrollIntoView({ behavior: 'smooth' });
    }
}
</script>
</body>
</html>
<?php include 'resources/views/layouts/footer.php'; ?>