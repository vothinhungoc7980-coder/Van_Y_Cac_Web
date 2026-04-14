<?php
/**
 * admin/panel.php — TRANG QUẢN TRỊ DUY NHẤT
 * Truy cập: admin/panel.php?page=dashboard|don-hang|san-pham|danh-muc|khach-hang|danh-gia|doanh-thu
 * Chi tiết: admin/panel.php?page=don-hang&id=X
 */
require_once __DIR__ . '/config/auth.php';
requireAdmin();
require_once __DIR__ . '/config/db.php';

$page   = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);
$ok = $err = '';

// ── MAP TITLE + MENU
$titles = [
    'dashboard'     => 'Dashboard',
    'don-hang'      => 'Đơn Hàng',
    'san-pham'      => 'Sản Phẩm',
    'form-san-pham' => 'Sản Phẩm', // Gộp chung menu
    'danh-muc'      => 'Danh Mục',
    'khach-hang'    => 'Khách Hàng',
    'danh-gia'      => 'Đánh Giá',
    'doanh-thu'     => 'Doanh Thu',
];
$page_title  = $titles[$page] ?? 'Admin';
$active_menu = str_replace('-','_',$page);
if ($page === 'form-san-pham') $active_menu = 'san_pham';
$depth       = 1;

// ── XỬ LÝ ACTION TRƯỚC KHI HEADER
// ======== ĐƠN HÀNG ========
if ($page === 'don-hang' && $_SERVER['REQUEST_METHOD']==='POST') {
    $did = (int)$_POST['did'];
    $tdh = $conn->real_escape_string($_POST['trang_thai_dh']??'');
    $ttt = $conn->real_escape_string($_POST['trang_thai_tt']??'');
    $ly_do = $conn->real_escape_string(trim($_POST['ly_do_huy']??''));

    $dh_cur = $conn->query("SELECT trang_thai_dh, ghi_chu FROM don_hang WHERE id=$did LIMIT 1")->fetch_assoc();
    
    // Chỉ cho phép update nếu chưa Hoàn thành và chưa Hủy
    if ($dh_cur && !in_array($dh_cur['trang_thai_dh'], ['Hoàn thành', 'Đã hủy'])) {
        $cur = $dh_cur['trang_thai_dh'];
        $valid = false;
        
        // Cấu hình quy trình 1 chiều cứng
        if ($cur === 'Chờ xác nhận' && in_array($tdh, ['Chờ xác nhận', 'Đã xác nhận', 'Đã hủy'])) $valid = true;
        if ($cur === 'Đã xác nhận'  && in_array($tdh, ['Đã xác nhận', 'Đang giao'])) $valid = true;
        if ($cur === 'Đang giao'    && in_array($tdh, ['Đang giao', 'Hoàn thành', 'Đã hủy'])) $valid = true;
        
        if ($valid) {
            $q_note = "";
            if ($tdh === 'Đã hủy') {
                // Hoàn tồn kho
                $its = $conn->query("SELECT id_san_pham,so_luong FROM chi_tiet_don_hang WHERE id_don_hang=$did");
                while($it=$its->fetch_assoc()) {
                    $conn->query("UPDATE san_pham SET so_luong_ton=so_luong_ton+{$it['so_luong']} WHERE id={$it['id_san_pham']}");
                }
                // Ghi chú lý do
                $lydo_text = $ly_do ?: 'Admin hủy đơn';
                $new_note = $dh_cur['ghi_chu'] ? $dh_cur['ghi_chu'] . "\n[Admin hủy] " . $lydo_text : "[Admin hủy] " . $lydo_text;
                $q_note = ", ghi_chu='$new_note'";
            }
            $conn->query("UPDATE don_hang SET trang_thai_dh='$tdh', trang_thai_tt='$ttt', nguoi_xu_ly={$_SESSION['user_id']}, ngay_cap_nhat=NOW() $q_note WHERE id=$did");
        }
    }
    header("Location: panel.php?page=don-hang".($id?"&id=$id":"")."&ok=1"); exit;
}
// ======== SẢN PHẨM ========
if ($page === 'san-pham') {
    if (isset($_GET['toggle'])) {
        $tid = (int)$_GET['toggle'];
        $conn->query("UPDATE san_pham SET trang_thai=1-trang_thai WHERE id=$tid");
        header('Location: panel.php?page=san-pham&msg=ok'); exit;
    }
    if (isset($_GET['del'])) {
        $did = (int)$_GET['del'];
        $conn->query("UPDATE san_pham SET trang_thai=0 WHERE id=$did");
        header('Location: panel.php?page=san-pham&msg=del'); exit;
    }
    if (isset($_GET['msg'])) {
        if ($_GET['msg']==='del') $ok = 'Đã ẩn sản phẩm.';
        if ($_GET['msg']==='ok') $ok = 'Đã cập nhật thành công.';
    }
}

// ======== FORM SẢN PHẨM ========
if ($page === 'form-san-pham' && $_SERVER['REQUEST_METHOD']==='POST') {
    $ten  = trim($_POST['ten_vi']??'');
    $gia  = (int)($_POST['gia_ban']??0);
    $goc  = $_POST['gia_goc']!=='' ? (int)$_POST['gia_goc'] : null;
    $motn = trim($_POST['mo_ta_ngan']??'');
    $mot  = trim($_POST['mo_ta']??'');
    $dm   = (int)($_POST['id_danh_muc']??0);
    $slt  = (int)($_POST['so_luong_ton']??0);
    $tt   = isset($_POST['trang_thai']) ? 1 : 0;
    $nb   = isset($_POST['noi_bat'])    ? 1 : 0;
    $gt   = in_array($_POST['gioi_tinh']??'',['Nam','Nữ','Unisex']) ? $_POST['gioi_tinh'] : 'Nữ';
    $slug = trim($_POST['slug']??'');
    
    $img = '';
    if($id) {
        $old = $conn->query("SELECT duong_dan FROM san_pham WHERE id=$id")->fetch_assoc();
        $img = $old['duong_dan'] ?? '';
    }

    if(!$ten)       $err='Tên sản phẩm không được để trống.';
    elseif($gia<=0) $err='Giá bán phải > 0.';
    elseif(!$dm)    $err='Vui lòng chọn danh mục.';
    else{
        if(!empty($_FILES['hinh_anh']['name'])){
            $ext=strtolower(pathinfo($_FILES['hinh_anh']['name'],PATHINFO_EXTENSION));
            if(in_array($ext,['jpg','jpeg','png','webp','gif'])){
                $new_name='sp_'.time().'_'.rand(100,999).'.'.$ext;
                $dest='../image/'.$new_name; // Fix đường dẫn lưu ảnh
                if(move_uploaded_file($_FILES['hinh_anh']['tmp_name'],$dest)) $img=$new_name;
                else $err='Lỗi upload ảnh.';
            } else $err='Định dạng ảnh không hợp lệ.';
        }
        if(!$err){
            $ten_e  =$conn->real_escape_string($ten);
            $motn_e =$conn->real_escape_string($motn);
            $mot_e  =$conn->real_escape_string($mot);
            $slug_e =$conn->real_escape_string($slug?:strtolower(str_replace(' ','-',$ten)));
            $img_e  =$conn->real_escape_string($img);
            $goc_sql= $goc!==null ? $goc : 'NULL';
            $dm_sql = $dm ?: 'NULL';

            if($id){
                $conn->query("UPDATE san_pham SET ten_vi='$ten_e',gia_ban=$gia,gia_goc=$goc_sql,
                  mo_ta_ngan='$motn_e',mo_ta='$mot_e',id_danh_muc=$dm_sql,
                  so_luong_ton=$slt,trang_thai=$tt,noi_bat=$nb,gioi_tinh='$gt',
                  slug='$slug_e',duong_dan='$img_e',ngay_cap_nhat=NOW()
                  WHERE id=$id");
                header("Location: panel.php?page=form-san-pham&id=$id&msg=updated"); exit;
            } else{
                $conn->query("INSERT INTO san_pham(ten_vi,gia_ban,gia_goc,mo_ta_ngan,mo_ta,id_danh_muc,
                  so_luong_ton,trang_thai,noi_bat,gioi_tinh,slug,duong_dan)
                  VALUES('$ten_e',$gia,$goc_sql,'$motn_e','$mot_e',$dm_sql,
                  $slt,$tt,$nb,'$gt','$slug_e','$img_e')");
                $new_id=$conn->insert_id;
                header("Location: panel.php?page=form-san-pham&id=$new_id&msg=added"); exit;
            }
        }
    }
}
if(isset($_GET['msg'])&&$_GET['msg']==='added') $ok='Đã thêm sản phẩm thành công!';
if(isset($_GET['msg'])&&$_GET['msg']==='updated') $ok='Đã cập nhật sản phẩm thành công!';

// ======== DANH MỤC ========
if ($page === 'danh-muc') {
    if ($action==='del' && $id) {
        $sp_cnt=(int)$conn->query("SELECT COUNT(*) v FROM san_pham WHERE id_danh_muc=$id")->fetch_assoc()['v'];
        $sub_cnt=(int)$conn->query("SELECT COUNT(*) v FROM danh_muc WHERE id_cha=$id")->fetch_assoc()['v'];
        if ($sp_cnt) $err="Không thể xóa: có $sp_cnt sản phẩm đang dùng.";
        elseif ($sub_cnt) $err="Không thể xóa: còn $sub_cnt danh mục con.";
        else { $conn->query("DELETE FROM danh_muc WHERE id=$id"); $ok='Đã xóa danh mục.'; }
    }
    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_dm'])) {
        $ten   = $conn->real_escape_string(trim($_POST['ten_danh_muc']??''));
        $slug  = $conn->real_escape_string(trim($_POST['slug']??''));
        $idcha = (int)($_POST['id_cha']??0) ?: 'NULL';
        $tt    = (int)($_POST['trang_thai']??1);
        $thu   = (int)($_POST['thu_tu']??0);
        $eid   = (int)($_POST['eid']??0);
        if (!$ten||!$slug) { $err='Tên và slug không được để trống.'; }
        else {
            if ($eid) $conn->query("UPDATE danh_muc SET ten_danh_muc='$ten',slug='$slug',id_cha=$idcha,trang_thai=$tt,thu_tu=$thu WHERE id=$eid");
            else {
                $dup=(int)$conn->query("SELECT COUNT(*) v FROM danh_muc WHERE slug='$slug'")->fetch_assoc()['v'];
                if ($dup) $err='Slug đã tồn tại!';
                else { $conn->query("INSERT INTO danh_muc(ten_danh_muc,slug,id_cha,trang_thai,thu_tu) VALUES('$ten','$slug',$idcha,$tt,$thu)"); $ok='Đã thêm danh mục.'; }
            }
        }
    }
}

// ======== KHÁCH HÀNG ========
if ($page === 'khach-hang') {
    if ($action==='lock'   && $id) { $conn->query("UPDATE khachhang SET TrangThai='Vô hiệu hóa' WHERE idKhachHang=$id AND VaiTro='Khách hàng'"); header("Location: panel.php?page=khach-hang&ok=lock"); exit; }
    if ($action==='unlock' && $id) { $conn->query("UPDATE khachhang SET TrangThai='Kích hoạt' WHERE idKhachHang=$id AND VaiTro='Khách hàng'"); header("Location: panel.php?page=khach-hang&ok=unlock"); exit; }
    if ($action==='del'    && $id) {
        $has=(int)$conn->query("SELECT COUNT(*) c FROM don_hang WHERE id_khach_hang=$id")->fetch_assoc()['c'];
        if ($has) $err='Không thể xóa: tài khoản có '.$has.' đơn hàng.';
        else { $conn->query("DELETE FROM khachhang WHERE idKhachHang=$id AND VaiTro='Khách hàng'"); $ok='Đã xóa tài khoản.'; }
    }
    $ok_map = ['lock'=>'Đã khóa tài khoản.','unlock'=>'Đã mở khóa tài khoản.'];
    if (isset($_GET['ok'])) $ok = $ok_map[$_GET['ok']] ?? 'Thành công.';
}

