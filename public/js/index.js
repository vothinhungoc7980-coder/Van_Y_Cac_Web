
async function fetchAPI(action, method = 'GET', data = null) {
    try {
        const response = await fetch(`public/api.php?action=${action}`, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: data ? JSON.stringify(data) : null
        });
        return await response.json();
    } catch (error) {
        console.error("Lỗi API:", error);
        return { success: false, message: "Không thể kết nối server!" };
    }
}

// ─── Helper gọi API giỏ hàng (endpoint riêng) ────────────
async function cartAPI(data) {
    try {
        const res = await fetch('public/api.php?action=cart', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return await res.json();
    } catch (e) {
        return { success: false, message: 'Lỗi kết nối.' };
    }
}

// ─── Cập nhật badge số lượng giỏ hàng ────────────────────
async function updateCartBadge() {
    const data = await cartAPI({ action: 'get' });
    if (data.success) {
        const badge = document.querySelector('a[href="giohang.php"] .badge');
        if (badge) badge.textContent = data.cart_count || 0;
    }
}

// ─── Toast notification ───────────────────────────────────
function showToast(msg, type = 'ok') {
    const colors = { ok: '#155724', err: '#721c24', warn: '#856404' };
    const icons  = { ok: 'fa-check-circle', err: 'fa-times-circle', warn: 'fa-exclamation-triangle' };

    let wrap = document.getElementById('toastWrap');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.id = 'toastWrap';
        wrap.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:8px';
        document.body.appendChild(wrap);
    }
    if (!document.getElementById('_toastStyle')) {
        const s = document.createElement('style');
        s.id = '_toastStyle';
        s.textContent = `@keyframes toastIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}`;
        document.head.appendChild(s);
    }
    const t = document.createElement('div');
    t.style.cssText = `background:${colors[type]};color:#fff;padding:12px 18px;border-radius:8px;font-size:.88rem;display:flex;align-items:center;gap:10px;box-shadow:0 4px 16px rgba(0,0,0,.2);animation:toastIn .3s ease;min-width:220px`;
    t.innerHTML = `<i class="fas ${icons[type]}"></i><span>${msg}</span>`;
    wrap.appendChild(t);
    setTimeout(() => {
        t.style.opacity = '0'; t.style.transition = 'opacity .3s';
        setTimeout(() => t.remove(), 300);
    }, 3200);
}

