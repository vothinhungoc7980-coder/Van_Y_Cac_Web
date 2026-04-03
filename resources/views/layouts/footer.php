<footer style="background:linear-gradient(135deg,#1A0A0A,#2D0000);color:rgba(255,255,255,.6);padding:40px 0 20px;font-family:'EB Garamond',Georgia,serif;margin-top:60px">
  <div style="max-width:1200px;margin:0 auto;padding:0 28px">
    <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:32px;margin-bottom:32px">
      <div>
        <div style="font-family:'Cormorant Garamond',Georgia,serif;font-size:1.4rem;font-weight:700;color:#C9A84C;margin-bottom:8px">Vân Y Các</div>
        <div style="font-size:.7rem;letter-spacing:3px;color:rgba(201,168,76,.5);text-transform:uppercase;margin-bottom:14px">Tinh Hoa Di Sản Việt</div>
        <p style="font-size:.82rem;line-height:1.7;margin-bottom:14px">Thương hiệu thời trang truyền thống Việt Nam, tôn vinh vẻ đẹp áo dài và trang phục cổ phục qua từng đường may tinh tế.</p>
       <div style="display:flex;gap:10px">
          <?php 
          $socials = [
              ['fab fa-facebook-f', '#1877F2', 'https://www.facebook.com/hello.kitty.2k9'],
              ['fab fa-instagram', '#E4405F', 'https://www.instagram.com/vthnhungoc'],
              ['fab fa-tiktok', '#000', 'https://www.tiktok.com/@babyanhngu'],
              ['fab fa-youtube', '#FF0000', '#'] 
          ];
          foreach ($socials as [$ic, $cl, $link]): 
          ?>
          <a href="<?= $link ?>" target="_blank" style="width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.7);text-decoration:none;transition:background .2s;font-size:.85rem" onmouseover="this.style.background='<?= $cl ?>33'" onmouseout="this.style.background='rgba(255,255,255,.08)'"><i class="<?= $ic ?>"></i></a>
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
 <?php 
// KIỂM TRA QUYỀN ADMIN: NẾU LÀ ADMIN THÌ SẼ KHÔNG HIỂN THỊ CHATBOX NÀY
$is_admin_check = ($_SESSION['vai_tro'] ?? $_SESSION['user']['role'] ?? $_SESSION['user']['vai_tro'] ?? '') === 'Quản trị viên';
if (!$is_admin_check): 
?>