// ======== ĐÁNH GIÁ ========
if ($page === 'danh-gia' && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_dg'])) {
    $dgid = (int)$_POST['dgid'];
    $ph   = $conn->real_escape_string(trim($_POST['phan_hoi']??''));
    // Bỏ cập nhật trang_thai, chỉ lưu phan_hoi_admin
    $conn->query("UPDATE danh_gia SET phan_hoi_admin='$ph' WHERE id=$dgid");
    $ok = 'Đã gửi phản hồi thành công.';
}

// ── INCLUDE HEADER
require_once __DIR__ . '/layouts/header.php';
?>

<?php if($ok):?><div class="alert al-success" data-dismiss><i class="fas fa-check-circle"></i> <?=htmlspecialchars($ok)?></div><?php endif?>
<?php if($err):?><div class="alert al-danger"><i class="fas fa-exclamation-circle"></i> <div><?=$err?></div></div><?php endif?>

<?php
// ═══════════════════════════════════════════
// RENDER TỪNG TRANG
// ═══════════════════════════════════════════

// ────────────────── DASHBOARD ──────────────────
if ($page === 'dashboard'):
$dt_thang=(int)$conn->query("SELECT COALESCE(SUM(thanh_tien),0) v FROM don_hang WHERE trang_thai_dh='Hoàn thành' AND MONTH(ngay_tao)=MONTH(NOW()) AND YEAR(ngay_tao)=YEAR(NOW())")->fetch_assoc()['v'];
$tong_don=(int)$conn->query("SELECT COUNT(*) v FROM don_hang")->fetch_assoc()['v'];
$don_cho =(int)$conn->query("SELECT COUNT(*) v FROM don_hang WHERE trang_thai_dh='Chờ xác nhận'")->fetch_assoc()['v'];
$tong_sp =(int)$conn->query("SELECT COUNT(*) v FROM san_pham WHERE trang_thai=1")->fetch_assoc()['v'];
$tong_kh =(int)$conn->query("SELECT COUNT(*) v FROM khachhang WHERE VaiTro='Khách hàng'")->fetch_assoc()['v'];
$kh_moi  =(int)$conn->query("SELECT COUNT(*) v FROM khachhang WHERE DATE(NgayTao)=CURDATE()")->fetch_assoc()['v'];
$dg_cho  =(int)$conn->query("SELECT COUNT(*) v FROM danh_gia WHERE trang_thai='Chờ duyệt'")->fetch_assoc()['v'];
$c_days=$c_rev=$c_ord=[];
for($i=6;$i>=0;$i--){
  $c_days[]=date('d/m',strtotime("-$i day"));
  $r=$conn->query("SELECT COALESCE(SUM(thanh_tien),0) v,COUNT(*) c FROM don_hang WHERE DATE(ngay_tao)=DATE_SUB(CURDATE(),INTERVAL $i DAY) AND trang_thai_dh='Hoàn thành'")->fetch_assoc();
  $c_rev[]=(int)$r['v'];$c_ord[]=(int)$r['c'];
}
$pie=[]; foreach(['Chờ xác nhận','Đã xác nhận','Đang giao','Hoàn thành','Đã hủy'] as $s)
  $pie[$s]=(int)$conn->query("SELECT COUNT(*) v FROM don_hang WHERE trang_thai_dh='$s'")->fetch_assoc()['v'];
$top_sp=$conn->query("SELECT id,ten_vi,da_ban,gia_ban,duong_dan FROM san_pham WHERE trang_thai=1 ORDER BY da_ban DESC LIMIT 5");
$recent=$conn->query("SELECT dh.*,kh.HoVaTen FROM don_hang dh LEFT JOIN khachhang kh ON dh.id_khach_hang=kh.idKhachHang ORDER BY dh.ngay_tao DESC LIMIT 8");
$bdg=['Chờ xác nhận'=>'b-warning','Đã xác nhận'=>'b-info','Đang giao'=>'b-purple','Hoàn thành'=>'b-success','Đã hủy'=>'b-danger'];
?>
<div class="stats-grid">
  <div class="scard c-gold"><div class="sc-ico i-gold"><i class="fas fa-coins"></i></div><div><div class="sc-val"><?=number_format($dt_thang,0,',','.')?> ₫</div><div class="sc-lbl">Doanh Thu Tháng</div></div></div>
  <div class="scard c-red"><div class="sc-ico i-red"><i class="fas fa-box"></i></div><div><div class="sc-val"><?=$tong_don?></div><div class="sc-lbl">Tổng Đơn Hàng</div><?php if($don_cho>0):?><div class="sc-sub dn"><i class="fas fa-clock"></i> <?=$don_cho?> chờ xác nhận</div><?php endif?></div></div>
  <div class="scard c-blue"><div class="sc-ico i-blue"><i class="fas fa-tshirt"></i></div><div><div class="sc-val"><?=$tong_sp?></div><div class="sc-lbl">Sản Phẩm Đang Bán</div></div></div>
  <div class="scard c-green"><div class="sc-ico i-green"><i class="fas fa-users"></i></div><div><div class="sc-val"><?=$tong_kh?></div><div class="sc-lbl">Khách Hàng</div><?php if($kh_moi>0):?><div class="sc-sub up"><i class="fas fa-user-plus"></i> +<?=$kh_moi?> hôm nay</div><?php endif?></div></div>
</div>
<?php if($don_cho>0||$dg_cho>0):?>
<div class="alert al-warning" data-dismiss="7000"><i class="fas fa-bell"></i><div>
  <?php if($don_cho>0):?><a href="panel.php?page=don-hang" style="color:inherit;font-weight:700"><?=$don_cho?> đơn chờ xác nhận</a><?php endif?>
  <?php if($don_cho&&$dg_cho):?> &nbsp;·&nbsp; <?php endif?>
  <?php if($dg_cho>0):?><a href="panel.php?page=danh-gia" style="color:inherit;font-weight:700"><?=$dg_cho?> đánh giá chờ duyệt</a><?php endif?>
</div></div>
<?php endif?>
<div class="g7-5">
  <div class="card"><div class="card-hd"><div><div class="card-title">Doanh Thu 7 Ngày</div><div class="card-sub">Chỉ tính đơn hoàn thành</div></div><a href="panel.php?page=doanh-thu" class="btn btn-secondary btn-sm">Chi tiết</a></div><div class="card-bd"><div class="chart-wrap"><canvas id="cLine"></canvas></div></div></div>
  <div class="card"><div class="card-hd"><div class="card-title">Phân Bổ Đơn Hàng</div></div><div class="card-bd"><div class="chart-wrap chart-sm"><canvas id="cPie"></canvas></div></div></div>
</div>
<div class="g5-7">
  <div class="card"><div class="card-hd"><div class="card-title">Top 5 Bán Chạy</div><a href="panel.php?page=san-pham" class="btn btn-secondary btn-sm">Xem tất cả</a></div>
    <div class="card-bd-flush"><table class="dtable"><thead><tr><th>#</th><th>Sản Phẩm</th><th>Bán</th><th>Giá</th></tr></thead><tbody>
    <?php $rk=1;while($r=$top_sp->fetch_assoc()):?>
    <tr><td><strong style="color:<?=$rk<=3?'var(--gold)':'var(--mu)'?>"><?=$rk++?></strong></td>
    <td style="display:flex;align-items:center;gap:9px;padding:11px 14px">
      <?php if($r['duong_dan']):?><img src="../image/<?=htmlspecialchars($r['duong_dan'])?>" class="tbl-thumb" onerror="this.style.display='none'"><?php else:?><div class="tbl-img-placeholder"><i class="fas fa-image"></i></div><?php endif?>
      <a href="panel.php?page=form-san-pham&id=<?=$r['id']?>" style="font-size:.82rem;font-weight:600;color:var(--text);text-decoration:none"><?=htmlspecialchars($r['ten_vi'])?></a></td>
    <td><strong><?=$r['da_ban']?></strong></td>
    <td style="color:var(--cr);font-weight:700;font-size:.82rem"><?=number_format($r['gia_ban'],0,',','.')?> ₫</td></tr>
    <?php endwhile?></tbody></table></div></div>
  <div class="card"><div class="card-hd"><div class="card-title">Đơn Hàng Mới Nhất</div><a href="panel.php?page=don-hang" class="btn btn-secondary btn-sm">Xem tất cả</a></div>
    <div class="card-bd-flush" style="overflow-x:auto"><table class="dtable"><thead><tr><th>Mã Đơn</th><th>Khách</th><th>Tiền</th><th>Trạng Thái</th><th>Ngày</th><th></th></tr></thead><tbody>
    <?php while($d=$recent->fetch_assoc()):?>
    <tr><td><strong style="color:var(--cr);font-size:.78rem"><?=htmlspecialchars($d['ma_don_hang'])?></strong></td>
    <td><div style="font-size:.83rem;font-weight:600"><?=htmlspecialchars($d['ho_ten']??'KVL')?></div><div class="text-xs text-muted"><?=htmlspecialchars($d['so_dien_thoai'])?></div></td>
    <td style="font-weight:700;font-size:.82rem"><?=number_format($d['thanh_tien'],0,',','.')?> ₫</td>
    <td><span class="badge <?=$bdg[$d['trang_thai_dh']]??'b-gray'?>"><?=$d['trang_thai_dh']?></span></td>
    <td class="text-xs text-muted"><?=date('d/m H:i',strtotime($d['ngay_tao']))?></td>
    <td><a href="panel.php?page=don-hang&id=<?=$d['id']?>" class="ibtn ib-view"><i class="fas fa-eye"></i></a></td></tr>
    <?php endwhile?></tbody></table></div></div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('cLine'),{type:'line',data:{labels:<?=json_encode($c_days)?>,datasets:[
  {label:'Doanh thu',data:<?=json_encode($c_rev)?>,borderColor:'#8B0000',backgroundColor:'rgba(139,0,0,.08)',tension:.4,fill:true,yAxisID:'y'},
  {label:'Số đơn',data:<?=json_encode($c_ord)?>,borderColor:'#C9A84C',tension:.4,fill:false,yAxisID:'y2',borderDash:[5,3]}
]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top',labels:{font:{size:11}}}},
scales:{y:{ticks:{callback:v=>v>=1e6?(v/1e6).toFixed(1)+'M':v,font:{size:10}},grid:{color:'#F0E8D8'}},
y2:{position:'right',grid:{display:false},ticks:{font:{size:10}}},x:{grid:{display:false},ticks:{font:{size:11}}}}}});
new Chart(document.getElementById('cPie'),{type:'doughnut',data:{labels:<?=json_encode(array_keys($pie))?>,datasets:[{data:<?=json_encode(array_values($pie))?>,backgroundColor:['#F59E0B','#3B82F6','#8B5CF6','#059669','#EF4444'],borderWidth:2,borderColor:'#fff'}]},options:{responsive:true,maintainAspectRatio:false,cutout:'65%',plugins:{legend:{position:'bottom',labels:{font:{size:10},padding:8}}}}});
</script>

