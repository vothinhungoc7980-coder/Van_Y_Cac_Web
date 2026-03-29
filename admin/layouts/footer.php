</div><!-- /.cwrap -->
</div><!-- /.admin-main -->
<script>
'use strict';
// ── Sidebar toggle (3 gạch)
document.getElementById('sidebarToggle').addEventListener('click', function() {
    const sb   = document.getElementById('sidebar');
    const main = document.getElementById('adminMain');
    if (!sb) return;
    if (window.innerWidth > 768) {
        sb.classList.toggle('collapsed');
        main.classList.toggle('expanded');
    } else {
        sb.classList.toggle('open');
    }
});
// Đóng sidebar mobile khi click ra ngoài
document.addEventListener('click', function(e) {
    const sb = document.getElementById('sidebar');
    if (!sb) return;
    if (window.innerWidth <= 768 && sb.classList.contains('open') &&
        !sb.contains(e.target) && !e.target.closest('#sidebarToggle'))
        sb.classList.remove('open');
});

// ── Bell dropdown (chuông thông báo)
(function() {
    const btn  = document.getElementById('bellBtn');
    const drop = document.getElementById('bellDrop');
    if (!btn || !drop) return;
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        drop.classList.toggle('open');
    });
    document.addEventListener('click', function(e) {
        if (!drop.contains(e.target) && e.target !== btn)
            drop.classList.remove('open');
    });
})();

// ── Auto-close alerts
document.querySelectorAll('[data-dismiss]').forEach(function(el) {
    const t = parseInt(el.dataset.dismiss) || 4000;
    setTimeout(function() {
        el.style.transition = 'opacity .4s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    }, t);
});

// ── Confirm links/buttons
document.querySelectorAll('[data-confirm]').forEach(function(el) {
    el.addEventListener('click', function(e) {
        if (!confirm(this.dataset.confirm || 'Xác nhận?')) e.preventDefault();
    });
});

// ── Modal helpers
function modalOpen(id)  { const m = document.getElementById(id); if (m) m.classList.add('open'); }
function modalClose(id) { const m = document.getElementById(id); if (m) m.classList.remove('open'); }
document.querySelectorAll('.modal-bd').forEach(function(m) {
    m.addEventListener('click', function(e) { if (e.target === m) m.classList.remove('open'); });
});

// ── Slug generator
function toSlug(s) {
    return s.toLowerCase()
        .replace(/[àáạảãâầấậẩẫăằắặẳẵ]/g,'a').replace(/[èéẹẻẽêềếệểễ]/g,'e')
        .replace(/[ìíịỉĩ]/g,'i').replace(/[òóọỏõôồốộổỗơờớợởỡ]/g,'o')
        .replace(/[ùúụủũưừứựửữ]/g,'u').replace(/[ỳýỵỷỹ]/g,'y').replace(/đ/g,'d')
        .replace(/[^a-z0-9\s-]/g,'').trim().replace(/\s+/g,'-').replace(/-+/g,'-');
}

// ── Image preview
function imgPreview(inp, previewId) {
    if (!inp.files || !inp.files[0]) return;
    const r = new FileReader();
    r.onload = function(e) {
        const p = document.getElementById(previewId);
        if (p) { p.src = e.target.result; p.style.display = 'block'; }
    };
    r.readAsDataURL(inp.files[0]);
}
</script>
</body></html>