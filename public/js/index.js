document.addEventListener("DOMContentLoaded", function () {

    // ==========================================
    // 0. KIỂM TRA LOGIN ĐỂ HIỆN AVATAR
    // ==========================================
    async function checkLogin() {
        const authArea = document.getElementById("authArea");
        if (!authArea) return;

        const res = await fetch("public/api.php?action=getUser");
        const data = await res.json();

        if (data.loggedIn) {
            authArea.innerHTML = `
                <div class="dropdown">
                    <img src="public/images/avatar.png"
                         width="40"
                         height="40"
                         class="rounded-circle"
                         style="cursor:pointer"
                         data-bs-toggle="dropdown">

                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="trangcanhan.php">
                                Trang cá nhân
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item text-danger" href="#" id="btnLogout">
                                Đăng xuất
                            </a>
                        </li>
                    </ul>
                </div>
            `;
        }
    }

    checkLogin();

    // ==========================================
    // 1. MODAL LOGIN / REGISTER
    // ==========================================
    const loginModalEl = document.getElementById('loginModal');
    const registerModalEl = document.getElementById('registerModal');

    var loginModalObj = loginModalEl ? new bootstrap.Modal(loginModalEl) : null;
    var registerModalObj = registerModalEl ? new bootstrap.Modal(registerModalEl) : null;

    const btnToRegister = document.getElementById('btnSwitchToRegister');
    const btnToLogin = document.getElementById('btnSwitchToLogin');

    if (btnToRegister) {
        btnToRegister.addEventListener('click', function (e) {
            e.preventDefault();
            loginModalObj.hide();
            registerModalObj.show();
        });
    }

    if (btnToLogin) {
        btnToLogin.addEventListener('click', function (e) {
            e.preventDefault();
            registerModalObj.hide();
            loginModalObj.show();
        });
    }

    // ==========================================
    // 2. ĐĂNG NHẬP
    // ==========================================
    const loginForm = document.getElementById('loginForm');
    const loginMsg = document.getElementById('loginMessage');

    if (loginForm) {
        loginForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const data = Object.fromEntries(new FormData(loginForm));
            const result = await fetchAPI('login', 'POST', data);

            loginMsg.classList.remove('d-none');

            if (result.success) {
                loginMsg.className = 'alert alert-success text-center';
                loginMsg.textContent = 'Đăng nhập thành công!';

                setTimeout(() => {
                    window.location.reload();
                }, 1000);

            } else {
                loginMsg.className = 'alert alert-danger text-center';
                loginMsg.textContent = result.message;
            }
        });
    }

    // ==========================================
    // 3. ĐĂNG KÝ
    // ==========================================
    const registerForm = document.getElementById('registerForm');
    const registerMsg = document.getElementById('registerMessage');

    if (registerForm) {
        registerForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const data = Object.fromEntries(new FormData(registerForm));

            if (data.password !== data.confirm_password) {
                registerMsg.classList.remove('d-none');
                registerMsg.className = 'alert alert-danger text-center';
                registerMsg.textContent = 'Mật khẩu nhập lại không khớp!';
                return;
            }

            const result = await fetchAPI('register', 'POST', data);

            registerMsg.classList.remove('d-none');

            if (result.success) {
                registerMsg.className = 'alert alert-success text-center';
                registerMsg.textContent = 'Đăng ký thành công! Vui lòng đăng nhập.';

                setTimeout(() => {
                    registerModalObj.hide();
                    loginModalObj.show();
                    registerForm.reset();
                }, 1200);

            } else {
                registerMsg.className = 'alert alert-danger text-center';
                registerMsg.textContent = result.message;
            }
        });
    }

});
document.addEventListener("DOMContentLoaded", function () {
    
    // 1. Khởi tạo hiệu ứng cuộn trang AOS
    AOS.init({
        duration: 800, 
        easing: 'ease-out-cubic',
        once: true, 
        offset: 50 
    });

    // 2. Khởi tạo Swiper (Hiển thị 4 sản phẩm / hàng)
    var swiper = new Swiper(".productSwiper", {
        slidesPerView: 1, // Trên điện thoại hiện 1 hình
        spaceBetween: 20, // Khoảng cách giữa các khung
        loop: true, // Vuốt tròn vòng
        autoplay: {
            delay: 3000, 
            disableOnInteraction: false,
        },
        pagination: {
            el: ".swiper-pagination",
            clickable: true,
        },
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
        // Chỉnh sửa theo kích thước màn hình
        breakpoints: {
            576: {
                slidesPerView: 2, // Màn hình nhỏ hiện 2
                spaceBetween: 20,
            },
            768: {
                slidesPerView: 3, // Tablet hiện 3
                spaceBetween: 25,
            },
            1024: {
                slidesPerView: 4, // Máy tính hiện 4 sản phẩm
                spaceBetween: 30,
            },
        },
    });
});