<?php 
// ────────────────── ĐƠN HÀNG ──────────────────
elseif ($page === 'don-hang' && !$id):
$tab=$_GET['tab']??'all'; $s=trim($_GET['s']??''); $pg=max(1,(int)($_GET['pg']??1)); $lim=15; $off=($pg-1)*$lim;
$cond="WHERE 1";
if($tab==='cho')  $cond.=" AND trang_thai_dh='Chờ xác nhận'";
elseif($tab==='xacnhan') $cond.=" AND trang_thai_dh='Đã xác nhận'"; // Thêm dòng này
elseif($tab==='giao') $cond.=" AND trang_thai_dh='Đang giao'";
elseif($tab==='ht')   $cond.=" AND trang_thai_dh='Hoàn thành'";
elseif($tab==='huy')  $cond.=" AND trang_thai_dh='Đã hủy'";

if($s) $cond.=" AND (ma_don_hang LIKE '%".($conn->real_escape_string($s))."%' OR ho_ten LIKE '%".($conn->real_escape_string($s))."%' OR so_dien_thoai LIKE '%".($conn->real_escape_string($s))."%')";

$tabs_cnt=[];
// Thêm 'xacnhan' vào mảng đếm số lượng
foreach(['all'=>'','cho'=>"AND trang_thai_dh='Chờ xác nhận'", 'xacnhan'=>"AND trang_thai_dh='Đã xác nhận'", 'giao'=>"AND trang_thai_dh='Đang giao'",'ht'=>"AND trang_thai_dh='Hoàn thành'",'huy'=>"AND trang_thai_dh='Đã hủy'"] as $k=>$w)
  $tabs_cnt[trim($k)]=(int)$conn->query("SELECT COUNT(*) v FROM don_hang WHERE 1 $w")->fetch_assoc()['v'];

$total=(int)$conn->query("SELECT COUNT(*) v FROM don_hang $cond")->fetch_assoc()['v'];
$pages=max(1,(int)ceil($total/$lim));
$rows=$conn->query("SELECT * FROM don_hang $cond ORDER BY ngay_tao DESC LIMIT $lim OFFSET $off");
$bdg=['Chờ xác nhận'=>'b-warning','Đã xác nhận'=>'b-info','Đang giao'=>'b-purple','Hoàn thành'=>'b-success','Đã hủy'=>'b-danger'];
?>
<div class="tab-nav">
  <?php foreach(['all'=>['Tất Cả',$tabs_cnt['all']],'cho'=>['Chờ Xác Nhận',$tabs_cnt['cho']], 'xacnhan'=>['Đã Xác Nhận',$tabs_cnt['xacnhan']], 'giao'=>['Đang Giao',$tabs_cnt['giao']],'ht'=>['Hoàn Thành',$tabs_cnt['ht']],'huy'=>['Đã Hủy',$tabs_cnt['huy']]] as $k=>[$lbl,$cnt]):?>
  <button class="tab-btn <?=$tab===$k?'active':''?>" onclick="location.href='panel.php?page=don-hang&tab=<?=$k?>'">
    <?=$lbl?> <span class="badge <?=$k==='cho'&&$cnt>0?'b-danger':'b-gray'?>"><?=$cnt?></span>
  </button>
  <?php endforeach?>
</div>
<form method="GET" class="fbar">
  <input type="hidden" name="page" value="don-hang">
  <input type="hidden" name="tab" value="<?=$tab?>">
  <div class="finput-wrap fbar-search"><i class="fas fa-search finput-ico"></i>
    <input type="text" name="s" class="fctrl" placeholder="Mã đơn, tên KH, SĐT..." value="<?=htmlspecialchars($s)?>"></div>
  <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Tìm</button>
  <a href="panel.php?page=don-hang&tab=<?=$tab?>" class="btn btn-secondary btn-sm">Reset</a>
  <span class="text-sm text-muted" style="margin-left:auto">Tổng: <strong><?=$total?></strong> đơn</span>
</form>
<div class="card"><div class="card-bd-flush" style="overflow-x:auto"><table class="dtable">
  <thead><tr><th>Mã Đơn</th><th>Khách Hàng</th><th>Tổng Tiền</th><th>T.Toán</th><th>Trạng Thái</th><th>Ngày</th><th></th></tr></thead>
  <tbody>
  <?php while($d=$rows->fetch_assoc()):?>
  <tr>
    <td><strong style="color:var(--cr);font-size:.8rem"><?=htmlspecialchars($d['ma_don_hang'])?></strong></td>
    <td><div style="font-weight:700;font-size:.84rem"><?=htmlspecialchars($d['ho_ten'])?></div><div class="text-xs text-muted"><?=htmlspecialchars($d['so_dien_thoai'])?></div></td>
    <td style="font-weight:700;font-size:.84rem"><?=number_format($d['thanh_tien'],0,',','.')?> ₫</td>
   <td>
  <div style="font-size:.72rem; color:var(--mu); margin-bottom:4px; font-weight:600; white-space:nowrap;">
      <i class="fas fa-wallet me-1"></i> <?=htmlspecialchars($d['phuong_thuc_tt'] ?? '')?>
  </div>
  <span class="badge <?=$d['trang_thai_tt']==='Đã thanh toán'?'b-success':'b-warning'?>"><?=$d['trang_thai_tt']?></span>
</td>
  <td>
      <?php 
      $tt_dh = $d['trang_thai_dh'];
      if ($tt_dh === 'Đã hủy'): ?>
          <span class="badge b-danger" style="padding:6px 10px;">Đã Hủy</span>
      <?php elseif ($tt_dh === 'Hoàn thành'): ?>
          <span class="badge b-success" style="padding:6px 10px;"><i class="fas fa-check-double me-1"></i>Hoàn thành</span>
      <?php else: ?>
          <form method="POST" style="margin:0; display:inline-block;">
              <input type="hidden" name="did" value="<?=$d['id']?>">
              <input type="hidden" name="trang_thai_tt" value="<?=htmlspecialchars($d['trang_thai_tt'])?>">
              
              <?php if ($tt_dh === 'Chờ xác nhận'): ?>
                  <button type="submit" name="trang_thai_dh" value="Đã xác nhận" class="btn btn-primary btn-sm" style="font-size:0.75rem; padding:4px 10px; font-weight:600;"><i class="fas fa-check me-1"></i>Xác nhận</button>
                  
              <?php elseif ($tt_dh === 'Đã xác nhận'): ?>
                  <button type="submit" name="trang_thai_dh" value="Đang giao" class="btn btn-sm" style="background:#8B5CF6; color:#fff; font-size:0.75rem; padding:4px 10px; font-weight:600;"><i class="fas fa-truck me-1"></i>Giao hàng</button>
                  
              <?php elseif ($tt_dh === 'Đang giao'): ?>
                  <button type="submit" name="trang_thai_dh" value="Hoàn thành" class="btn btn-success btn-sm" style="font-size:0.75rem; padding:4px 10px; font-weight:600;" onclick="return confirm('Xác nhận khách đã nhận hàng thành công?');"><i class="fas fa-box-open me-1"></i>Đã giao</button>
              <?php endif; ?>
          </form>
      <?php endif; ?>
    </td>
    <td class="text-xs text-muted"><?=date('d/m/Y H:i',strtotime($d['ngay_tao']))?></td>
    <td><a href="panel.php?page=don-hang&id=<?=$d['id']?>" class="ibtn ib-view"><i class="fas fa-eye"></i></a></td>
  </tr>
  <?php endwhile?>
  </tbody>
</table></div></div>
<?php if($pages>1):?><div class="pagi">
  <?php if($pg>1):?><a href="panel.php?page=don-hang&tab=<?=$tab?>&s=<?=urlencode($s)?>&pg=<?=$pg-1?>" class="pagi-link"><i class="fas fa-chevron-left"></i></a><?php endif?>
  <?php for($i=max(1,$pg-2);$i<=min($pages,$pg+2);$i++):?><a href="panel.php?page=don-hang&tab=<?=$tab?>&s=<?=urlencode($s)?>&pg=<?=$i?>" class="pagi-link <?=$i==$pg?'active':''?>"><?=$i?></a><?php endfor?>
  <?php if($pg<$pages):?><a href="panel.php?page=don-hang&tab=<?=$tab?>&s=<?=urlencode($s)?>&pg=<?=$pg+1?>" class="pagi-link"><i class="fas fa-chevron-right"></i></a><?php endif?>
</div><?php endif?>

