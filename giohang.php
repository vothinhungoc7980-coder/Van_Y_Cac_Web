<?php
session_start();
include 'config/db.php';

// Đồng bộ session
if (isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $_SESSION['user']['id'];
    $_SESSION['ho_ten']  = $_SESSION['user']['fullname'];
    $_SESSION['vai_tro'] = $_SESSION['user']['role'];
}

// XỬ LÝ ACTION XÓA SẢN PHẨM BẰNG PHP GET
if (isset($_GET['action'])) {
    $da_login = isset($_SESSION['user_id']) || isset($_SESSION['user']);
    if (!$da_login) { header('Location: index.php'); exit; }
    
    $uid = (int)($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0);
    
    if ($_GET['action'] === 'remove' && isset($_GET['gh_id'])) {
        $conn->query("DELETE FROM gio_hang WHERE id=".(int)$_GET['gh_id']." AND id_khach_hang=$uid");
    }
    if ($_GET['action'] === 'clear') {
        $conn->query("DELETE FROM gio_hang WHERE id_khach_hang=$uid");
    }
    header('Location: giohang.php'); 
    exit;
}

// LẤY DỮ LIỆU GIỎ HÀNG
$cart_items   = [];
$tong_tien    = 0;
$tong_sl      = 0;
$da_dang_nhap = isset($_SESSION['user_id']) || isset($_SESSION['user']);