<style>
.chat-widget { position: fixed; bottom: 25px; right: 25px; z-index: 9999; font-family: 'EB Garamond', serif; }
.chat-btn { width: 55px; height: 55px; border-radius: 50%; background: linear-gradient(135deg, #8B0000, #5C0000); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; cursor: pointer; box-shadow: 0 5px 20px rgba(139,0,0,0.4); border: 2px solid #C9A84C; transition: transform 0.3s; }
.chat-btn:hover { transform: scale(1.1); }
.chat-window { position: absolute; bottom: 70px; right: 0; width: 320px; background: #fff; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); border: 1px solid #E8E1D5; display: none; flex-direction: column; overflow: hidden; transform-origin: bottom right; animation: popChat 0.3s ease; }
@keyframes popChat { from { opacity: 0; transform: scale(0.5); } to { opacity: 1; transform: scale(1); } }
.chat-header { background: linear-gradient(135deg, #8B0000, #5C0000); color: #FFD700; padding: 12px 15px; display: flex; align-items: center; justify-content: space-between; font-family: 'Cormorant Garamond', serif; }
.chat-header h5 { margin: 0; font-weight: 700; font-size: 1.2rem; }
.chat-body { height: 350px; padding: 15px; background: #FAF6EE; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; }
.msg { max-width: 80%; padding: 8px 12px; border-radius: 12px; font-size: 0.9rem; line-height: 1.4; position: relative; }
.msg.khach { align-self: flex-end; background: #C9A84C; color: #1A0A0A; border-bottom-right-radius: 2px; }
.msg.ai { align-self: flex-start; background: #fff; color: #1A0A0A; border: 1px solid #E8E1D5; border-bottom-left-radius: 2px; }
.msg.admin { align-self: flex-start; background: #8B0000; color: #fff; border-bottom-left-radius: 2px; }
.msg-time { font-size: 0.65rem; opacity: 0.7; margin-top: 3px; display: block; text-align: right; }
.chat-footer { padding: 10px; background: #fff; border-top: 1px solid #E8E1D5; display: flex; gap: 8px; }
.chat-input { flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 20px; font-family: 'EB Garamond', serif; outline: none; }
.chat-input:focus { border-color: #8B0000; }
.chat-send { background: #8B0000; color: #fff; border: none; width: 35px; height: 35px; border-radius: 50%; cursor: pointer; }
.typing-indicator { display: none; align-self: flex-start; background: transparent; padding: 5px; color: #8B0000; font-size: 0.8rem; font-style: italic; }
</style>

<div class="chat-widget">
    <div class="chat-window" id="chatWindow">
        <div class="chat-header">
            <h5><i class="fas fa-robot me-2"></i>Vân Y Các AI</h5>
            <i class="fas fa-times" style="cursor:pointer" onclick="toggleChat()"></i>
        </div>
        <div class="chat-body" id="chatBody">
            </div>
        <div class="typing-indicator" id="aiTyping"><i class="fas fa-comment-dots fa-flip"></i> AI đang gõ...</div>
        <form class="chat-footer" id="chatForm">
            <input type="text" id="chatInput" class="chat-input" placeholder="Nhập tin nhắn..." autocomplete="off">
            <button type="submit" class="chat-send"><i class="fas fa-paper-plane"></i></button>
        </form>
    </div>
    <div class="chat-btn" onclick="toggleChat()"><i class="fas fa-comments"></i></div>
</div>

<script>
let chatTimer = null;

function toggleChat() {
    const isLogged = document.body.getAttribute('data-logged') === '1' || <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
    if (!isLogged) {
        const loginModal = document.getElementById('loginModal');
        if(loginModal) {
            const modal = new bootstrap.Modal(loginModal);
            modal.show();
        } else {
            alert('Vui lòng đăng nhập để sử dụng chat!');
        }
        return;
    }
    
    const win = document.getElementById('chatWindow');
    if (win.style.display === 'flex') {
        win.style.display = 'none';
        clearInterval(chatTimer);
    } else {
        win.style.display = 'flex';
        loadMessages();
        chatTimer = setInterval(loadMessages, 3000); 
    }
}

async function loadMessages() {
    try {
        const res = await fetch('chat_api.php?action=fetch');
        const data = await res.json();
        if (data.success) {
            const body = document.getElementById('chatBody');
            let html = '';
            if (data.messages.length === 0) {
                html = '<div class="msg ai">Kính chào quý khách! Em là trợ lý AI của Vân Y Các. Anh/chị cần hỗ trợ gì ạ?</div>';
            } else {
                data.messages.forEach(m => {
                    html += `<div class="msg ${m.nguoi_gui}">${m.noi_dung}<span class="msg-time">${m.gio}</span></div>`;
                });
            }
            const isScrolledToBottom = body.scrollHeight - body.clientHeight <= body.scrollTop + 50;
            body.innerHTML = html;
            if (isScrolledToBottom) body.scrollTop = body.scrollHeight;
        }
    } catch(e) {}
}

const chatForm = document.getElementById('chatForm');
if(chatForm) {
    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const inp = document.getElementById('chatInput');
        const msg = inp.value.trim();
        if (!msg) return;
        
        const body = document.getElementById('chatBody');
        body.innerHTML += `<div class="msg khach">${msg}<span class="msg-time">Vừa xong</span></div>`;
        body.scrollTop = body.scrollHeight;
        inp.value = '';
        
        document.getElementById('aiTyping').style.display = 'block';

        try {
            await fetch('chat_api.php?action=send', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ message: msg })
            });
            document.getElementById('aiTyping').style.display = 'none';
            loadMessages(); 
        } catch(e) { document.getElementById('aiTyping').style.display = 'none'; }
    });
}
</script>

<?php endif; // KẾT THÚC LỆNH ẨN CHATBOX DÀNH CHO ADMIN ?>
<script src="public/js/index.js"></script>