<?php // ────────────────── CHI TIẾT ĐƠN ──────────────────
elseif ($page === 'don-hang' && $id):
$dh=$conn->query("SELECT dh.*,kh.TaiKhoan FROM don_hang dh LEFT JOIN khachhang kh ON dh.id_khach_hang=kh.idKhachHang WHERE dh.id=$id LIMIT 1")->fetch_assoc();
if(!$dh){echo '<div class="alert al-danger">Không tìm thấy đơn hàng.</div>';} else {
$items=$conn->query("SELECT * FROM chi_tiet_don_hang WHERE id_don_hang=$id");
$is_huy=$dh['trang_thai_dh']==='Đã hủy';
$ly_do=''; if($is_huy&&$dh['ghi_chu']){preg_match('/\[Khách hủy\]\s*(.+)/',$dh['ghi_chu'],$m);$ly_do=$m[1]??'';}
$bdg=['Chờ xác nhận'=>'b-warning','Đã xác nhận'=>'b-info','Đang giao'=>'b-purple','Hoàn thành'=>'b-success','Đã hủy'=>'b-danger'];
$steps=['Chờ xác nhận','Đã xác nhận','Đang giao','Hoàn thành'];
$cur_s=array_search($dh['trang_thai_dh'],$steps);
?>
<div style="margin-bottom:14px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
  <a href="panel.php?page=don-hang" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Danh Sách</a>
  <span class="badge <?=$bdg[$dh['trang_thai_dh']]??'b-gray'?>" style="font-size:.82rem;padding:5px 14px"><?=$dh['trang_thai_dh']?></span>
  <?php if($is_huy&&$ly_do):?><span style="font-size:.78rem;color:#991B1B;background:#FEE2E2;padding:4px 10px;border-radius:20px"><i class="fas fa-info-circle me-1"></i>Lý do: <?=htmlspecialchars($ly_do)?></span><?php endif?>
</div>
<?php if(!$is_huy): ?>
<div class="card" style="margin-bottom:14px"><div class="card-bd" style="padding:14px 20px">
  <div style="display:flex;align-items:center">
  <?php $icons=['fas fa-clock','fas fa-check-circle','fas fa-truck','fas fa-box-open']; foreach($steps as $si=>$st):
    $done=$cur_s!==false&&$si<$cur_s; $current=$cur_s!==false&&$si===$cur_s; ?>
  <div style="display:flex;flex-direction:column;align-items:center;min-width:80px">
    <div style="width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.82rem;border:2px solid <?=$done?'#C9A84C':($current?'#8B0000':'#E8E1D5')?>;background:<?=$done?'#C9A84C':($current?'#8B0000':'#fff')?>;color:<?=($done||$current)?'#fff':'#ccc'?>">
      <i class="<?=$icons[$si]??'fas fa-circle'?>"></i></div>
    <div style="font-size:.62rem;font-weight:700;color:<?=$current?'#8B0000':($done?'#C9A84C':'#ccc')?>;margin-top:4px;text-align:center"><?=$st?></div>
  </div>
  <?php if($si<3): ?><div style="flex:1;height:2px;background:<?=$done?'#C9A84C':'#E8E1D5'?>;margin-bottom:18px"></div><?php endif; endforeach; ?>
  </div>
</div></div>
<?php elseif($is_huy): ?>
<div class="alert al-danger"><i class="fas fa-ban"></i><div><strong>Đơn hàng đã bị hủy</strong><?php if($ly_do):?> — <?=htmlspecialchars($ly_do)?><?php endif?></div></div>
<?php endif?>
<div class="g4-8" style="align-items:start">
  <div>
    <div class="card"><div class="card-hd"><div class="card-title">Thông Tin Đơn</div></div><div class="card-bd">
      <?php $info=[['Mã đơn','<strong style="color:var(--cr)">'.$dh['ma_don_hang'].'</strong>'],['Ngày đặt',date('d/m/Y H:i',strtotime($dh['ngay_tao']))],['Họ tên',htmlspecialchars($dh['ho_ten'])],['SĐT',htmlspecialchars($dh['so_dien_thoai'])],['Địa chỉ',htmlspecialchars($dh['dia_chi'])],['P.thức TT',htmlspecialchars($dh['phuong_thuc_tt'])],['Tài khoản',htmlspecialchars($dh['TaiKhoan']??'KVL')]];?>
      <table style="width:100%;font-size:.83rem;border-collapse:collapse">
        <?php foreach($info as[$k,$v]):?><tr><td style="padding:6px 0;color:var(--mu);width:90px;font-size:.74rem;text-transform:uppercase;letter-spacing:.5px"><?=$k?></td><td style="padding:6px 0;font-weight:500"><?=$v?></td></tr><?php endforeach?>
      </table>
    </div></div>
    <?php if(!$is_huy): ?>
    <div class="card"><div class="card-hd"><div class="card-title">Cập Nhật Đơn Hàng</div></div><div class="card-bd">
      <form method="POST">
        <input type="hidden" name="did" value="<?=$dh['id']?>">
        
        <div class="fg"><label class="fl">Trạng Thái Thanh Toán</label>
          <div style="display:flex; gap:10px; align-items:center;">
              <select name="trang_thai_tt" class="fctrl" style="flex:1;">
                  <?php foreach(['Chờ thanh toán','Đã thanh toán','Hoàn tiền'] as $st):?>
                  <option <?=$dh['trang_thai_tt']===$st?'selected':''?>><?=$st?></option>
                  <?php endforeach?>
              </select>
              <button type="submit" name="trang_thai_dh" value="<?=$dh['trang_thai_dh']?>" class="btn btn-secondary btn-sm" style="padding: 8px 15px;"><i class="fas fa-save"></i> Lưu TT</button>
          </div>
        </div>

        <hr style="border:0; border-top:1px dashed #E8E1D5; margin:15px 0;">

        <div class="fg" style="margin-bottom:0;"><label class="fl" style="color:#8B0000; font-weight:700;"><i class="fas fa-tasks me-1"></i> Quy Trình Xử Lý Giao Hàng</label>
            <div style="display:flex; gap:10px; flex-wrap: wrap; margin-top: 10px;">
                <?php $tt_cur = $dh['trang_thai_dh']; ?>
                
                <?php if ($tt_cur === 'Chờ xác nhận'): ?>
                    <button type="submit" name="trang_thai_dh" value="Đã xác nhận" class="btn btn-primary" style="font-weight:600;"><i class="fas fa-check-circle me-1"></i> Xác nhận đơn</button>
                    <button type="submit" name="trang_thai_dh" value="Đã hủy" class="btn btn-outline-danger" style="font-weight:600;" onclick="return confirm('Bạn muốn hủy đơn này? Kho sẽ tự động hoàn lại sản phẩm.');"><i class="fas fa-times-circle me-1"></i> Hủy đơn</button>
                
                <?php elseif ($tt_cur === 'Đã xác nhận'): ?>
                    <button type="submit" name="trang_thai_dh" value="Đang giao" class="btn" style="background:#8B5CF6; color:#fff; font-weight:600;"><i class="fas fa-truck me-1"></i> Bàn giao Vận chuyển</button>
                
                <?php elseif ($tt_cur === 'Đang giao'): ?>
                    <button type="submit" name="trang_thai_dh" value="Hoàn thành" class="btn btn-success" style="font-weight:600;" onclick="return confirm('Xác nhận khách đã nhận hàng thành công?');"><i class="fas fa-box-open me-1"></i> Giao Thành Công</button>
                    <button type="submit" name="trang_thai_dh" value="Đã hủy" class="btn btn-warning" style="font-weight:600; color:#854D0E;" onclick="return confirm('Khách bom hàng / Hoàn về kho?');"><i class="fas fa-undo me-1"></i> Giao Thất Bại (Hoàn hàng)</button>
                
                <?php elseif ($tt_cur === 'Hoàn thành'): ?>
                    <div style="background:#DEF7EC; color:#046C4E; padding:10px 15px; border-radius:8px; font-weight:600; width:100%; border:1px solid #31C48D;">
                        <i class="fas fa-check-double me-1"></i> Đơn hàng đã Giao Thành Công
                    </div>
                <?php endif; ?>
            </div>
        </div>
      </form>
    </div></div>
    <?php endif?>
  </div>
  <div class="card"><div class="card-hd"><div class="card-title">Sản Phẩm Trong Đơn</div></div>
    <div class="card-bd-flush"><table class="dtable"><thead><tr><th>Ảnh</th><th>Sản Phẩm</th><th>Giá</th><th>SL</th><th>Thành Tiền</th></tr></thead><tbody>
    <?php while($it=$items->fetch_assoc()):?>
    <tr><td><?php if($it['hinh_anh']):?><img src="../image/<?=htmlspecialchars($it['hinh_anh'])?>" class="tbl-thumb" onerror="this.src='https://placehold.co/46x46?text=SP'"><?php else:?><div class="tbl-img-placeholder"><i class="fas fa-box"></i></div><?php endif?></td>
 <td style="font-weight:600;font-size:.84rem">
        <?=htmlspecialchars($it['ten_san_pham'])?>
        <?php if (!empty($it['size'])): ?>
            <div style="margin-top: 4px;">
                <span class="badge" style="background:#333; color:#fff; font-size:0.7rem; padding: 3px 8px;">Size: <?= htmlspecialchars($it['size']) ?></span>
            </div>
        <?php endif; ?>
    </td>
    <td class="text-sm"><?=number_format($it['gia_ban'],0,',','.')?> ₫</td>
    <td><strong><?=$it['so_luong']?></strong></td>
    <td style="font-weight:700;color:var(--cr)"><?=number_format($it['thanh_tien'],0,',','.')?> ₫</td></tr>
    <?php endwhile?>
    </tbody></table></div>
    <div style="padding:13px 18px;border-top:1px solid var(--bd2);background:#FAF6EE;text-align:right">
      <div class="text-sm">Tạm tính: <strong><?=number_format($dh['tong_tien'],0,',','.')?> ₫</strong></div>
      <div class="text-sm">Vận chuyển: <strong><?=number_format($dh['phi_van_chuyen']??0,0,',','.')?> ₫</strong></div>
      <div style="font-family:var(--fd);font-size:1.15rem;font-weight:700;color:var(--cr);margin-top:4px">Tổng: <?=number_format($dh['thanh_tien'],0,',','.')?> ₫</div>
    </div>
  </div>
</div>
<?php } 
// ────────────────── SẢN PHẨM (DANH SÁCH) ──────────────────
elseif ($page === 'san-pham'):
    $s    = trim($_GET['s']??'');
    $dm   = (int)($_GET['dm']??0);
    $tt   = $_GET['tt']??'';
    $nb   = $_GET['nb']??'';
    $tk   = $_GET['tk']??''; // BỘ LỌC TỒN KHO MỚI
    $pg   = max(1,(int)($_GET['pg']??1));
    $lim  = 15; $off=($pg-1)*$lim;

    $w = "WHERE 1";
    if($s)  $w.=" AND sp.ten_vi LIKE '%".$conn->real_escape_string($s)."%'";
    if($dm) $w.=" AND sp.id_danh_muc=$dm";
    if($tt!=='') $w.=" AND sp.trang_thai=".($tt==='1'?1:0);
    if($nb==='1') $w.=" AND sp.noi_bat=1";
    if($tk==='hethang') $w.=" AND sp.so_luong_ton <= 0";
    if($tk==='saphet') $w.=" AND sp.so_luong_ton > 0 AND sp.so_luong_ton <= 5";

    // Đếm số lượng sản phẩm hết hàng để làm nút cảnh báo
    $cnt_hethang = (int)$conn->query("SELECT COUNT(*) v FROM san_pham WHERE so_luong_ton <= 0")->fetch_assoc()['v'];

    $total=(int)$conn->query("SELECT COUNT(*) v FROM san_pham sp $w")->fetch_assoc()['v'];
    $pages=max(1,(int)ceil($total/$lim));
    $rows =$conn->query("SELECT sp.*,dm.ten_danh_muc FROM san_pham sp LEFT JOIN danh_muc dm ON sp.id_danh_muc=dm.id $w ORDER BY sp.id DESC LIMIT $lim OFFSET $off");
    $dms  =$conn->query("SELECT id,ten_danh_muc FROM danh_muc WHERE id_cha IS NOT NULL AND trang_thai=1 ORDER BY ten_danh_muc");
?>
<div class="page-actions">
  <div class="text-sm text-muted">Hiển thị <strong><?=number_format($total)?></strong> sản phẩm</div>
  <div style="display:flex; gap:10px; flex-wrap:wrap">
    <?php if($cnt_hethang > 0): ?>
      <a href="panel.php?page=san-pham&tk=hethang" class="btn btn-danger"><i class="fas fa-exclamation-triangle"></i> Có <?= $cnt_hethang ?> SP hết hàng</a>
    <?php endif; ?>
    <a href="panel.php?page=form-san-pham" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm Sản Phẩm</a>
  </div>
</div>

<form method="GET" class="fbar">
  <input type="hidden" name="page" value="san-pham">
  <div class="finput-wrap fbar-search">
    <i class="fas fa-search finput-ico"></i>
    <input type="text" name="s" class="fctrl" placeholder="Tìm tên sản phẩm..." value="<?=htmlspecialchars($s)?>">
  </div>
  <select name="dm" class="fctrl fbar-select">
    <option value="">Tất cả danh mục</option>
    <?php while($d=$dms->fetch_assoc()):?>
    <option value="<?=$d['id']?>" <?=$dm==$d['id']?'selected':''?>><?=htmlspecialchars($d['ten_danh_muc'])?></option>
    <?php endwhile?>
  </select>
  <select name="tk" class="fctrl fbar-select">
    <option value="">Tồn kho: Tất cả</option>
    <option value="hethang" <?=$tk==='hethang'?'selected':''?>>Hết hàng (0)</option>
    <option value="saphet" <?=$tk==='saphet'?'selected':''?>>Sắp hết (≤ 5)</option>
  </select>
  <select name="tt" class="fctrl fbar-select">
    <option value="">Tất cả trạng thái</option>
    <option value="1" <?=$tt==='1'?'selected':''?>>Đang hiện</option>
    <option value="0" <?=$tt==='0'?'selected':''?>>Đang ẩn</option>
  </select>
  <select name="nb" class="fctrl fbar-select">
    <option value="">Tất cả</option>
    <option value="1" <?=$nb==='1'?'selected':''?>>Nổi bật</option>
  </select>
  <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Lọc</button>
  <a href="panel.php?page=san-pham" class="btn btn-secondary btn-sm">Reset</a>
</form>

