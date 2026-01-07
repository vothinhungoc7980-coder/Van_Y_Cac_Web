<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vân Y Các - Cổ Phục Việt</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
/* --- CSS MENU DROPDOWN CẢI TIẾN --- */

/* 1. Xử lý hiển thị khi Hover (Giữ nguyên logic của bạn nhưng mượt hơn) */
@media (min-width: 992px) {
    .parent-hover:hover .dropdown-menu {
        display: block;
        margin-top: 0;
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
}

/* 2. Khung Menu Con */
.custom-dropdown {
    display: none;
    border: none;
    /* Thêm viền vàng trên cùng để tạo điểm nhấn sang trọng */
    border-top: 3px solid #ffc107; 
    border-radius: 0 0 8px 8px;
    /* Đổ bóng sâu hơn một chút cho nổi bật */
    box-shadow: 0 10px 30px rgba(139, 0, 0, 0.1); 
    padding: 8px 0; /* Cách trên dưới một chút cho thoáng */
    background-color: #fff;
    min-width: 220px; /* Rộng hơn xíu để chữ không bị chật */
    
    transition: all 0.3s ease;
    transform: translateY(15px); 
    opacity: 0;
}

/* 3. Từng mục menu con */
.dropdown-item {
    padding: 12px 25px; /* Tăng khoảng cách lề */
    font-size: 15px;
    font-weight: 500; /* Chữ đậm hơn xíu cho rõ */
    color: #444;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); /* Chuyển động nảy nhẹ */
    font-family: 'Poppins', sans-serif;
    position: relative;
}

/* 4. Hiệu ứng Hover vào mục con */
.dropdown-item:hover {
    background-color: #8B0000; /* Đỏ đô thuần (bỏ transparency để chữ vàng rõ hơn) */
    color: #ffc107; /* Vàng kim */
    padding-left: 35px; /* Trượt sang phải nhiều hơn chút */
    box-shadow: inset 5px 0 0 #ffc107; /* Thêm vạch vàng bên trái khi hover */
}

/* 5. Đường kẻ ngăn cách (Style đường chỉ may) */
.custom-dropdown li:not(:last-child) {
    /* Dùng border-bottom cho thẻ li thay vì thẻ a để không bị vỡ layout khi hover */
    border-bottom: 1px dashed rgba(139, 0, 0, 0.15); /* Nét đứt màu đỏ nhạt */
}

/* 6. Mũi tên chỉ xuống ở menu cha */
.nav-link.dropdown-toggle::after {
    transition: transform 0.3s ease;
    margin-left: 8px;
}
.parent-hover:hover .nav-link.dropdown-toggle::after {
    transform: rotate(180deg);
}      /* --- CSS TÙY CHỈNH --- */
        
        /* 1. Tinh chỉnh Menu */
        .navbar {
            box-shadow: 0 2px 10px rgba(0,0,0,0.2); /* Đổ bóng cho menu */
            padding: 15px 0; /* Tăng độ cao menu chút cho thoáng */
        }
        
        .nav-link {
            font-size: 1.05rem;
            margin: 0 10px;
            transition: all 0.3s;
            position: relative;
        }

        /* Hiệu ứng gạch chân khi di chuột vào menu */
        .nav-link::after {
            content: '';
            display: block;
            width: 0;
            height: 2px;
            background: #ffc107; /* Màu vàng warning */
            transition: width .3s;
            margin-top: 3px;
        }
        .nav-link:hover::after {
            width: 100%;
        }

        /* 2. Banner khổ lớn (Hero Section) */
        .hero-banner {
            position: relative;
            /* Chiều cao = 90% chiều cao màn hình (trừ thanh menu) */
            height: 90vh; 
            background: url('https://images.unsplash.com/photo-1596245195047-4952d7e10815?q=80&w=2070&auto=format&fit=crop') no-repeat center center/cover;
            display: flex;
            align-items: center; /* Căn giữa dọc */
            justify-content: center; /* Căn giữa ngang */
            text-align: center;
            color: white;
            margin-top: -1px; /* Khớp liền mạch với menu */
        }

        /* Lớp phủ tối để chữ dễ đọc hơn */
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4); /* Màu đen mờ 40% */
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2; /* Nổi lên trên lớp phủ */
            max-width: 800px;
            padding: 20px;
        }

        .hero-title {
            font-size: 4rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 30px;
            font-weight: 300;
        }

        .btn-hero {
            padding: 12px 40px;
            font-size: 1.2rem;
            text-transform: uppercase;
            font-weight: bold;
            border-radius: 50px;
            transition: 0.3s;
        }
        
        .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
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

<header class="hero-banner">
    <div class="overlay"></div>
    <div class="hero-content">
        <h1 class="hero-title">Tinh Hoa Cổ Phục Việt</h1>
        <p class="hero-subtitle">Khôi phục vẻ đẹp truyền thống - Kết nối giá trị hiện đại</p>
        <a href="#" class="btn btn-warning btn-hero text-dark">Khám Phá Ngay</a>
    </div>
</header>

<div class="container py-5">
    <h2 class="text-center mb-4" style="color: #8B0000;">Sản Phẩm Mới Nhất</h2>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>