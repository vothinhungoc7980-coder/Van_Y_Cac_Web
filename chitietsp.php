<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Áo Nhật Bình - Vân Y Các</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/chitietsp.css">
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark sticky-top"> 
        <div class="container-fluid px-lg-5"> 
            <a class="navbar-brand fw-bold text-warning fs-3" href="index.php">
                <i class="fas fa-fan me-2"></i>VÂN Y CÁC
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
          <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
    <li class="nav-item"><a class="nav-link active text-warning" href="index.php">Trang chủ</a></li>
    <li class="nav-item"><a class="nav-link text-white" href="bosuutap.php">Bộ Sưu Tập</a></li>
    
    <li class="nav-item dropdown parent-hover">
        <a class="nav-link dropdown-toggle text-white" href="cophuc.php" id="navbarDropdown" role="button" aria-expanded="false">
            Việt Cổ Phục
        </a>
        <ul class="dropdown-menu custom-dropdown" aria-labelledby="navbarDropdown">
            <li><a class="dropdown-item" href="#">Áo Nhật Bình</a></li>
            <li><a class="dropdown-item" href="#">Áo Giao Lĩnh</a></li>
            <li><a class="dropdown-item" href="#">Áo Tấc</a></li>
            <li><a class="dropdown-item" href="#">Áo Tứ Thân</a></li>
           <li><a class="dropdown-item" href="#">Áo Ngũ Thân</a></li>
            <li><a class="dropdown-item" href="#">Áo Viên lĩnh</a></li>
            <li><a class="dropdown-item" href="#">Áo Yếm</a></li>
        </ul>
    </li>
           <li class="nav-item dropdown parent-hover">
        <a class="nav-link dropdown-toggle text-white" href="cophuc.php" id="navbarDropdown" role="button" aria-expanded="false">
           Trang Phục Truyền Thống
        </a>
        <ul class="dropdown-menu custom-dropdown" aria-labelledby="navbarDropdown">
            <li><a class="dropdown-item" href="#">Áo Dài</a></li>
            <li><a class="dropdown-item" href="#">Áo Bà Ba</a></li>
    </ul>
    </li>
    <li class="nav-item"><a class="nav-link text-white" href="#">Câu chuyện AI</a></li>
</ul>
        
            <div class="d-flex align-items-center ms-lg-4">
                <a href="#" class="btn btn-outline-warning position-relative me-3 rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light">0</span>
                </a>
                <a href="#" class="btn btn-warning text-dark fw-bold px-4 rounded-pill">Đăng nhập</a>
            </div>
        </div>
    </div>