<div class="card">
  <div class="card-bd-flush" style="overflow-x:auto">
    <table class="dtable">
      <thead><tr><th>ID</th><th>Ảnh</th><th>Tên Sản Phẩm</th><th>Danh Mục</th><th>Giá Bán</th><th>Tồn</th><th>Đã Bán</th><th>Lượt Xem</th><th>Hiển Thị</th><th>Thao Tác</th></tr></thead>
      <tbody>
      <?php while($r=$rows->fetch_assoc()):
        if($r['so_luong_ton']<=0) {$sc='b-danger';$si='stock-out';}
        elseif($r['so_luong_ton']<=5){$sc='b-warning';$si='stock-low';}
        else{$sc='b-success';$si='stock-ok';}
      ?>
      <tr>
        <td class="text-xs text-muted">#<?=$r['id']?></td>
        <td>
          <?php if($r['duong_dan']):?>
          <img src="../image/<?=htmlspecialchars($r['duong_dan'])?>" class="tbl-thumb" onerror="this.src='https://placehold.co/46x46?text=SP'">
          <?php else:?><div class="tbl-img-placeholder"><i class="fas fa-image"></i></div><?php endif?>
        </td>
        <td>
          <div style="font-weight:700;font-size:.84rem;max-width:220px"><?=htmlspecialchars($r['ten_vi'])?></div>
          <?php if($r['noi_bat']):?><span class="badge b-gold" style="margin-top:3px"><i class="fas fa-fire" style="margin-right:3px"></i>Nổi bật</span><?php endif?>
        </td>
        <td class="text-xs text-muted"><?=htmlspecialchars($r['ten_danh_muc']??'—')?></td>
        <td>
          <div style="font-weight:700;color:var(--cr);font-size:.84rem"><?=number_format($r['gia_ban'],0,',','.')?> ₫</div>
          <?php if($r['gia_goc']&&$r['gia_goc']>$r['gia_ban']):?>
          <div class="text-xs" style="text-decoration:line-through;color:var(--mu)"><?=number_format($r['gia_goc'],0,',','.')?> ₫</div>
          <?php endif?>
        </td>
        <td><span class="badge <?=$sc?> <?=$si?>"><?=$r['so_luong_ton']?></span></td>
        <td class="fw7"><?=number_format($r['da_ban'])?></td>
        <td class="text-sm text-muted"><?=number_format($r['luot_xem'])?></td>
        <td>
          <a href="panel.php?page=san-pham&toggle=<?=$r['id']?>&s=<?=urlencode($s)?>&dm=<?=$dm?>&tk=<?=$tk?>&tt=<?=$tt?>&nb=<?=$nb?>&pg=<?=$pg?>"
             class="badge <?=$r['trang_thai']?'b-success':'b-gray'?>" style="text-decoration:none">
            <?=$r['trang_thai']?'Hiện':'Ẩn'?>
          </a>
        </td>
        <td style="white-space:nowrap">
          <a href="panel.php?page=form-san-pham&id=<?=$r['id']?>" class="ibtn ib-edit" title="Sửa"><i class="fas fa-edit"></i></a>
          <a href="panel.php?page=san-pham&del=<?=$r['id']?>&s=<?=urlencode($s)?>&dm=<?=$dm?>&tk=<?=$tk?>&tt=<?=$tt?>&nb=<?=$nb?>&pg=<?=$pg?>"
             class="ibtn ib-del" title="Ẩn sản phẩm" data-confirm="Ẩn sản phẩm '<?=htmlspecialchars(addslashes($r['ten_vi']))?>'?">
             <i class="fas fa-eye-slash"></i></a>
          <a href="../../sanpham.php?id=<?=$r['id']?>" class="ibtn ib-view" title="Xem trang web" target="_blank"><i class="fas fa-external-link-alt"></i></a>
        </td>
      </tr>
      <?php endwhile?>
      </tbody>
    </table>
  </div>
</div>

<?php if($pages>1):?>
<div class="pagi">
  <?php if($pg>1):?><a href="panel.php?page=san-pham&s=<?=urlencode($s)?>&dm=<?=$dm?>&tk=<?=$tk?>&tt=<?=$tt?>&nb=<?=$nb?>&pg=<?=$pg-1?>" class="pagi-link"><i class="fas fa-chevron-left"></i></a><?php endif?>
  <?php for($i=max(1,$pg-2);$i<=min($pages,$pg+2);$i++):?>
  <a href="panel.php?page=san-pham&s=<?=urlencode($s)?>&dm=<?=$dm?>&tk=<?=$tk?>&tt=<?=$tt?>&nb=<?=$nb?>&pg=<?=$i?>" class="pagi-link <?=$i==$pg?'active':''?>"><?=$i?></a>
  <?php endfor?>
  <?php if($pg<$pages):?><a href="panel.php?page=san-pham&s=<?=urlencode($s)?>&dm=<?=$dm?>&tk=<?=$tk?>&tt=<?=$tt?>&nb=<?=$nb?>&pg=<?=$pg+1?>" class="pagi-link"><i class="fas fa-chevron-right"></i></a><?php endif?>
</div>
<?php endif?>

<?php
// ────────────────── SẢN PHẨM (FORM THÊM/SỬA) ──────────────────
elseif ($page === 'form-san-pham'):
    $sp = ['ten_vi'=>'','gia_ban'=>'','gia_goc'=>'','mo_ta_ngan'=>'','mo_ta'=>'',
           'id_danh_muc'=>0,'so_luong_ton'=>0,'trang_thai'=>1,'noi_bat'=>0,
           'slug'=>'','duong_dan'=>''];
    if($id){
      $row=$conn->query("SELECT * FROM san_pham WHERE id=$id LIMIT 1");
      if($row&&$r=$row->fetch_assoc()) $sp=$r;
    }
    $dms=$conn->query("SELECT id,ten_danh_muc FROM danh_muc WHERE id_cha IS NOT NULL AND trang_thai=1 ORDER BY ten_danh_muc");
?>
<div style="margin-bottom:16px">
  <a href="panel.php?page=san-pham" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Danh Sách Sản Phẩm</a>
  <?php if($id):?>
  <a href="../../sanpham.php?id=<?=$id?>" class="btn btn-secondary btn-sm" target="_blank"><i class="fas fa-external-link-alt"></i> Xem trên web</a>
  <?php endif?>
</div>

<form method="POST" action="panel.php?page=form-san-pham<?= $id ? "&id=$id" : "" ?>" enctype="multipart/form-data">
<div class="g4-8" style="align-items:start">
  <div>
    <div class="card">
      <div class="card-hd"><div class="card-title">Hình Ảnh Sản Phẩm</div></div>
      <div class="card-bd">
        <div id="imgWrap" style="text-align:center;margin-bottom:14px;min-height:120px;background:#FAF6EE;border:1px dashed var(--bd);border-radius:5px;display:flex;align-items:center;justify-content:center;overflow:hidden">
          <?php if($sp['duong_dan']):?>
          <img id="imgPreview" src="../image/<?=htmlspecialchars($sp['duong_dan'])?>" style="max-height:180px;max-width:100%" onerror="this.src='https://placehold.co/200x200?text=No+Image'">
          <?php else:?>
          <img id="imgPreview" src="" style="display:none;max-height:180px;max-width:100%">
          <?php endif?>
          <?php if(!$sp['duong_dan']):?><span id="imgPlaceholder" style="color:#ccc;font-size:.82rem"><i class="fas fa-image" style="font-size:2rem;display:block;margin-bottom:6px"></i>Chưa có ảnh</span><?php endif?>
        </div>
        <div class="fgroup" style="margin:0">
          <label class="flabel">Chọn Ảnh Mới</label>
          <input type="file" name="hinh_anh" class="fctrl" accept=".jpg,.jpeg,.png,.webp"
            onchange="imgPreview(this,'imgPreview');document.getElementById('imgPlaceholder')&&(document.getElementById('imgPlaceholder').style.display='none')">
          <div class="fhint">JPG, PNG, WEBP — tối đa 5MB. Tỉ lệ 3:4 cho đẹp nhất.</div>
          <?php if($sp['duong_dan']):?>
          <div class="text-xs text-muted" style="margin-top:4px">File hiện tại: <strong><?=htmlspecialchars($sp['duong_dan'])?></strong></div>
          <?php endif?>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-hd"><div class="card-title">Phân Loại & Kho</div></div>
      <div class="card-bd">
        <div class="fgroup">
          <label class="flabel">Danh Mục <span class="req">*</span></label>
          <select name="id_danh_muc" class="fctrl" required>
            <option value="">— Chọn danh mục —</option>
            <?php while($d=$dms->fetch_assoc()):?>
            <option value="<?=$d['id']?>" <?=$sp['id_danh_muc']==$d['id']?'selected':''?>><?=htmlspecialchars($d['ten_danh_muc'])?></option>
            <?php endwhile?>
          </select>
        </div>
        <div class="fgroup">
          <label class="flabel">Số Lượng Tồn Kho</label>
          <input type="number" name="so_luong_ton" class="fctrl" value="<?=$sp['so_luong_ton']?>" min="0">
        </div>
        <div class="fgroup">
          <label class="flabel">Giới Tính</label>
          <select name="gioi_tinh" class="fctrl">
            <option value="Nữ"     <?=($sp['gioi_tinh']??'Nữ')==='Nữ'    ?'selected':''?>>Nữ</option>
            <option value="Nam"    <?=($sp['gioi_tinh']??'')==='Nam'   ?'selected':''?>>Nam</option>
            <option value="Unisex" <?=($sp['gioi_tinh']??'')==='Unisex'?'selected':''?>>Unisex</option>
          </select>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-hd"><div class="card-title">Trạng Thái</div></div>
      <div class="card-bd" style="display:flex;flex-direction:column;gap:14px">
        <label class="toggle-wrap">
          <input type="checkbox" name="trang_thai" class="toggle" <?=$sp['trang_thai']?'checked':''?>>
          <span>Hiển thị trên website</span>
        </label>
        <label class="toggle-wrap">
          <input type="checkbox" name="noi_bat" class="toggle" <?=$sp['noi_bat']?'checked':''?>>
          <span>Đánh dấu Nổi Bật ⭐</span>
        </label>
        <hr class="divider">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?=$id?'Cập Nhật':'Thêm Mới'?></button>
        <a href="panel.php?page=san-pham" class="btn btn-secondary" style="text-align:center">Hủy Bỏ</a>
      </div>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-hd"><div class="card-title">Thông Tin Sản Phẩm</div></div>
      <div class="card-bd">
        <div class="fgroup">
          <label class="flabel">Tên Sản Phẩm <span class="req">*</span></label>
          <input type="text" name="ten_vi" class="fctrl" required
            value="<?=htmlspecialchars($sp['ten_vi'])?>"
            oninput="if(!editMode)document.getElementById('slugField').value=toSlug(this.value)">
        </div>
        <div class="frow">
          <div class="fgroup">
            <label class="flabel">Giá Bán (₫) <span class="req">*</span></label>
            <input type="number" name="gia_ban" class="fctrl" required value="<?=$sp['gia_ban']?>" min="1000" step="1000">
          </div>
          <div class="fgroup">
            <label class="flabel">Giá Gốc (₫)</label>
            <input type="number" name="gia_goc" class="fctrl" value="<?=$sp['gia_goc']??''?>" min="0" step="1000" placeholder="Để trống nếu không có sale">
          </div>
        </div>
        <div class="fgroup">
          <label class="flabel">Mô Tả Ngắn</label>
          <textarea name="mo_ta_ngan" class="fctrl" rows="2" maxlength="500"><?=htmlspecialchars($sp['mo_ta_ngan']??'')?></textarea>
          <div class="fhint">Hiển thị trong danh sách và trang chi tiết (tối đa 500 ký tự)</div>
        </div>
        <div class="fgroup">
          <label class="flabel">Mô Tả Chi Tiết</label>
          <textarea name="mo_ta" class="fctrl" rows="6"><?=htmlspecialchars($sp['mo_ta']??'')?></textarea>
        </div>
        <div class="fgroup">
          <label class="flabel">Slug URL</label>
          <div class="finput-wrap">
            <i class="fas fa-link finput-ico"></i>
            <input type="text" name="slug" id="slugField" class="fctrl"
              value="<?=htmlspecialchars($sp['slug']??'')?>"
              placeholder="tu-dong-tao-tu-ten">
          </div>
          <div class="fhint">URL thân thiện: dùng chữ thường, không dấu, cách nhau bằng dấu -</div>
        </div>
      </div>
    </div>
  </div>
