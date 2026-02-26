<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vân Y Các - Cổ Phục Việt</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
 
    <style>
        /* --- 1. CSS MENU & HERO --- */
        @media (min-width: 992px) {
            .parent-hover:hover > .dropdown-menu {
                display: block; margin-top: 0; opacity: 1; visibility: visible; transform: translateY(0);
            }
        }
        .custom-dropdown {
            border: none; border-top: 3px solid #ffc107;
            border-radius: 0 0 8px 8px; box-shadow: 0 10px 30px rgba(139, 0, 0, 0.1);
            padding: 8px 0; background-color: #fff; min-width: 220px;
            
        }
        .dropdown-item {
            padding: 12px 25px; font-size: 15px; font-weight: 500; color: #444;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); font-family: 'Poppins', sans-serif; position: relative;
        }
        .dropdown-item:hover {
            background-color: #8B0000; color: #ffc107; padding-left: 35px; box-shadow: inset 5px 0 0 #ffc107;
        }
        .custom-dropdown li:not(:last-child) { border-bottom: 1px dashed rgba(139, 0, 0, 0.15); }
        .nav-link.dropdown-toggle::after { transition: transform 0.3s ease; margin-left: 8px; }
        .parent-hover:hover .nav-link.dropdown-toggle::after { transform: rotate(180deg); }
        
        .navbar { box-shadow: 0 2px 10px rgba(0,0,0,0.2); padding: 15px 0; }
        .nav-link { font-size: 1.05rem; margin: 0 10px; transition: all 0.3s; position: relative; }
        .nav-link::after {
            content: ''; display: block; width: 0; height: 2px; background: #ffc107;
            transition: width .3s; margin-top: 3px;
        }
        .nav-link:hover::after { width: 100%; }

        .hero-banner {
            position: relative; height: 90vh;
            background: url('https://images.unsplash.com/photo-1596245195047-4952d7e10815?q=80&w=2070&auto=format&fit=crop') no-repeat center center/cover;
            display: flex; align-items: center; justify-content: center;
            text-align: center; color: white; margin-top: -1px;
        }
        .overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.4); z-index: 1; }
        .hero-content { position: relative; z-index: 2; max-width: 800px; padding: 20px; }
        .hero-title { font-size: 4rem; font-weight: 700; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 2px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); }
        .hero-subtitle { font-size: 1.5rem; margin-bottom: 30px; font-weight: 300; }
        .btn-hero { padding: 12px 40px; font-size: 1.2rem; text-transform: uppercase; font-weight: bold; border-radius: 50px; transition: 0.3s; }
        .btn-hero:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.3); }

        /* --- 2. CSS CHO MODAL ĐĂNG NHẬP/ĐĂNG KÝ --- */
        .modal-content { border-radius: 16px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .modal-header { border-bottom: none; padding-bottom: 0; }
        .modal-title { font-weight: 700; font-size: 1.5rem; color: #8B0000; }
        .form-control { border-radius: 8px; padding: 12px; background-color: #f9f9f9; border: 1px solid #eee; }
        .form-control:focus { box-shadow: none; border-color: #8B2323; background-color: #fff; }
        
        .btn-primary-custom {
            background: linear-gradient(90deg, #8B0000, #B22222);
            border: none; padding: 12px; border-radius: 8px; font-weight: 600; width: 100%; color: white; transition: all 0.3s;
        }
        .btn-primary-custom:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(139, 0, 0, 0.3); color: #ffc107; }
        
        .btn-google { background: #fff; border: 1px solid #ddd; color: #555; font-weight: 500; }
        .btn-google:hover { background: #f1f1f1; }
        
        .divider-text { position: relative; text-align: center; margin: 20px 0; }
        .divider-text::before { content: ""; position: absolute; top: 50%; left: 0; width: 100%; height: 1px; background: #eee; z-index: 0; }
        .divider-text span { background: #fff; padding: 0 10px; position: relative; z-index: 1; color: #888; font-size: 0.9rem; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background-color: #8B0000;"> 
    <div class="container-fluid px-5"> 
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
                <a class="nav-link dropdown-toggle text-white" href="cophuc.php" id="navbarDropdown2" role="button" aria-expanded="false">
                    Trang Phục Truyền Thống
                </a>
                <ul class="dropdown-menu custom-dropdown" aria-labelledby="navbarDropdown2">
                    <li><a class="dropdown-item" href="#">Áo Dài</a></li>
                    <li><a class="dropdown-item" href="#">Áo Bà Ba</a></li>
                </ul>
            </li>
            <li class="nav-item"><a class="nav-link text-white" href="#">Câu chuyện AI</a></li>
        </ul>
        
            <div class="d-flex align-items-center ms-lg-4">
                <a href="giohang.php" class="btn btn-outline-warning position-relative me-3 rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light">0</span>
                </a>
                
                <?php if (isset($_SESSION['user'])): ?>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user']['fullname']); ?>&background=ffc107&color=8B0000" alt="Avatar" width="40" height="40" class="rounded-circle me-2 border border-warning">
                            <span class="fw-bold text-warning"><?php echo htmlspecialchars($_SESSION['user']['fullname']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow custom-dropdown" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="trangcanhan.php"><i class="fas fa-user me-2"></i>Trang cá nhân</a></li>
                            
                            <?php if(isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Quản trị viên'): ?>
                                <li><a class="dropdown-item text-primary" href="admin/index.php"><i class="fas fa-cog me-2"></i>Trang quản trị</a></li>
                            <?php endif; ?>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <button type="button" class="btn btn-warning text-dark fw-bold px-4 rounded-pill" data-bs-toggle="modal" data-bs-target="#loginModal">
                        Đăng nhập
                    </button>
                <?php endif; ?>

            </div>
        </div>
    </div>
</nav>

<header class="hero-banner">
    <div class="overlay"></div>
    <div class="hero-content">
        <h1 class="hero-title">Tinh Hoa Cổ Phục Việt</h1>
        <p class="hero-subtitle">Khôi phục vẻ đẹp truyền thống - Kết nối giá trị hiện đại</p>
        <a href="#shop" class="btn btn-warning btn-hero text-dark">Khám Phá Ngay</a>
    </div>
</header>

<div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
            <div class="modal-header">
                <h5 class="modal-title">Đăng Nhập</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="loginForm">
                    <div id="loginMessage" class="alert d-none text-center" style="font-size: 0.9rem;"></div>
                    <div class="mb-3">
                        <input type="text" name="username" class="form-control" placeholder="Tên đăng nhập hoặc Email" required>
                    </div>
                    <div class="mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Mật khẩu" required>
                    </div>
                    
                    <div class="d-flex justify-content-end mb-3">
                        <a href="#" class="text-decoration-none small" style="color: #8B0000;">Quên mật khẩu?</a>
                    </div>

                    <button type="submit" class="btn btn-primary-custom">Đăng Nhập</button>
                </form>

                <div class="divider-text">
                    <span>Hoặc tiếp tục với</span>
                </div>

                <button class="btn btn-google w-100 mb-3 py-2">
                    <img src="https://img.icons8.com/color/16/000000/google-logo.png" class="me-2"> Google
                </button>

                <div class="text-center mt-3">
                    <span class="text-muted small">Chưa có tài khoản? </span>
                    <a href="#" id="btnSwitchToRegister" class="fw-bold text-decoration-none" style="color: #8B0000;">
                        Đăng ký ngay
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="registerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
            <div class="modal-header">
                <h5 class="modal-title">Đăng Ký Tài Khoản</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="registerForm">
                    <div id="registerMessage" class="alert d-none text-center" style="font-size: 0.9rem;"></div>
                    <div class="mb-3">
                        <input type="text" name="fullname" class="form-control" placeholder="Họ và tên" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="username" class="form-control" placeholder="Tên đăng nhập" required>
                    </div>
                    <div class="mb-3">
                        <input type="email" name="email" class="form-control" placeholder="Địa chỉ Email" required>
                    </div>
                     <div class="mb-3">
                      <input type="text" name="phone" class="form-control" 
                          placeholder="Số điện thoại" 
                           pattern="[0-9]{10}" 
                   title="Số điện thoại phải gồm 10 chữ số"
                    required>
                    </div>
                    <div class="mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Mật khẩu" required>
                    </div>
                    <div class="mb-3">
                        <input type="password" name="confirm_password" class="form-control" placeholder="Nhập lại mật khẩu" required>
                    </div>

                    <button type="submit" class="btn btn-primary-custom">Đăng Ký</button>
                </form>

                <div class="text-center mt-4">
                    <span class="text-muted small">Đã có tài khoản? </span>
                    <a href="#" id="btnSwitchToLogin" class="fw-bold text-decoration-none" style="color: #8B0000;">
                        Đăng nhập
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="public/js/api.js"></script> 
<script src="public/js/index.js"></script>