</nav>

    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#" class="text-decoration-none text-secondary">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="#" class="text-decoration-none text-secondary">Cổ Phục Nữ</a></li>
                <li class="breadcrumb-item active text-danger fw-bold" aria-current="page">Nhật Bình - Phượng Bào</li>
            </ol>
        </nav>
    </div>

    <div class="container py-3">
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="main-image-box mb-3">
                    <img src="https://i.pinimg.com/736x/28/dc/03/28dc03c7333502c524e933c0d8327293.jpg" id="mainImage" class="img-fluid w-100" alt="Áo Nhật Bình">
                </div>
                <div class="d-flex gap-2 justify-content-center">
                    <img onclick="changeImage(this)" src="https://i.pinimg.com/736x/28/dc/03/28dc03c7333502c524e933c0d8327293.jpg" class="thumbnail rounded active">
                    <img onclick="changeImage(this)" src="https://i.pinimg.com/564x/e1/9e/2f/e19e2f4178550cb539268f6c388656d0.jpg" class="thumbnail rounded">
                    <img onclick="changeImage(this)" src="https://i.pinimg.com/564x/0f/c6/8e/0fc68e64c4021200f6c2f35759188812.jpg" class="thumbnail rounded">
                </div>
            </div>

            <div class="col-md-6">
                <h2 class="fw-bold mb-2" style="color: var(--brand-red);">Áo Nhật Bình - Họa Tiết Phượng Bào</h2>
                
                <div class="mb-3 d-flex align-items-center">
                    <div class="text-warning small me-2">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                    </div>
                    <span class="text-muted small border-start ps-2">4.8 (128 Đánh giá)</span>
                    <span class="badge bg-success ms-3">Còn hàng</span>
                </div>
                
                <div class="price-tag mb-4 p-3 bg-white rounded shadow-sm border-start border-4 border-danger">
                    <span class="fs-2 fw-bold text-danger">1.250.000₫</span>
                    <span class="text-decoration-line-through text-muted ms-3">1.500.000₫</span>
                    <span class="badge bg-warning text-dark ms-2">-20%</span>
                </div>

                <div class="alert alert-light border border-warning mb-4">
                    <h6 class="fw-bold text-danger"><i class="fas fa-box-open me-2"></i>Bộ sản phẩm bao gồm:</h6>
                    <ul class="mb-0 small text-secondary ps-3">
                        <li>01 Áo Nhật Bình (Gấm thêu hoa văn)</li>
                        <li>01 Áo Ngũ Thân lót trong (Lụa trắng)</li>
                        <li>01 Quần lụa ống rộng</li>
                        <li class="fw-bold text-dark">🎁 Tặng kèm: Mấn đội đầu cùng màu</li>
                    </ul>
                </div>

                <form action="giohang.php" method="POST">
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="id" value="SP001"> <input type="hidden" name="name" value="Áo Nhật Bình - Họa Tiết Phượng Bào">
    <input type="hidden" name="price" value="1250000">
    <input type="hidden" name="image" value="https://i.pinimg.com/736x/28/dc/03/28dc03c7333502c524e933c0d8327293.jpg">

    <div class="mb-4">
        <label class="fw-bold mb-2 d-block">Kích Thước:</label>
        <div class="d-flex gap-2">
            <input type="radio" class="btn-check" name="size" value="S" id="sizeS" autocomplete="off">
            <label class="btn btn-outline-custom px-3" for="sizeS">S</label>

            <input type="radio" class="btn-check" name="size" value="M" id="sizeM" autocomplete="off" checked>
            <label class="btn btn-outline-custom px-3" for="sizeM">M</label>

            <input type="radio" class="btn-check" name="size" value="L" id="sizeL" autocomplete="off">
            <label class="btn btn-outline-custom px-3" for="sizeL">L</label>

            <input type="radio" class="btn-check" name="size" value="XL" id="sizeXL" autocomplete="off">
            <label class="btn btn-outline-custom px-3" for="sizeXL">XL</label>
        </div>
        <a href="#" class="small text-decoration-underline text-muted mt-2 d-inline-block">Bảng size chi tiết</a>
    </div>

    <div class="mb-4">
        <label class="fw-bold mb-2 d-block">Số Lượng:</label>
        <div class="input-group" style="width: 140px;">
            <button class="btn btn-outline-secondary" type="button" onclick="decreaseQuantity()">-</button>
            <input type="text" class="form-control text-center fw-bold" name="quantity" id="quantity" value="1" readonly>
            <button class="btn btn-outline-secondary" type="button" onclick="increaseQuantity()">+</button>
        </div>
    </div>

    <div class="d-grid gap-2 d-md-flex mt-4">
        <button type="submit" class="btn btn-outline-danger btn-lg flex-grow-1">
            <i class="fas fa-cart-plus me-2"></i>Thêm Giỏ Hàng
        </button>
        
        <button type="submit" class="btn btn-buy-now btn-lg flex-grow-1 fw-bold text-uppercase">
            Mua Ngay
        </button>
    </div>
