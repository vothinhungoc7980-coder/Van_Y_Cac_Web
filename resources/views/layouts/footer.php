<footer style="background:linear-gradient(135deg,#1A0A0A,#2D0000);color:rgba(255,255,255,.6);padding:40px 0 20px;font-family:'EB Garamond',Georgia,serif;margin-top:60px">
  <div style="max-width:1200px;margin:0 auto;padding:0 28px">
    <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:32px;margin-bottom:32px">
      <div>
        <div style="font-family:'Cormorant Garamond',Georgia,serif;font-size:1.4rem;font-weight:700;color:#C9A84C;margin-bottom:8px">Vân Y Các</div>
        <div style="font-size:.7rem;letter-spacing:3px;color:rgba(201,168,76,.5);text-transform:uppercase;margin-bottom:14px">Tinh Hoa Di Sản Việt</div>
        <p style="font-size:.82rem;line-height:1.7;margin-bottom:14px">Thương hiệu thời trang truyền thống Việt Nam, tôn vinh vẻ đẹp áo dài và trang phục cổ phục qua từng đường may tinh tế.</p>
        <div style="display:flex;gap:10px">
          <?php foreach ([['fab fa-facebook-f','#1877F2'],['fab fa-instagram','#E4405F'],['fab fa-tiktok','#000'],['fab fa-youtube','#FF0000']] as [$ic,$cl]): ?>
          <a href="#" style="width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.7);text-decoration:none;transition:background .2s;font-size:.85rem" onmouseover="this.style.background='<?= $cl ?>33'" onmouseout="this.style.background='rgba(255,255,255,.08)'"><i class="<?= $ic ?>"></i></a>
          <?php endforeach; ?>
        </div>
      </div>
      <div>
        <h6 style="color:#C9A84C;font-family:'Cormorant Garamond',Georgia,serif;font-size:.95rem;font-weight:700;letter-spacing:1px;margin-bottom:14px">Sản Phẩm</h6>
        <?php foreach ([['Trang Phục Nữ','bosuutap.php?gt=nu'],['Trang Phục Nam','bosuutap.php?gt=nam'],['Áo Dài Cưới','bosuutap.php'],['Phụ Kiện','bosuutap.php'],['Sale','bosuutap.php']] as [$t,$u]): ?>
        <a href="<?= $u ?>" style="display:block;color:rgba(255,255,255,.55);text-decoration:none;font-size:.82rem;margin-bottom:7px;transition:color .2s" onmouseover="this.style.color='#C9A84C'" onmouseout="this.style.color='rgba(255,255,255,.55)'"><?= $t ?></a>
        <?php endforeach; ?>
      </div>
      <div>
        <h6 style="color:#C9A84C;font-family:'Cormorant Garamond',Georgia,serif;font-size:.95rem;font-weight:700;letter-spacing:1px;margin-bottom:14px">Hỗ Trợ</h6>
        <?php foreach ([['Hướng dẫn chọn size','#'],['Chính sách đổi trả','#'],['Thanh toán & Giao hàng','#'],['Liên hệ','#'],['FAQ','#']] as [$t,$u]): ?>
        <a href="<?= $u ?>" style="display:block;color:rgba(255,255,255,.55);text-decoration:none;font-size:.82rem;margin-bottom:7px;transition:color .2s" onmouseover="this.style.color='#C9A84C'" onmouseout="this.style.color='rgba(255,255,255,.55)'"><?= $t ?></a>
        <?php endforeach; ?>
      </div>
      <div>
        <h6 style="color:#C9A84C;font-family:'Cormorant Garamond',Georgia,serif;font-size:.95rem;font-weight:700;letter-spacing:1px;margin-bottom:14px">Liên Hệ</h6>
        <div style="font-size:.82rem;line-height:2">
          <div><i class="fas fa-map-marker-alt me-2" style="color:#C9A84C;width:14px"></i>123 Đường Di Sản, Q.1, TP.HCM</div>
          <div><i class="fas fa-phone me-2" style="color:#C9A84C;width:14px"></i>0987 654 321</div>
          <div><i class="fas fa-envelope me-2" style="color:#C9A84C;width:14px"></i>hello@vanyycac.vn</div>
          <div style="margin-top:10px"><i class="fas fa-clock me-2" style="color:#C9A84C;width:14px"></i>8h–21h mỗi ngày</div>
        </div>
      </div>
    </div>
    <div style="border-top:1px solid rgba(255,255,255,.08);padding-top:18px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
      <div style="font-size:.75rem">© <?= date('Y') ?> Vân Y Các. Bảo lưu mọi quyền.</div>
      <div style="display:flex;gap:12px;font-size:.7rem">
        <a href="#" style="color:rgba(255,255,255,.4);text-decoration:none">Chính sách bảo mật</a>
        <a href="#" style="color:rgba(255,255,255,.4);text-decoration:none">Điều khoản sử dụng</a>
      </div>
    </div>
  </div>
</footer>
<script src="public/js/index.js"></script>