if ($da_dang_nhap) {
    $uid = (int)($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0);
    $rs  = $conn->query("
        SELECT gh.id AS gh_id, gh.so_luong, gh.size,
               sp.id AS sp_id, sp.ten_vi, sp.gia_ban, sp.gia_goc,
               sp.duong_dan, sp.so_luong_ton, sp.slug
        FROM gio_hang gh
        JOIN san_pham sp ON gh.id_san_pham = sp.id
        WHERE gh.id_khach_hang = $uid AND sp.trang_thai = 1
        ORDER BY gh.ngay_them DESC
    ");
    while ($r = $rs->fetch_assoc()) {
        $r['tien_dong'] = $r['gia_ban'] * $r['so_luong'];
        $tong_tien     += $r['tien_dong'];
        $tong_sl       += $r['so_luong'];
        $cart_items[]   = $r;
    }
}

$phi_ship   = $tong_tien >= 500000 ? 0 : 30000;
$thanh_tien = $tong_tien + $phi_ship;

// 1. GỌI HEADER (Gánh toàn bộ HTML, Head, Body, Menu, Bootstrap)
include 'resources/views/layouts/header.php';
?>

<style>
:root {
    --cr: #8B0000; --cr2: #5C0000; --go: #C9A84C; --pa: #FAF6EE; 
    --ink: #1A0A0A; --mu: #6B6B6B; --bd: #E8E1D5; 
    --fd: 'Cormorant Garamond', Georgia, serif; 
    --fb: 'EB Garamond', Georgia, serif;
}
.cart-hero { background: linear-gradient(135deg, #1A0A0A 0%, #3D0000 100%); padding: 28px 0; }
.cart-hero-inner { max-width: 1160px; margin: 0 auto; padding: 0 20px; display: flex; align-items: center; justify-content: space-between; }
.cart-hero h1 { font-family: var(--fd); font-size: 1.7rem; font-weight: 700; color: var(--go); display: flex; align-items: center; gap: 10px; margin:0;}
.cart-hero-sub { font-size: .82rem; color: rgba(255,255,255,.5); margin-top: 3px; }
.btn-continue { color: rgba(255,255,255,.7); text-decoration: none; font-size: .82rem; display: flex; align-items: center; gap: 6px; transition: color .2s; }
.btn-continue:hover { color: var(--go); }

.bc { background: #F0E8D8; border-bottom: 1px solid var(--bd); padding: 9px 0; font-size: .78rem; }
.bc-inner { max-width: 1160px; margin: 0 auto; padding: 0 20px; display: flex; gap: 6px; align-items: center; }
.bc a { color: var(--cr); text-decoration: none; }
.bc .sep { color: #ccc; }

.cart-main { max-width: 1160px; margin: 0 auto; padding: 24px 20px 60px; font-family: var(--fb); color: var(--ink);}
.cart-layout { display: grid; grid-template-columns: 1fr 360px; gap: 24px; align-items: start; }
.cart-card { background: #fff; border: 1px solid var(--bd); border-radius: 8px; overflow: hidden; }
.cart-card-header { padding: 14px 20px; border-bottom: 1px solid var(--bd); display: flex; align-items: center; justify-content: space-between; }
.cart-card-title { font-family: var(--fd); font-size: 1rem; font-weight: 700; color: var(--cr2); display: flex; align-items: center; gap: 8px; }
.btn-clear { font-size: .78rem; color: var(--mu); background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 5px; text-decoration: none; transition: color .2s; }
.btn-clear:hover { color: var(--cr); }

.cart-item { display: grid; grid-template-columns: auto 1fr auto; gap: 14px; padding: 16px 20px; border-bottom: 1px solid var(--bd); align-items: start; transition: background .15s; }
.cart-item:last-child { border-bottom: none; }
.cart-item:hover { background: #FDFAF5; }
.ci-check input { width: 16px; height: 16px; accent-color: var(--cr); cursor: pointer; margin-top: 4px; }
.ci-img-wrap { width: 86px; height: 106px; flex-shrink: 0; background: var(--pa); border: 1px solid var(--bd); border-radius: 6px; overflow: hidden; display: block; }
.ci-img-wrap img { width: 100%; height: 100%; object-fit: cover; transition: transform .3s; }
.ci-img-wrap:hover img { transform: scale(1.05); }

.ci-info { display: flex; gap: 12px; }
.ci-meta { flex: 1; }
.ci-name { font-family: var(--fd); font-size: 1rem; font-weight: 700; color: var(--ink); text-decoration: none; display: block; margin-bottom: 4px; }
.ci-name:hover { color: var(--cr); }
.ci-tags { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 6px; }
.ci-tag { font-size: .67rem; font-weight: 700; padding: 2px 9px; border-radius: 10px; border: 1px solid var(--bd); color: var(--mu); background: #F5F0E8; }
.ci-price-row { display: flex; align-items: center; gap: 8px; }
.ci-price { font-family: var(--fd); font-size: 1.05rem; font-weight: 700; color: var(--cr); }
.ci-price-old { font-size: .78rem; color: #bbb; text-decoration: line-through; }

.ci-actions { display: flex; gap: 8px; margin-top: 10px; align-items: center; }
.qty-ctrl { display: flex; align-items: center; border: 1.5px solid var(--bd); border-radius: 4px; overflow: hidden; }
.qty-btn { width: 30px; height: 30px; background: #F0E8D8; border: none; cursor: pointer; font-size: 1rem; color: var(--cr2); transition: background .2s; font-weight: 700; }
.qty-btn:hover { background: var(--cr); color: #fff; }
.qty-num { width: 38px; height: 30px; border: none; border-left: 1px solid var(--bd); border-right: 1px solid var(--bd); text-align: center; font-size: .88rem; outline: none; background: #fff; }
.btn-remove { font-size: .75rem; color: var(--mu); background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 4px; transition: all .2s; text-decoration:none;}
.btn-remove:hover { color: var(--cr); background: #FEE2E2; }

.ci-subtotal { text-align: right; padding-top: 4px; }
.ci-sub-price { font-family: var(--fd); font-size: 1.05rem; font-weight: 700; color: var(--cr); }
.ci-sub-label { font-size: .68rem; color: var(--mu); margin-top: 1px; }

.empty-cart, .login-wall { text-align: center; padding: 64px 20px; }
.empty-cart i, .login-wall i { font-size: 3rem; color: #d0c4b0; display: block; margin-bottom: 16px; }
.empty-cart h3, .login-wall h3 { font-family: var(--fd); font-size: 1.4rem; color: var(--cr2); margin-bottom: 8px; }
.btn-shop, .btn-login-wall { display: inline-flex; align-items: center; gap: 8px; padding: 11px 26px; background: var(--cr); color: #fff; border-radius: 3px; text-decoration: none; font-family: var(--fd); font-size: .95rem; font-weight: 700; transition: background .2s; border: none; cursor: pointer; }

.summary-card { background: #fff; border: 1px solid var(--bd); border-radius: 8px; overflow: hidden; position: sticky; top: 80px; }
.summary-header { background: linear-gradient(135deg, var(--cr2), var(--cr)); padding: 14px 20px; }
.summary-header h3 { font-family: var(--fd); font-size: 1rem; font-weight: 700; color: var(--go); display: flex; align-items: center; gap: 8px; margin: 0; }
.summary-body { padding: 18px; }
.sum-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 9px; font-size: .88rem; }
.sum-label { color: var(--mu); } .sum-val { font-weight: 600; }
.sum-divider { height: 1px; background: var(--bd); margin: 12px 0; }
.sum-total-row { display: flex; justify-content: space-between; align-items: baseline; }
.sum-total-label { font-family: var(--fd); font-size: 1rem; font-weight: 700; color: var(--cr2); }
.sum-total-val { font-family: var(--fd); font-size: 1.4rem; font-weight: 700; color: var(--cr); }
.btn-checkout { width: 100%; padding: 13px; background: linear-gradient(135deg, var(--cr2), var(--cr)); color: var(--go); border: none; border-radius: 4px; font-family: var(--fd); font-size: 1rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 6px; }
.btn-checkout:hover { opacity: .9; }
.btn-checkout:disabled { opacity: .4; cursor: not-allowed; }

.toast-notif { position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 8px; }
.toast { background: #1A0A0A; color: #fff; padding: 12px 18px; border-radius: 6px; font-size: .83rem; display: flex; align-items: center; gap: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.25); animation: slideIn .3s ease; }
@keyframes slideIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes slideOut { to { opacity: 0; transform: translateX(120%); } }
@media(max-width:900px){.cart-layout{grid-template-columns:1fr;}.summary-card{position:static;}}
@media(max-width:540px){.cart-item{grid-template-columns:1fr;}.ci-info{flex-direction:column;}}
</style>

<div class="cart-hero">
    <div class="cart-hero-inner">
        <div>
            <h1><i class="fas fa-shopping-bag"></i> Giỏ Hàng</h1>
            <div class="cart-hero-sub"><span id="hero-count"><?= $tong_sl ?></span> sản phẩm trong giỏ</div>
        </div>
        <a href="bosuutap.php" class="btn-continue"><i class="fas fa-arrow-left"></i> Tiếp tục mua sắm</a>
    </div>
</div>
<div class="bc">
    <div class="bc-inner">
       <a href="index.php">Trang chủ</a><span class="sep">/</span><a href="bosuutap.php">Bộ sưu tập</a><span class="sep">/</span><span>Giỏ Hàng</span>
    </div>
</div>

<div class="cart-main">
    <?php if (!$da_dang_nhap): ?>
    <div class="login-wall">
        <i class="fas fa-lock"></i>
        <h3>Vui Lòng Đăng Nhập</h3>
        <p>Bạn cần đăng nhập để xem và quản lý giỏ hàng.</p>
        <button class="btn-login-wall" data-bs-toggle="modal" data-bs-target="#loginModal">
            <i class="fas fa-sign-in-alt"></i> Đăng Nhập Ngay
        </button>
    </div>

    <?php elseif (empty($cart_items)): ?>
    <div class="empty-cart">
        <i class="fas fa-shopping-basket"></i>
        <h3>Giỏ Hàng Trống</h3>
        <p>Hãy khám phá bộ sưu tập trang phục truyền thống của chúng tôi!</p>
        <a href="bosuutap.php" class="btn-shop"><i class="fas fa-store"></i> Khám Phá Ngay</a>
    </div>

    <?php else: ?>
    <div class="cart-layout">
        <div>
            <div class="cart-card">
                <div class="cart-card-header">
                    <div class="cart-card-title">
                        <input type="checkbox" id="checkAll" onchange="toggleAll(this)" checked style="width:16px;height:16px;accent-color:var(--cr);cursor:pointer"> 
                        Sản Phẩm Đã Chọn
                    </div>
                    <a href="giohang.php?action=clear" class="btn-clear" onclick="return confirm('Bạn chắc chắn muốn xóa toàn bộ giỏ hàng?')">
                        <i class="fas fa-trash"></i> Xóa tất cả
                    </a>
                </div>

                <?php foreach ($cart_items as $item): ?>
                <div class="cart-item" id="item-<?= $item['gh_id'] ?>">
                    <div class="ci-check">
                        <input type="checkbox" class="item-cb" data-id="<?= $item['gh_id'] ?>" data-price="<?= $item['tien_dong'] ?>" checked onchange="recalcTotal()">
                    </div>
                    <div class="ci-info">
                        <a href="sanpham.php?id=<?= $item['sp_id'] ?>" class="ci-img-wrap">
                            <img src="image/<?= htmlspecialchars($item['duong_dan'] ?? 'no-image.jpg') ?>" onerror="this.src='https://placehold.co/86x106/FAF6EE/8B0000?text=SP'" alt="SP">
                        </a>
                        <div class="ci-meta">
                            <a href="sanpham.php?id=<?= $item['sp_id'] ?>" class="ci-name"><?= htmlspecialchars($item['ten_vi']) ?></a>
                            <div class="ci-tags">
                                <span class="ci-tag" onclick="openSizeModal(<?= $item['gh_id'] ?>, '<?= htmlspecialchars($item['size']) ?>')" style="cursor:pointer;border-color:var(--cr);color:var(--cr)">
                                    <i class="fas fa-ruler-horizontal me-1"></i>Size: <strong><?= htmlspecialchars($item['size'] ?: 'M') ?></strong>
                                    <i class="fas fa-pen ms-1" style="font-size:.6rem"></i>
                                </span>
                            </div>
                            <div class="ci-price-row">
                                <span class="ci-price"><?= number_format($item['gia_ban'],0,',','.') ?> ₫</span>
                            </div>
                            <div class="ci-actions">
                                <div class="qty-ctrl">
                                    <button type="button" class="qty-btn" onclick="changeItemQty(<?= $item['gh_id'] ?>, -1, <?= $item['gia_ban'] ?>)">−</button>
                                    <input type="number" class="qty-num" id="qty-<?= $item['gh_id'] ?>" value="<?= $item['so_luong'] ?>" min="1" max="99" onchange="updateQty(<?= $item['gh_id'] ?>, this, <?= $item['gia_ban'] ?>)">
                                    <button type="button" class="qty-btn" onclick="changeItemQty(<?= $item['gh_id'] ?>, 1, <?= $item['gia_ban'] ?>)">+</button>
                                </div>
                                <a href="giohang.php?action=remove&gh_id=<?= $item['gh_id'] ?>" class="btn-remove" onclick="return confirm('Xóa sản phẩm này?')">
                                    <i class="fas fa-times"></i> Xóa
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="ci-subtotal">
                        <div class="ci-sub-price" id="sub-<?= $item['gh_id'] ?>"><?= number_format($item['tien_dong'],0,',','.') ?> ₫</div>
                        <div class="ci-sub-label">Thành tiền</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <div class="summary-card">
                <div class="summary-header"><h3><i class="fas fa-receipt"></i> Tóm Tắt Đơn Hàng</h3></div>
                <div class="summary-body">
                    <div class="sum-row">
                        <span class="sum-label">Tạm tính (<span id="count-selected"><?= $tong_sl ?></span> sp)</span>
                        <span class="sum-val" id="display-subtotal"><?= number_format($tong_tien,0,',','.') ?> ₫</span>
                    </div>
                    <div class="sum-row">
                        <span class="sum-label">Phí vận chuyển</span>
                        <span id="display-ship">
                            <?php if ($phi_ship === 0): ?><span class="badge bg-success">Miễn phí</span>
                            <?php else: ?><?= number_format($phi_ship,0,',','.') ?> ₫<?php endif; ?>
                        </span>
                    </div>
                    <div class="sum-divider"></div>
                    <div class="sum-total-row">
                        <span class="sum-total-label">Tổng Cộng</span>
                        <span class="sum-total-val" id="display-total"><?= number_format($thanh_tien,0,',','.') ?> ₫</span>
                    </div>
                    
                    <button class="btn-checkout" id="btnCheckout" onclick="goToCheckout()">
                        <i class="fas fa-check-circle"></i> Tiến Hành Đặt Hàng
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div id="sizeModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:10px;padding:24px;max-width:360px;width:90%;font-family:var(--fb)">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
      <h4 style="font-family:var(--fd);color:#5C0000;margin:0;font-weight:700">Đổi Kích Thước</h4>
      <button onclick="closeSizeModal()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:#888">&times;</button>
    </div>
    <div id="sizeModalBtns" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px"></div>
    <button onclick="confirmSize()" style="width:100%;padding:11px;background:#8B0000;color:#C9A84C;border:none;border-radius:4px;font-family:var(--fd);font-size:1.1rem;font-weight:700;cursor:pointer">Lưu Thay Đổi</button>
  </div>
</div>

<div class="toast-notif" id="toastContainer"></div>

<script>
const PHI_SHIP_THRESHOLD = 500000;
const PHI_SHIP_DEFAULT   = 30000;

// === LƯU & KHÔI PHỤC TRẠNG THÁI CHECKBOX ===
function saveCheckedState() {
    const checkedIds = [];
    document.querySelectorAll('.item-cb:checked').forEach(cb => checkedIds.push(cb.dataset.id));
    sessionStorage.setItem('cart_checked', JSON.stringify(checkedIds));
}

function restoreCheckedState() {
    const saved = sessionStorage.getItem('cart_checked');
    if (saved !== null) {
        const checkedIds = JSON.parse(saved);
        document.querySelectorAll('.item-cb').forEach(cb => {
            cb.checked = checkedIds.includes(cb.dataset.id);
        });
    }
    recalcTotal();
}

// === LOGIC TÍNH TIỀN & CHECKBOX ===
function toggleAll(checkAllBox) {
    document.querySelectorAll('.item-cb').forEach(cb => cb.checked = checkAllBox.checked);
    recalcTotal();
}

function recalcTotal() {
    let sub = 0, count = 0;
    const cbs = document.querySelectorAll('.item-cb');
    cbs.forEach(cb => { 
        if(cb.checked) { sub += parseFloat(cb.dataset.price) || 0; count++; } 
    });
    
    const checkAll = document.getElementById('checkAll'); 
    if(checkAll) checkAll.checked = (count > 0 && count === cbs.length);
    
    const ship = (sub >= PHI_SHIP_THRESHOLD || sub === 0) ? 0 : PHI_SHIP_DEFAULT;
    
    const elSub = document.getElementById('display-subtotal');
    if(elSub) elSub.textContent = fmt(sub) + ' ₫';
    
    const elTotal = document.getElementById('display-total');
    if(elTotal) elTotal.textContent = fmt(sub + ship) + ' ₫';
    
    const elCount = document.getElementById('count-selected');
    if(elCount) elCount.textContent = count;
    
    const elShip = document.getElementById('display-ship');
    if(elShip) elShip.innerHTML = ship === 0 ? '<span class="badge bg-success">Miễn phí</span>' : fmt(ship) + ' ₫';
    
    const btnCheck = document.getElementById('btnCheckout');
    if(btnCheck) btnCheck.disabled = (count === 0);

    saveCheckedState();
}

function fmt(n) { return Math.round(n).toLocaleString('vi-VN'); }

function showToast(msg, type='success') {
    let c = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    t.innerHTML = `<span>${msg}</span>`;
    c.appendChild(t);
    setTimeout(() => { t.style.animation = 'slideOut .3s ease forwards'; setTimeout(() => t.remove(), 300); }, 2500);
}

// === TƯƠNG TÁC API: TĂNG GIẢM SL & ĐỔI SIZE ===
async function changeItemQty(ghId, delta, unitPrice) {
    const inp = document.getElementById('qty-' + ghId);
    if (!inp) return;
    let val = parseInt(inp.value) + delta;
    if(val < 1) val = 1;
    
    try {
        const res = await fetch('public/api.php?action=cart', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update', gh_id: ghId, so_luong: val })
        });
        const data = await res.json();
        if(data.success) location.reload(); 
        else showToast(data.message || 'Lỗi cập nhật', 'warning');
    } catch(e) { showToast('Lỗi kết nối máy chủ', 'warning'); }
}

async function updateQty(ghId, inp, unitPrice) {
    let val = parseInt(inp.value) || 1;
    if(val < 1) val = 1;
    await changeItemQty(ghId, 0, unitPrice); 
}

const SIZES = ['S','M','L','XL','2XL'];
let _sizeGhId = null, _sizeSelected = '';

function openSizeModal(ghId, curSize) {
    _sizeGhId = ghId; _sizeSelected = curSize;
    let html = '';
    SIZES.forEach(s => {
        let isSel = (s === curSize);
        html += `<button type="button" onclick="selectModalSize('${s}')" id="msz-${s}"
            style="width:46px;height:46px;border:2px solid ${isSel?'#8B0000':'#E8E1D5'};border-radius:4px;
            background:${isSel?'#8B0000':'#fff'};color:${isSel?'#C9A84C':'#444'};
            font-weight:700;cursor:pointer;transition:all .2s">${s}</button>`;
    });
    document.getElementById('sizeModalBtns').innerHTML = html;
    document.getElementById('sizeModal').style.display = 'flex';
}

function selectModalSize(s) {
    _sizeSelected = s;
    SIZES.forEach(sz => {
        const b = document.getElementById('msz-' + sz);
        if(b) {
            b.style.borderColor = (sz === s) ? '#8B0000' : '#E8E1D5';
            b.style.background  = (sz === s) ? '#8B0000' : '#fff';
            b.style.color       = (sz === s) ? '#C9A84C' : '#444';
        }
    });
}

function closeSizeModal() { document.getElementById('sizeModal').style.display = 'none'; }

async function confirmSize() {
    if(!_sizeGhId || !_sizeSelected) return;
    try {
        const res = await fetch('public/api.php?action=cart', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'change_size', gh_id: _sizeGhId, size: _sizeSelected })
        });
        const data = await res.json();
        if(data.success) location.reload(); 
        else showToast(data.message || 'Không thể đổi size', 'warning');
    } catch(e) { showToast('Lỗi kết nối máy chủ', 'warning'); }
}

// === CHUYỂN TRANG THANH TOÁN ===
function goToCheckout() {
    const sel = [];
    document.querySelectorAll('.item-cb:checked').forEach(cb => sel.push(cb.dataset.id));
    if(sel.length === 0) {
        showToast('Vui lòng chọn ít nhất 1 sản phẩm!', 'warning'); 
        return;
    }
    const btn = document.getElementById('btnCheckout');
    btn.disabled = true; 
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang chuyển...';
    window.location.href = 'thanhtoan.php?items=' + sel.join(',');
}

document.addEventListener('DOMContentLoaded', function() {
    restoreCheckedState();
});
</script>

<?php include 'resources/views/layouts/footer.php'; ?>