</div>
</form>
<script>const editMode = <?=$id?'true':'false'?>;</script>

<?php
// ────────────────── DANH MỤC ──────────────────
elseif ($page === 'danh-muc'):
    $edit_dm=null; if(isset($_GET['edit'])) $edit_dm=$conn->query("SELECT * FROM danh_muc WHERE id=".(int)$_GET['edit'])->fetch_assoc();
    $all=$conn->query("SELECT dm.*,p.ten_danh_muc ten_cha,(SELECT COUNT(*) FROM san_pham sp WHERE sp.id_danh_muc=dm.id) so_sp FROM danh_muc dm LEFT JOIN danh_muc p ON dm.id_cha=p.id ORDER BY COALESCE(dm.id_cha,0),dm.thu_tu,dm.id");
    $parents=$conn->query("SELECT id,ten_danh_muc FROM danh_muc WHERE id_cha IS NULL ORDER BY thu_tu");
?>
<div class="g4-8" style="align-items:start">
  <div class="card"><div class="card-hd"><div class="card-title"><?=$edit_dm?'Sửa':'Thêm'?> Danh Mục</div></div><div class="card-bd">
    <form method="POST"><input type="hidden" name="save_dm" value="1"><input type="hidden" name="eid" value="<?=$edit_dm['id']??0?>">
      <div class="fg"><label class="fl">Tên *</label><input type="text" name="ten_danh_muc" class="fctrl" required value="<?=htmlspecialchars($edit_dm['ten_danh_muc']??'')?>" oninput="if(!<?=$edit_dm?'true':'false'?>)document.getElementById('sl').value=toSlug(this.value)"></div>
      <div class="fg"><label class="fl">Slug *</label><input type="text" name="slug" id="sl" class="fctrl" required value="<?=htmlspecialchars($edit_dm['slug']??'')?>"></div>
      <div class="fg"><label class="fl">Danh Mục Cha</label>
        <select name="id_cha" class="fctrl"><option value="">— Cấp 1 —</option>
          <?php $parents->data_seek(0);while($p=$parents->fetch_assoc()):?><option value="<?=$p['id']?>" <?=($edit_dm['id_cha']??0)==$p['id']?'selected':''?>><?=htmlspecialchars($p['ten_danh_muc'])?></option><?php endwhile?>
        </select></div>
      <div class="frow"><div class="fg"><label class="fl">Thứ Tự</label><input type="number" name="thu_tu" class="fctrl" value="<?=$edit_dm['thu_tu']??0?>" min="0"></div>
        <div class="fg"><label class="fl">Trạng Thái</label><select name="trang_thai" class="fctrl"><option value="1" <?=($edit_dm['trang_thai']??1)?'selected':''?>>Hiển thị</option><option value="0" <?=isset($edit_dm)&&!$edit_dm['trang_thai']?'selected':''?>>Ẩn</option></select></div></div>
      <div style="display:flex;gap:8px"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu</button><?php if($edit_dm):?><a href="panel.php?page=danh-muc" class="btn btn-secondary">Hủy</a><?php endif?></div>
    </form>
  </div></div>
  <div class="card"><div class="card-hd"><div class="card-title">Danh Sách (<?=$all->num_rows?> mục)</div></div>
    <div class="card-bd-flush" style="overflow-x:auto"><table class="dtable"><thead><tr><th>Tên</th><th>Cha</th><th>Slug</th><th>SP</th><th>TT</th><th></th></tr></thead><tbody>
    <?php while($d=$all->fetch_assoc()):?>
    <tr style="<?=$d['id_cha']?'background:#FDFAF5':''?>">
      <td><?php if($d['id_cha']):?><span style="color:var(--gold);margin-right:5px">└</span><?php endif?><strong style="font-size:.85rem"><?=htmlspecialchars($d['ten_danh_muc'])?></strong></td>
      <td class="text-xs text-muted"><?=htmlspecialchars($d['ten_cha']??'—')?></td>
      <td class="text-xs" style="font-family:monospace;color:var(--mu)"><?=htmlspecialchars($d['slug'])?></td>
      <td><span class="badge b-info"><?=$d['so_sp']?></span></td>
      <td><span class="badge <?=$d['trang_thai']?'b-success':'b-gray'?>"><?=$d['trang_thai']?'Hiện':'Ẩn'?></span></td>
      <td style="white-space:nowrap">
        <a href="panel.php?page=danh-muc&edit=<?=$d['id']?>" class="ibtn ib-edit"><i class="fas fa-edit"></i></a>
        <a href="panel.php?page=danh-muc&action=del&id=<?=$d['id']?>" class="ibtn ib-del" data-confirm="Xóa '<?=htmlspecialchars(addslashes($d['ten_danh_muc']))?>'?"><i class="fas fa-trash"></i></a>
      </td>
    </tr>
    <?php endwhile?></tbody></table></div>
  </div>
</div>

<?php
// ────────────────── KHÁCH HÀNG ──────────────────
elseif ($page === 'khach-hang'):
$s=trim($_GET['s']??''); $tt=$_GET['tt']??''; $pg=max(1,(int)($_GET['pg']??1)); $lim=20; $off=($pg-1)*$lim;
$w="WHERE VaiTro='Khách hàng'";
if($s) $w.=" AND (TaiKhoan LIKE '%".($conn->real_escape_string($s))."%' OR HoVaTen LIKE '%".($conn->real_escape_string($s))."%' OR Email LIKE '%".($conn->real_escape_string($s))."%')";
if($tt) $w.=" AND TrangThai='".$conn->real_escape_string($tt)."'";
$total=(int)$conn->query("SELECT COUNT(*) v FROM khachhang $w")->fetch_assoc()['v'];
$pages=max(1,(int)ceil($total/$lim));
$rows=$conn->query("SELECT kh.*,
    (SELECT COUNT(*) FROM don_hang WHERE id_khach_hang=kh.idKhachHang) so_don,
    (SELECT COUNT(*) FROM don_hang WHERE id_khach_hang=kh.idKhachHang AND trang_thai_dh='Đã hủy' AND (ghi_chu IS NULL OR ghi_chu NOT LIKE '%[Admin hủy]%')) so_huy 
    FROM khachhang kh $w ORDER BY kh.NgayTao DESC LIMIT $lim OFFSET $off");
$warn=$conn->query("SELECT kh.idKhachHang, kh.HoVaTen, kh.TaiKhoan, kh.TrangThai, COUNT(*) so_huy 
    FROM don_hang dh 
    JOIN khachhang kh ON dh.id_khach_hang=kh.idKhachHang 
    WHERE dh.trang_thai_dh='Đã hủy' AND (dh.ghi_chu IS NULL OR dh.ghi_chu NOT LIKE '%[Admin hủy]%') 
    GROUP BY kh.idKhachHang 
    HAVING so_huy>=3 ORDER BY so_huy DESC LIMIT 5");
if($warn&&$warn->num_rows>0):?>
<div class="alert al-warning" style="flex-direction:column;align-items:flex-start">
  <div style="font-weight:700;margin-bottom:8px"><i class="fas fa-exclamation-triangle me-1"></i>Tài Khoản Hủy Đơn Nhiều (≥3 lần)</div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <?php while($wk=$warn->fetch_assoc()):?>
    <span style="background:#fff;border:1px solid #FCD34D;border-radius:5px;padding:5px 10px;font-size:.8rem">
      <strong><?=htmlspecialchars($wk['HoVaTen']??$wk['TaiKhoan'])?></strong> <span style="color:#92400E"><?=$wk['so_huy']?> lần</span>
      <?php if($wk['TrangThai']==='Kích hoạt'):?>
      <a href="panel.php?page=khach-hang&action=lock&id=<?=$wk['idKhachHang']?>" class="btn btn-danger btn-sm" style="margin-left:6px;padding:2px 7px" onclick="return confirm('Khóa tài khoản này?')"><i class="fas fa-ban"></i></a>
      <?php endif?>
    </span>
    <?php endwhile?>
  </div>
</div>
<?php endif?>
<form method="GET" class="fbar"><input type="hidden" name="page" value="khach-hang">
  <div class="finput-wrap fbar-search"><i class="fas fa-search finput-ico"></i><input type="text" name="s" class="fctrl" placeholder="Tên, tài khoản, email..." value="<?=htmlspecialchars($s)?>"></div>
  <select name="tt" class="fctrl" style="max-width:160px"><option value="">Tất cả</option><option value="Kích hoạt" <?=$tt==='Kích hoạt'?'selected':''?>>Kích hoạt</option><option value="Vô hiệu hóa" <?=$tt==='Vô hiệu hóa'?'selected':''?>>Vô hiệu hóa</option></select>
  <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Lọc</button>
  <a href="panel.php?page=khach-hang" class="btn btn-secondary btn-sm">Reset</a>
  <span class="text-sm text-muted" style="margin-left:auto">Tổng: <strong><?=$total?></strong></span>
</form>
<div class="card"><div class="card-bd-flush" style="overflow-x:auto"><table class="dtable">
  <thead><tr><th>Khách Hàng</th><th>Liên Hệ</th><th>Đơn/Hủy</th><th>Đăng Nhập Cuối</th><th>TT</th><th>Thao Tác</th></tr></thead>
  <tbody><?php while($r=$rows->fetch_assoc()):$is_w=(int)$r['so_huy']>=3;?>
  <tr style="<?=$is_w?'background:#FFFBEB':''?>">
    <td><div style="display:flex;align-items:center;gap:9px"><div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--cr));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.75rem;flex-shrink:0"><?=mb_strtoupper(mb_substr($r['HoVaTen']??$r['TaiKhoan'],0,1))?></div>
      <div><div style="font-weight:700;font-size:.84rem"><?=htmlspecialchars($r['HoVaTen']??'—')?><?php if($is_w):?><i class="fas fa-exclamation-triangle" style="color:#F59E0B;font-size:.7rem;margin-left:4px"></i><?php endif?></div><div class="text-xs text-muted">@<?=htmlspecialchars($r['TaiKhoan'])?></div></div></div></td>
    <td><div class="text-sm"><?=htmlspecialchars($r['Email']??'—')?></div><div class="text-xs text-muted"><?=htmlspecialchars($r['SoDienThoai']??'')?></div></td>
    <td><strong><?=$r['so_don']?></strong><?php if($r['so_huy']>0):?> / <span style="color:<?=$is_w?'#DC2626':'var(--mu)'?>;font-weight:<?=$is_w?700:400?>"><?=$r['so_huy']?> hủy</span><?php endif?></td>
    <td class="text-xs text-muted"><?=$r['LanCuoiDangNhap']?date('d/m/Y',strtotime($r['LanCuoiDangNhap'])):'Chưa'?></td>
    <td><span class="badge <?=$r['TrangThai']==='Kích hoạt'?'b-success':'b-danger'?>"><?=$r['TrangThai']?></span></td>
    <td style="white-space:nowrap">
      <?php if($r['TrangThai']==='Kích hoạt'):?>
      <a href="panel.php?page=khach-hang&action=lock&id=<?=$r['idKhachHang']?>" class="ibtn ib-block" title="Khóa" data-confirm="Khóa '<?=htmlspecialchars(addslashes($r['TaiKhoan']))?>'?"><i class="fas fa-ban"></i></a>
      <?php else:?>
      <a href="panel.php?page=khach-hang&action=unlock&id=<?=$r['idKhachHang']?>" class="ibtn ib-ok" title="Mở khóa" data-confirm="Mở khóa '<?=htmlspecialchars(addslashes($r['TaiKhoan']))?>'?"><i class="fas fa-check"></i></a>
      <?php endif?>
      <a href="panel.php?page=khach-hang&action=del&id=<?=$r['idKhachHang']?>" class="ibtn ib-del" title="Xóa" data-confirm="XÓA '<?=htmlspecialchars(addslashes($r['TaiKhoan']))?>'? Không thể khôi phục!"><i class="fas fa-trash"></i></a>
    </td>
  </tr>
  <?php endwhile?></tbody>