// ─────────────────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", function () {

    // =================================================
    // 1. MODAL LOGIN / REGISTER
    // =================================================
    const loginModalEl    = document.getElementById('loginModal');
    const registerModalEl = document.getElementById('registerModal');

    var loginModalObj    = loginModalEl    ? new bootstrap.Modal(loginModalEl)    : null;
    var registerModalObj = registerModalEl ? new bootstrap.Modal(registerModalEl) : null;

    // Chuyển đổi giữa 2 modal
    document.getElementById('btnSwitchToRegister')?.addEventListener('click', function (e) {
        e.preventDefault();
        loginModalObj.hide();
        registerModalObj.show();
    });
    document.getElementById('btnSwitchToLogin')?.addEventListener('click', function (e) {
        e.preventDefault();
        registerModalObj.hide();
        loginModalObj.show();
    });

    // =================================================
    // 2. ĐĂNG NHẬP
    // =================================================
    const loginForm = document.getElementById('loginForm');
    const loginMsg  = document.getElementById('loginMessage');

    if (loginForm) {
        loginForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const btn = loginForm.querySelector('[type="submit"]');
            const origText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Đang xử lý...';

            const data   = Object.fromEntries(new FormData(loginForm));
            const result = await fetchAPI('login', 'POST', data);

            loginMsg.classList.remove('d-none');

            if (result.success) {
                loginMsg.className = 'alert alert-success text-center';
                loginMsg.textContent = 'Đăng nhập thành công!';
                // Reload để PHP render lại header đúng session
                setTimeout(() => window.location.reload(), 1000);
            } else {
                loginMsg.className = 'alert alert-danger text-center';
                loginMsg.textContent = result.message || 'Đăng nhập thất bại.';
                btn.disabled = false;
                btn.textContent = origText;
            }
        });
    }

    // =================================================
    // 3. ĐĂNG KÝ
    // =================================================
    const registerForm = document.getElementById('registerForm');
    const registerMsg  = document.getElementById('registerMessage');

    if (registerForm) {
        registerForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const btn = registerForm.querySelector('[type="submit"]');
            const origText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Đang xử lý...';

            const data = Object.fromEntries(new FormData(registerForm));

            if (data.password !== data.confirm_password) {
                registerMsg.classList.remove('d-none');
                registerMsg.className = 'alert alert-danger text-center';
                registerMsg.textContent = 'Mật khẩu nhập lại không khớp!';
                btn.disabled = false;
                btn.textContent = origText;
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
                    registerMsg.classList.add('d-none');
                }, 1200);
            } else {
                registerMsg.className = 'alert alert-danger text-center';
                registerMsg.textContent = result.message || 'Đăng ký thất bại.';
                btn.disabled = false;
                btn.textContent = origText;
            }
        });
    }

    // =================================================
    // 4. THÊM VÀO GIỎ HÀNG (data-add-cart="id_sp")
    // Dùng chung index.php, bosuutap.php, sanpham.php
    // VD: <button data-add-cart="5" data-size="M" data-qty="1">
    // =================================================
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('[data-add-cart]');
        if (!btn) return;
        e.preventDefault();

        // Kiểm tra đăng nhập qua data-logged="1" trên <body> do PHP render
        if (document.body.dataset.logged !== '1') {
            loginModalObj?.show();
            return;
        }

        const spId  = btn.dataset.addCart;
        const size  = btn.dataset.size || '';
        const qty   = parseInt(btn.dataset.qty || '1');
        const isBuy = btn.dataset.buy === '1';

        const origHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

        const result = await cartAPI({
            action: 'add',
            id_san_pham: +spId,
            size: size,
            so_luong: qty
        });

        if (result.need_login) {
            loginModalObj?.show();
            btn.disabled = false; btn.innerHTML = origHTML;
            return;
        }

        if (result.success) {
            // Cập nhật badge
            const badge = document.querySelector('a[href="giohang.php"] .badge');
            if (badge) badge.textContent = result.cart_count || 0;

            showToast('Đã thêm vào giỏ hàng!', 'ok');

            if (isBuy) {
                setTimeout(() => window.location.href = 'giohang.php', 600);
            } else {
                btn.innerHTML = '<i class="fas fa-check me-1"></i>Đã thêm';
                setTimeout(() => { btn.disabled = false; btn.innerHTML = origHTML; }, 2000);
            }
        } else {
            showToast(result.message || 'Không thể thêm vào giỏ.', 'err');
            btn.disabled = false; btn.innerHTML = origHTML;
        }
    });

    // =================================================
    // 5. AOS — Hiệu ứng cuộn trang
    // =================================================
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-out-cubic',
            once: true,
            offset: 50
        });
    }

    // =================================================
    // 6. SWIPER — Slider sản phẩm
    // =================================================
    if (typeof Swiper !== 'undefined' && document.querySelector('.productSwiper')) {
        new Swiper('.productSwiper', {
            slidesPerView: 1,
            spaceBetween: 20,
            loop: true,
            autoplay: { delay: 3000, disableOnInteraction: false },
            pagination: { el: '.swiper-pagination', clickable: true },
            navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
            breakpoints: {
                576:  { slidesPerView: 2, spaceBetween: 20 },
                768:  { slidesPerView: 3, spaceBetween: 25 },
                1024: { slidesPerView: 4, spaceBetween: 30 },
            },
        });
    }

    // =================================================
    // 7. Badge giỏ hàng khi load trang (nếu đã đăng nhập)
    // =================================================
    if (document.body.dataset.logged === '1') {
        updateCartBadge();
    }

}); // end DOMContentLoaded