</form>

                    
                <div class="row mt-4 g-2 text-muted small">
                    <div class="col-6"><i class="fas fa-truck text-warning me-1"></i> FreeShip đơn > 500k</div>
                    <div class="col-6"><i class="fas fa-sync-alt text-warning me-1"></i> Đổi trả trong 7 ngày</div>
                    <div class="col-6"><i class="fas fa-check-circle text-warning me-1"></i> Kiểm tra hàng trước khi nhận</div>
                    <div class="col-6"><i class="fas fa-shield-alt text-warning me-1"></i> Bảo hành đường may 6 tháng</div>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <ul class="nav nav-tabs border-bottom border-danger" id="myTab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active text-danger fw-bold bg-light" id="desc-tab" data-bs-toggle="tab" data-bs-target="#desc" type="button">Chi Tiết Sản Phẩm</button>
            </li>
            <li class="nav-item">
                <button class="nav-link text-secondary" id="review-tab" data-bs-toggle="tab" data-bs-target="#review" type="button">Đánh Giá (128)</button>
            </li>
        </ul>
        
        <div class="tab-content p-4 border border-top-0 bg-white shadow-sm" id="myTabContent">
            <div class="tab-pane fade show active" id="desc" role="tabpanel">
                
                <div class="material-showcase bg-light p-4 rounded-3 mb-5 border border-1 border-warning">
                    <h5 class="text-center fw-bold mb-4 text-uppercase" style="color: var(--brand-red); letter-spacing: 2px;">
                        🌟 Tinh Hoa Chất Liệu
                    </h5>
                    <div class="row text-center g-4">
                        <div class="col-md-4">
                            <div class="material-card">
                                <div class="icon-box mb-3 text-warning"><i class="fas fa-feather-alt fa-2x"></i></div>
                                <h6 class="fw-bold">Gấm Thượng Uyển</h6>
                                <p class="small text-muted mb-0">Dòng gấm dệt nổi hoa văn 3D, bắt sáng nhẹ, tạo phong thái quyền quý nhưng vẫn đứng form.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="material-card border-start border-end border-secondary border-opacity-10">
                                <div class="icon-box mb-3 text-warning"><i class="fas fa-wind fa-2x"></i></div>
                                <h6 class="fw-bold">Đông Ấm - Hạ Mát</h6>
                                <p class="small text-muted mb-0">Sợi tự nhiên điều hòa thân nhiệt. Lớp lót lụa Habutai mềm mại, không gây dặm ngứa.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="material-card">
                                <div class="icon-box mb-3 text-warning"><i class="fas fa-gem fa-2x"></i></div>
                                <h6 class="fw-bold">Thêu Tay Tỉ Mỉ</h6>
                                <p class="small text-muted mb-0">Từng đường kim mũi chỉ được gia công chắc chắn. Màu nhuộm thủ công bền bỉ.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <h5 class="fw-bold text-danger mb-3">Nguồn Gốc & Ý Nghĩa</h5>
                        <p class="text-muted text-justify">
                            Áo Nhật Bình là thường phục của Hoàng hậu, Công chúa và Phi tần thuộc cung đình Huế triều Nguyễn. Cái tên "Nhật Bình" xuất phát từ đặc điểm hoa văn ở cổ áo khi ghép lại tạo thành hình chữ nhật ngay trước ngực.
                            Sản phẩm của <strong>Vân Y Các</strong> được phục dựng dựa trên nguyên mẫu lịch sử nhưng tinh chỉnh phom dáng để phù hợp với hình thể người hiện đại.
                        </p>
                        
                        <h5 class="fw-bold text-danger mt-4 mb-3">Thông Số Kỹ Thuật</h5>
                        <table class="table table-bordered table-striped text-sm">
                            <tbody>
                                <tr><th width="30%">Chất liệu chính</th><td>Gấm tơ tằm dệt nổi</td></tr>
                                <tr><th>Chất liệu lót</th><td>Lụa Habutai trắng</td></tr>
                                <tr><th>Họa tiết</th><td>Phượng Bào (Thêu máy & Đính kết thủ công)</td></tr>
                                <tr><th>Màu sắc</th><td>Đỏ Đô (Huyết Dụ)</td></tr>
                                <tr><th>Trọng lượng</th><td>450g (Nhẹ, thoải mái vận động)</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-warning">
                            <h6 class="fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Lưu ý bảo quản</h6>
                            <ul class="small mb-0 ps-3">
                                <li>Nên giặt khô (Dry Clean) là tốt nhất.</li>
                                <li>Giặt tay nhẹ nhàng với dầu gội đầu.</li>
                                <li>Không phơi trực tiếp dưới nắng gắt.</li>
                                <li>Ủi hơi nước ở nhiệt độ thấp.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="review" role="tabpanel">
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-comments fa-3x mb-3"></i>
                    <p>Hiện chưa có đánh giá nào. Bạn hãy là người đầu tiên nhé!</p>
                </div>
            </div>
        </div>
    </div>

    <footer class="py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0 fw-bold text-warning">VÂN Y CÁC - TINH HOA CỔ PHỤC VIỆT</p>
            <small class="text-muted">Địa chỉ: 123 Đường Cổ Ngư, Hà Nội | Hotline: 0987.654.321</small>
        </div>
    </footer>
      <script src="public/js/chitietsp.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>