</table></div></div>
<?php if($pages>1):?><div class="pagi"><?php for($i=max(1,$pg-2);$i<=min($pages,$pg+2);$i++):?><a href="panel.php?page=khach-hang&s=<?=urlencode($s)?>&tt=<?=urlencode($tt)?>&pg=<?=$i?>" class="pagi-link <?=$i==$pg?'active':''?>"><?=$i?></a><?php endfor?></div><?php endif?>

<?php
// ────────────────── QUẢN LÝ TIN NHẮN (CHAT) ──────────────────
elseif ($page === 'chat'):
?>
<style>
.chat-container { display: flex; height: 75vh; background: #fff; border-radius: 8px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); border: 1px solid #E8E1D5; overflow: hidden; margin-bottom: 20px; }
.chat-sidebar { width: 320px; border-right: 1px solid #E8E1D5; display: flex; flex-direction: column; background: #FAF6EE; }
.chat-sidebar-header { padding: 18px; border-bottom: 1px solid #E8E1D5; font-weight: bold; color: #8B0000; font-size: 1.2rem; }
.user-list { flex: 1; overflow-y: auto; }
.user-item { padding: 15px; border-bottom: 1px solid #E8E1D5; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 12px; }
.user-item:hover, .user-item.active { background: #fff; border-left: 4px solid #8B0000; }
.user-avatar { width: 45px; height: 45px; border-radius: 50%; background: #C9A84C; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; }
.user-info { flex: 1; overflow: hidden; }
.user-name { font-weight: bold; font-size: 0.95rem; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.chat-main { flex: 1; display: flex; flex-direction: column; background: #fff; }
.chat-main-header { padding: 15px 20px; border-bottom: 1px solid #E8E1D5; font-weight: bold; font-size: 1.1rem; color: #333; display: flex; align-items: center; gap: 12px; background: #FFF8EE; }
.chat-messages { flex: 1; overflow-y: auto; padding: 20px; background: #F9F9F9; display: flex; flex-direction: column; gap: 10px; }
.msg { max-width: 70%; padding: 10px 15px; border-radius: 12px; font-size: 0.95rem; line-height: 1.4; position: relative; }
.msg.khach { align-self: flex-start; background: #fff; border: 1px solid #E8E1D5; color: #333; border-bottom-left-radius: 2px; }
.msg.admin { align-self: flex-end; background: #8B0000; color: #fff; border-bottom-right-radius: 2px; }
.msg.ai { align-self: flex-start; background: #E8E1D5; color: #555; font-style: italic; border-bottom-left-radius: 2px; font-size: 0.85rem; }
.msg-time { display: block; font-size: 0.7rem; margin-top: 5px; opacity: 0.7; text-align: right; }

.chat-input-area { padding: 15px; border-top: 1px solid #E8E1D5; background: #fff; display: flex; gap: 10px; }
.chat-input { flex: 1; padding: 12px 18px; border: 1px solid #ddd; border-radius: 25px; outline: none; font-family: inherit; font-size: 0.95rem; }
.chat-input:focus { border-color: #8B0000; }
.btn-send { background: #8B0000; color: #fff; border: none; width: 45px; height: 45px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; transition: 0.2s;}
.btn-send:hover { transform: scale(1.05); }

#emptyChat { flex: 1; display: flex; align-items: center; justify-content: center; flex-direction: column; color: #aaa; }
</style>

<div class="chat-container">
    <div class="chat-sidebar">
        <div class="chat-sidebar-header"><i class="fas fa-comments me-2"></i>Khách hàng cần tư vấn</div>
        <div class="user-list" id="userList">
            <div style="padding: 20px; text-align: center; color: #888;"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>
        </div>
    </div>
    
    <div class="chat-main" id="chatMain" style="display:none;">
        <div class="chat-main-header">
            <div class="user-avatar" id="activeAvatar" style="width: 35px; height: 35px; font-size: 1rem;">K</div>
            <span id="activeName">Tên khách hàng</span>
        </div>
        <div class="chat-messages" id="chatMessages"></div>
        <form class="chat-input-area" id="adminChatForm">
            <input type="text" id="adminChatInput" class="chat-input" placeholder="Nhập tin nhắn hỗ trợ khách..." autocomplete="off">
            <button type="submit" class="btn-send"><i class="fas fa-paper-plane"></i></button>
        </form>
    </div>
    
    <div id="emptyChat">
        <i class="fas fa-comment-dots fa-4x mb-3" style="color: #E8E1D5;"></i>
        <h5 style="color: #888;">Chọn một khách hàng để bắt đầu chat</h5>
    </div>
</div>

<script>
let activeUid = 0;
let adminChatTimer = null;
let isScrolledBottom = true;

async function loadUsers() {
    try {
        const res = await fetch('ajax_chat.php?action=get_users');
        const data = await res.json();
        if (data.success) {
            const list = document.getElementById('userList');
            list.innerHTML = '';
            data.users.forEach(u => {
                const name = u.HoVaTen || u.TaiKhoan;
                const av = name.charAt(0).toUpperCase();
                const activeCls = (activeUid == u.idKhachHang) ? 'active' : '';
                list.innerHTML += `
                    <div class="user-item ${activeCls}" onclick="openChat(event, ${u.idKhachHang}, '${name}', '${av}')">
                        <div class="user-avatar">${av}</div>
                        <div class="user-info">
                            <div class="user-name">${name}</div>
                            <div style="font-size:0.75rem; color:#888;">Cập nhật: Vừa xong</div>
                        </div>
                    </div>
                `;
            });
        }
    } catch(e) {}
}

async function openChat(event, uid, name, av) {
    activeUid = uid;
    document.getElementById('emptyChat').style.display = 'none';
    document.getElementById('chatMain').style.display = 'flex';
    document.getElementById('activeName').textContent = name;
    document.getElementById('activeAvatar').textContent = av;
    
    // Highlight list
    document.querySelectorAll('.user-item').forEach(el => el.classList.remove('active'));
    event.currentTarget.classList.add('active');

    await fetchMessages();
    if(adminChatTimer) clearInterval(adminChatTimer);
    adminChatTimer = setInterval(fetchMessages, 2000); // Admin load 2 giây 1 lần cho lẹ
}

async function fetchMessages() {
    if(!activeUid) return;
    try {
        const res = await fetch('ajax_chat.php?action=get_messages&uid=' + activeUid);
        const data = await res.json();
        if(data.success) {
            const box = document.getElementById('chatMessages');
            // Kiểm tra xem admin có đang cuộn lên để xem tin nhắn cũ không
            isScrolledBottom = box.scrollHeight - box.clientHeight <= box.scrollTop + 50;
            
            let html = '';
            data.messages.forEach(m => {
                let senderPrefix = '';
                if(m.nguoi_gui === 'ai') senderPrefix = '<strong>🤖 Trợ lý AI:</strong><br>';
                html += `<div class="msg ${m.nguoi_gui}">${senderPrefix}${m.noi_dung}<span class="msg-time">${m.gio}</span></div>`;
            });
            box.innerHTML = html;
            
            if (isScrolledBottom) box.scrollTop = box.scrollHeight;
        }
    } catch(e) {}
}

document.getElementById('adminChatForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const inp = document.getElementById('adminChatInput');
    const msg = inp.value.trim();
    if(!msg || !activeUid) return;

    // In ngay ra màn hình để có cảm giác mượt
    const box = document.getElementById('chatMessages');
    box.innerHTML += `<div class="msg admin">${msg}<span class="msg-time">Vừa xong</span></div>`;
    box.scrollTop = box.scrollHeight;
    inp.value = '';

    try {
        await fetch('ajax_chat.php?action=send', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ uid: activeUid, message: msg })
        });
        fetchMessages();
    } catch(e) {}
});

loadUsers();
setInterval(loadUsers, 5000); // Cứ 5s check xem có khách hàng mới nào chat không
</script>
<?php

// ────────────────── ĐÁNH GIÁ ──────────────────
elseif ($page === 'danh-gia'):
$tab = $_GET['tab'] ?? 'all'; 
$pg = max(1, (int)($_GET['pg'] ?? 1)); 
$lim = 15; 
$off = ($pg - 1) * $lim;

// Bộ lọc: Tìm các đánh giá chưa trả lời hoặc đã trả lời
$w = "WHERE 1"; 
if ($tab === 'chuatl') $w .= " AND (phan_hoi_admin IS NULL OR phan_hoi_admin = '')"; 
elseif ($tab === 'datl') $w .= " AND phan_hoi_admin != ''";

$cnt_chuatl = (int)$conn->query("SELECT COUNT(*) v FROM danh_gia WHERE (phan_hoi_admin IS NULL OR phan_hoi_admin = '')")->fetch_assoc()['v'];
$total = (int)$conn->query("SELECT COUNT(*) v FROM danh_gia $w")->fetch_assoc()['v'];
$pages = max(1, (int)ceil($total / $lim));
$rows = $conn->query("SELECT dg.*, sp.ten_vi FROM danh_gia dg LEFT JOIN san_pham sp ON dg.id_san_pham=sp.id $w ORDER BY dg.ngay_tao DESC LIMIT $lim OFFSET $off");
?>
<div class="tab-nav">
  <button class="tab-btn <?= $tab === 'all' ? 'active' : '' ?>" onclick="location.href='panel.php?page=danh-gia&tab=all'">Tất Cả</button>
  <button class="tab-btn <?= $tab === 'chuatl' ? 'active' : '' ?>" onclick="location.href='panel.php?page=danh-gia&tab=chuatl'">
    Chưa Trả Lời <?php if ($cnt_chuatl > 0): ?> <span class="badge b-danger"><?= $cnt_chuatl ?></span><?php endif; ?>
  </button>
  <button class="tab-btn <?= $tab === 'datl' ? 'active' : '' ?>" onclick="location.href='panel.php?page=danh-gia&tab=datl'">Đã Trả Lời</button>
</div>

<div class="card"><div class="card-bd-flush" style="overflow-x:auto"><table class="dtable">
  <thead><tr><th>Người Đánh Giá</th><th>Sản Phẩm</th><th>Nội Dung</th><th>Sao</th><th>Phản Hồi</th><th>Ngày</th><th>Thao Tác</th></tr></thead>
  <tbody><?php while($d=$rows->fetch_assoc()):?>
  <tr>
    <td><strong style="font-size:.84rem"><?=htmlspecialchars($d['ho_ten'])?></strong></td>
    <td class="text-xs text-muted" style="max-width:130px"><?=htmlspecialchars($d['ten_vi']??'—')?></td>
    <td style="max-width:200px;font-size:.82rem"><?=htmlspecialchars(mb_substr($d['noi_dung']??'',0,70)).(mb_strlen($d['noi_dung']??'')>70?'...':'')?></td>
    <td><div style="display:flex;gap:1px"><?php for($i=1;$i<=5;$i++):?><i class="fas fa-star" style="color:<?=$i<=$d['so_sao']?'#FFD700':'#d0c4b0'?>;font-size:.72rem"></i><?php endfor?></div></td>
    <td>
      <?php if ($d['phan_hoi_admin']): ?>
        <span class="badge b-success"><i class="fas fa-check"></i> Đã trả lời</span>
      <?php else: ?>
        <span class="badge b-warning">Chưa trả lời</span>
      <?php endif; ?>
    </td>
    <td class="text-xs text-muted"><?=date('d/m/Y',strtotime($d['ngay_tao']))?></td>
    <td><button class="ibtn ib-edit" onclick="openDG(<?=htmlspecialchars(json_encode($d))?>)" title="Trả lời"><i class="fas fa-reply"></i></button></td>
  </tr>
  <?php endwhile?></tbody>
</table></div></div>

<?php if($pages>1):?><div class="pagi"><?php for($i=max(1,$pg-2);$i<=min($pages,$pg+2);$i++):?><a href="panel.php?page=danh-gia&tab=<?=$tab?>&pg=<?=$i?>" class="pagi-link <?=$i==$pg?'active':''?>"><?=$i?></a><?php endfor?></div><?php endif?>

<div class="modal-bd" id="dgModal">
  <div class="modal-box">
    <div class="modal-hd">
      <div class="modal-title"><i class="fas fa-reply"></i> Trả Lời Khách Hàng</div>
      <button class="modal-close" onclick="modalClose('dgModal')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <div id="dgContent" style="background:#FAF6EE;border:1px solid var(--bd);border-radius:4px;padding:11px;font-size:.85rem;line-height:1.6;margin-bottom:14px;font-style:italic"></div>
      
      <form method="POST" action="panel.php?page=danh-gia">
        <input type="hidden" name="update_dg" value="1">
        <input type="hidden" name="dgid" id="dgId">
        
        <div class="fg">
          <label class="fl">Nội dung phản hồi từ Shop</label>
          <textarea name="phan_hoi" id="dgPH" class="fctrl" rows="4" placeholder="Nhập phản hồi..."></textarea>
        </div>
        
        <div style="display:flex;gap:8px">
          <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Gửi Phản Hồi</button>
          <button type="button" class="btn btn-secondary" onclick="modalClose('dgModal')">Đóng</button>
        </div>
      </form>

    </div>
  </div>
</div>

<script>
function openDG(d) {
    document.getElementById('dgId').value = d.id;
    
    // Tạo chuỗi ngôi sao
    let stars = '';
    for(let i=1; i<=5; i++) { 
        stars += `<i class="fas fa-star" style="color:${i<=d.so_sao ? '#FFD700' : '#d0c4b0'}"></i>`; 
    }
    
    // Đổ dữ liệu vào Modal
    document.getElementById('dgContent').innerHTML = 
        `<div style="margin-bottom:8px"><strong>${d.ho_ten || 'Khách hàng'}</strong> ${stars}</div>` + 
        `<div>"${d.noi_dung || '(Khách chỉ chấm sao, không để lại bình luận)'}"</div>`;
    
    document.getElementById('dgPH').value = d.phan_hoi_admin || '';
    
    // Mở modal (Giữ nguyên hàm modalOpen của bạn)
    modalOpen('dgModal');
}
</script>
<?php
// ────────────────── DOANH THU ──────────────────
elseif ($page === 'doanh-thu'):
    $year =(int)($_GET['year']??date('Y'));
    $month=(int)($_GET['month']??0);

    // FIX: Thêm dh. trước ngay_tao và thanh_tien
    $w="dh.trang_thai_dh='Hoàn thành' AND YEAR(dh.ngay_tao)=$year";
    if($month) $w.=" AND MONTH(dh.ngay_tao)=$month";

    $dt    = (int)$conn->query("SELECT COALESCE(SUM(dh.thanh_tien),0) v FROM don_hang dh WHERE $w")->fetch_assoc()['v'];
    $cnt   = (int)$conn->query("SELECT COUNT(*) v FROM don_hang dh WHERE $w")->fetch_assoc()['v'];
    $avg   = $cnt > 0 ? (int)($dt / $cnt) : 0;
    $sp_bn = (int)$conn->query("SELECT COALESCE(SUM(ct.so_luong),0) v FROM chi_tiet_don_hang ct JOIN don_hang dh ON ct.id_don_hang=dh.id WHERE $w")->fetch_assoc()['v'];

    // Chart 12 tháng
    $c_lbl=$c_dt=$c_don=[];
    for($m=1;$m<=12;$m++){
      $c_lbl[]="T$m";
      // FIX: Thêm dh.
      $r=$conn->query("SELECT COALESCE(SUM(dh.thanh_tien),0) v,COUNT(*) c FROM don_hang dh WHERE dh.trang_thai_dh='Hoàn thành' AND YEAR(dh.ngay_tao)=$year AND MONTH(dh.ngay_tao)=$m")->fetch_assoc();
      $c_dt[]=(int)$r['v'];$c_don[]=(int)$r['c'];
    }

    // Top SP
    $top=$conn->query("SELECT sp.id,sp.ten_vi,sp.duong_dan,SUM(ct.so_luong) sl,SUM(ct.thanh_tien) dt
      FROM chi_tiet_don_hang ct JOIN san_pham sp ON ct.id_san_pham=sp.id
      JOIN don_hang dh ON ct.id_don_hang=dh.id
      WHERE $w GROUP BY sp.id ORDER BY dt DESC LIMIT 10");

    // Top KH
    $topkh=$conn->query("SELECT kh.HoVaTen,kh.TaiKhoan,COUNT(dh.id) cnt,SUM(dh.thanh_tien) total
      FROM don_hang dh JOIN khachhang kh ON dh.id_khach_hang=kh.idKhachHang
      WHERE $w GROUP BY kh.idKhachHang ORDER BY total DESC LIMIT 5");

    $years_r=$conn->query("SELECT DISTINCT YEAR(ngay_tao) y FROM don_hang ORDER BY y DESC");
    $years=[];while($yr=$years_r->fetch_assoc())$years[]=$yr['y'];
    if(!in_array($year,$years))$years[]=$year;
?>

<div style="display:flex;gap:12px;align-items:center;margin-bottom:20px;flex-wrap:wrap">
  <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <input type="hidden" name="page" value="doanh-thu">
    <select name="year" class="fctrl" style="max-width:110px">
      <?php foreach($years as $y):?><option <?=$y==$year?'selected':''?>><?=$y?></option><?php endforeach?>
    </select>
    <select name="month" class="fctrl" style="max-width:140px">
      <option value="0" <?=!$month?'selected':''?>>Cả năm</option>
      <?php for($m=1;$m<=12;$m++):?><option value="<?=$m?>" <?=$m==$month?'selected':''?>>Tháng <?=$m?></option><?php endfor?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Xem</button>
    <?php if($month):?><a href="panel.php?page=doanh-thu&year=<?=$year?>" class="btn btn-secondary btn-sm">Reset</a><?php endif?>
  </form>
  <span class="text-sm text-muted">
    Đang xem: <strong><?=$month?"Tháng $month/$year":"Cả năm $year"?></strong>
  </span>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
  <div class="scard c-gold"><div class="sc-ico i-gold"><i class="fas fa-coins"></i></div>
    <div><div class="sc-val" style="font-size:1.2rem"><?=number_format($dt,0,',','.')?> ₫</div><div class="sc-lbl">Tổng Doanh Thu</div></div></div>
  <div class="scard c-red"><div class="sc-ico i-red"><i class="fas fa-box-open"></i></div>
    <div><div class="sc-val"><?=number_format($cnt)?></div><div class="sc-lbl">Đơn Hoàn Thành</div></div></div>
  <div class="scard c-blue"><div class="sc-ico i-blue"><i class="fas fa-calculator"></i></div>
    <div><div class="sc-val" style="font-size:1.2rem"><?=number_format($avg,0,',','.')?> ₫</div><div class="sc-lbl">Giá Trị Trung Bình</div></div></div>
  <div class="scard c-green"><div class="sc-ico i-green"><i class="fas fa-tshirt"></i></div>
    <div><div class="sc-val"><?=number_format($sp_bn)?></div><div class="sc-lbl">Sản Phẩm Đã Bán</div></div></div>
</div>

<div class="g7-5">
  <div class="card">
    <div class="card-hd">
      <div class="card-title">Doanh Thu 12 Tháng — <?=$year?></div>
    </div>
    <div class="card-bd"><div class="chart-wrap"><canvas id="cBar"></canvas></div></div>
  </div>
  <div class="card">
    <div class="card-hd"><div class="card-title">Top 5 Khách Hàng Chi Nhiều Nhất</div></div>
    <div class="card-bd-flush">
      <table class="dtable">
        <thead><tr><th>#</th><th>Khách Hàng</th><th>Đơn</th><th>Tổng Chi</th></tr></thead>
        <tbody>
        <?php $rk=1;while($r=$topkh->fetch_assoc()):?>
        <tr>
          <td><strong style="color:<?=$rk<=3?'var(--gold)':'var(--muted)'?>"><?=$rk++?></strong></td>
          <td><div style="font-weight:600;font-size:.83rem"><?=htmlspecialchars($r['HoVaTen']??'—')?></div>
              <div class="text-xs text-muted">@<?=htmlspecialchars($r['TaiKhoan'])?></div></td>
          <td><?=$r['cnt']?></td>
          <td style="color:var(--cr);font-weight:700;font-size:.82rem"><?=number_format($r['total'],0,',','.')?> ₫</td>
        </tr>
        <?php endwhile?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-hd"><div class="card-title">Top 10 Sản Phẩm Doanh Thu Cao Nhất</div></div>
  <div class="card-bd-flush" style="overflow-x:auto">
    <table class="dtable">
      <thead><tr><th>#</th><th>Sản Phẩm</th><th>Số Lượng Bán</th><th>Doanh Thu</th><th>Tỉ Lệ</th></tr></thead>
      <tbody>
      <?php $rk=1;while($r=$top->fetch_assoc()):
        $pct=$dt>0?round($r['dt']/$dt*100,1):0;
      ?>
      <tr>
        <td><strong style="color:<?=$rk<=3?'var(--gold)':'var(--muted)'?>"><?=$rk++?></strong></td>
        <td>
          <div style="display:flex;align-items:center;gap:9px">
            <?php if($r['duong_dan']):?><img src="../image/<?=htmlspecialchars($r['duong_dan'])?>" class="tbl-thumb" onerror="this.style.display='none'"><?php endif?>
            <a href="panel.php?page=form-san-pham&id=<?=$r['id']?>" style="font-size:.83rem;font-weight:600;color:var(--text);text-decoration:none"><?=htmlspecialchars($r['ten_vi'])?></a>
          </div>
        </td>
        <td><strong><?=number_format($r['sl'])?></strong></td>
        <td style="font-weight:700;color:var(--cr)"><?=number_format($r['dt'],0,',','.')?> ₫</td>
        <td>
          <div style="display:flex;align-items:center;gap:8px">
            <div style="background:#F0E8D8;border-radius:3px;height:8px;width:80px;overflow:hidden">
              <div style="background:var(--cr);height:100%;width:<?=$pct?>%;border-radius:3px"></div>
            </div>
            <span class="text-xs"><?=$pct?>%</span>
          </div>
        </td>
      </tr>
      <?php endwhile?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('cBar'),{type:'bar',
  data:{labels:<?=json_encode($c_lbl)?>,datasets:[
    {label:'Doanh thu (₫)',data:<?=json_encode($c_dt)?>,backgroundColor:'rgba(139,0,0,.75)',borderRadius:4,yAxisID:'y'},
    {label:'Số đơn',data:<?=json_encode($c_don)?>,type:'line',borderColor:'#C9A84C',tension:.4,yAxisID:'y2',fill:false}
  ]},
  options:{responsive:true,maintainAspectRatio:false,
    plugins:{legend:{position:'top',labels:{font:{size:11}}}},
    scales:{y:{ticks:{callback:v=>v>=1e6?(v/1e6).toFixed(1)+'M':v,font:{size:10}},grid:{color:'#F0E8D8'}},
    y2:{position:'right',grid:{display:false},ticks:{font:{size:10}}},
    x:{grid:{display:false},ticks:{font:{size:11}}}}}
});
</script>

<?php endif; // end page switch ?>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>