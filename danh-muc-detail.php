<?php
session_start();
include 'config/db.php';

$slug = $_GET['slug'] ?? '';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($slug) {
    $slug_esc = $conn->real_escape_string($slug);
    $dm = $conn->query("SELECT * FROM danh_muc WHERE slug='$slug_esc' AND trang_thai=1 LIMIT 1")->fetch_assoc();
} elseif ($id) {
    $dm = $conn->query("SELECT * FROM danh_muc WHERE id=$id AND trang_thai=1 LIMIT 1")->fetch_assoc();
}

if (!$dm) { header('Location: bosuutap.php'); exit; }

$dm_cha = null;
if ($dm['id_cha']) {
    $dm_cha = $conn->query("SELECT * FROM danh_muc WHERE id={$dm['id_cha']} LIMIT 1")->fetch_assoc();
}

$sp_list = $conn->query("
    SELECT sp.id, sp.ten_vi, sp.gia_ban, sp.gia_goc, sp.duong_dan, sp.mo_ta_ngan, sp.noi_bat, sp.so_luong_ton
    FROM san_pham sp
    WHERE sp.id_danh_muc = {$dm['id']} AND sp.trang_thai = 1
    ORDER BY sp.noi_bat DESC, sp.id DESC
    LIMIT 8
");

$da_dang_nhap = isset($_SESSION['user_id']) || isset($_SESSION['user']);
$ten_dm = $dm['ten_danh_muc'];
$ten_dm_cha = $dm_cha ? $dm_cha['ten_danh_muc'] : '';

// KHO TÀNG CÂU CHUYỆN AI
$stories = [
    'ao-nhat-binh' => [
        'title'    => 'Áo Nhật Bình — Vẻ Đẹp Cung Đình Nguyễn',
        'era'      => 'Triều Nguyễn (1802–1945)',
        'hero_img' => 'https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=1400',
        'intro'    => 'Áo Nhật Bình là trang phục đặc trưng của phụ nữ hoàng tộc triều Nguyễn — một kiệt tác nghệ thuật may mặc mang đậm tinh thần Nho giáo và vẻ đẹp cung đình Huế.',
        'chapters' => [
            ['icon'=>'fas fa-scroll','title'=>'Nguồn Gốc','text'=>'Áo Nhật Bình xuất hiện vào đầu thế kỷ 19 dưới triều vua Gia Long. Tên "Nhật Bình" bắt nguồn từ cổ áo hình chữ nhật đặc trưng.'],
            ['icon'=>'fas fa-crown','title'=>'Địa Vị & Màu Sắc','text'=>'Màu vàng dành cho hoàng hậu, đỏ thẫm cho cung tần, xanh lam cho mệnh phụ. Mỗi họa tiết thêu đều mang ý nghĩa phúc lộc thọ.'],
            ['icon'=>'fas fa-paint-brush','title'=>'Nghệ Thuật Thêu','text'=>'Mỗi chiếc áo cần hàng trăm giờ thêu tay. Chỉ tơ nhuộm từ thực vật tạo nên màu sắc bền vững, lung linh dưới ánh đèn hoàng cung.'],
            ['icon'=>'fas fa-heart','title'=>'Phục Hưng Hiện Đại','text'=>'Ngày nay, nhiều bạn trẻ diện áo Nhật Bình trong các dịp lễ Tết, chụp ảnh kỷ yếu, mang linh hồn triều Nguyễn vào thế kỷ 21.']
        ],
    ],
    'ao-giao-linh' => [
        'title'    => 'Áo Giao Lĩnh — Cội Nguồn Trang Phục Việt',
        'era'      => 'Thế kỷ 10–18',
        'hero_img' => 'https://images.unsplash.com/photo-1558769132-cb1aea458c5e?w=1400',
        'intro'    => 'Áo Giao Lĩnh là trang phục cổ xưa nhất của người Việt. Đây là nền tảng từ đó sinh ra nhiều kiểu trang phục Việt truyền thống khác.',
        'chapters' => [
            ['icon'=>'fas fa-scroll','title'=>'Lịch Sử Ngàn Năm','text'=>'Kiểu cổ chéo vạt trái đè vạt phải là đặc trưng nhận diện độc đáo, xuất hiện sớm nhất trong trang phục người Việt từ thời nhà Đinh.'],
            ['icon'=>'fas fa-tree','title'=>'Chất Liệu Thiên Nhiên','text'=>'Dệt từ tơ lụa, vải thô hay gai. Màu nhuộm từ thực vật như củ nâu, lá chàm, vỏ cây mặc nưa tạo nên bảng màu tự nhiên.'],
            ['icon'=>'fas fa-users','title'=>'Xuyên Giai Cấp','text'=>'Từ Hoàng bào gấm vóc đến bộ áo nâu sồng của thường dân, Giao Lĩnh là thiết kế bình đẳng, nối liền mọi khoảng cách giai tầng.'],
            ['icon'=>'fas fa-star','title'=>'Biểu Tượng Tự Hào','text'=>'Mỗi chiếc áo giao lĩnh được phục dựng ngày nay là một nhịp cầu nối liền người Việt hiện đại với tổ tiên ngàn năm.']
        ],
    ],
    'ao-ngu-than' => [
        'title'    => 'Áo Ngũ Thân — Đạo Lý Làm Người',
        'era'      => 'Triều Nguyễn (1744 đến nay)',
        'hero_img' => 'https://images.unsplash.com/photo-1617391763788-b3e15053bbbd?w=1400',
        'intro'    => 'Kín đáo, đĩnh đạc và mang vóc dáng của người quân tử. Áo Ngũ Thân không đơn thuần là quần áo, nó là một bài học đạo lý khoác lên người.',
        'chapters' => [
            ['icon'=>'fas fa-user-tie','title'=>'Cải Cách Võ Vương','text'=>'Năm 1744, chúa Nguyễn Phúc Khoát xưng vương và ban hành lệnh cải cách y phục. Áo Ngũ Thân cổ đứng ra đời từ đó.'],
            ['icon'=>'fas fa-yin-yang','title'=>'Ý Nghĩa 5 Tà Áo','text'=>'Bốn thân ngoài tượng trưng cho Tứ thân phụ mẫu. Thân thứ 5 ẩn bên trong tượng trưng cho bản thân người mặc luôn được che chở.'],
            ['icon'=>'fas fa-hand-holding-heart','title'=>'Năm Hạt Nút Áo','text'=>'Năm chiếc cúc áo tượng trưng cho Ngũ Thường: Nhân, Lễ, Nghĩa, Trí, Tín. Cổ áo vuông vức thể hiện sự chính trực.'],
            ['icon'=>'fas fa-seedling','title'=>'Sự Khiêm Nhường','text'=>'Được may hai lớp, tà áo rộng vửa phải, che khuất những nét thô kệch. Đây là thiết kế đỉnh cao của sự khiêm nhường Á Đông.']
        ],
    ],
    'ao-vien-linh' => [
        'title'    => 'Áo Viên Lĩnh — Quyền Uy Cổ Tròn',
        'era'      => 'Thời Lý - Trần - Lê',
        'hero_img' => 'https://images.unsplash.com/photo-1599839619722-39751411ea63?w=1400',
        'intro'    => 'Với thiết kế cổ tròn ôm sát, Viên Lĩnh từng là bá chủ của dòng trang phục cung đình đại triều, biểu tượng của uy quyền và sự viên mãn.',
        'chapters' => [
            ['icon'=>'fas fa-circle','title'=>'Dấu Ấn Cổ Tròn','text'=>'Viên Lĩnh mang thiết kế cổ tròn khoét sát cổ. Khác với Giao Lĩnh vạt chéo, Viên Lĩnh mang lại sự uy nghiêm và gọn gàng.'],
            ['icon'=>'fas fa-chess-king','title'=>'Lễ Phục Đại Triều','text'=>'Trong suốt thời Lê Sơ và Lê Trung Hưng, Viên Lĩnh là trang phục bắt buộc của bá quan văn võ khi vào chầu vua.'],
            ['icon'=>'fas fa-dragon','title'=>'Bổ Tử Quyền Lực','text'=>'Điểm nhấn là tấm "Bổ Tử" hình vuông thêu trước ngực và sau lưng. Quan văn thêu chim muông, quan võ thêu mãnh thú.'],
            ['icon'=>'fas fa-magic','title'=>'Cảm Hứng Đương Đại','text'=>'Vượt qua khỏi chốn quan trường cổ xưa, Viên Lĩnh nay được cách tân, trở thành trang phục cưới mang đậm nét quyền quý.']
        ],
    ],
    'ao-yem' => [
        'title'    => 'Áo Yếm — Vẻ Đẹp E Ấp Đậm Chất Á Đông',
        'era'      => 'Thời kỳ Phong Kiến',
        'hero_img' => 'https://images.unsplash.com/photo-1516914943479-89db7d9ae7f2?w=1400',
        'intro'    => 'Mỏng manh, e ấp nhưng đầy sức quyến rũ. Chiếc Yếm đào là bản tình ca tôn vinh đường nét mềm mại và tấm lưng trần của người con gái Việt.',
        'chapters' => [
            ['icon'=>'fas fa-fan','title'=>'Mảnh Lụa Chữ Thập','text'=>'Yếm vốn là nội y của phụ nữ xưa, thiết kế hình thoi với những dải dây lụa buộc nhẹ sau gáy và ngang eo.'],
            ['icon'=>'fas fa-palette','title'=>'Bảng Màu Cảm Xúc','text'=>'Gái quê mặc yếm nâu thô mộc. Cô gái thị thành chuộng yếm hoa hiên. Và "Yếm Đào" rực rỡ dành riêng cho ngày hội xuân.'],
            ['icon'=>'fas fa-music','title'=>'Nguồn Cảm Hứng','text'=>'Chiếc yếm đi vào ca dao tục ngữ nhẹ nhàng như hơi thở. Yếm là vũ khí của sự nữ tính và vẻ đẹp phồn thực.'],
            ['icon'=>'fas fa-fire','title'=>'Sự Trỗi Dậy Phá Cách','text'=>'Ngày nay, áo Yếm được thiết kế đầy táo bạo, trở thành trang phục thời trang độc lập, cá tính và quyến rũ trên phố.']
        ],
    ],
    'ao-tu-than' => [
        'title'    => 'Áo Tứ Thân — Hồn Quê Đồng Bằng Bắc Bộ',
        'era'      => 'Thế kỷ 16 đến nay',
        'hero_img' => 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?w=1400',
        'intro'    => 'Áo Tứ Thân là trang phục truyền thống đặc trưng của phụ nữ đồng bằng Bắc Bộ, gắn liền với hình ảnh những liền chị quan họ Bắc Ninh.',
        'chapters' => [
            ['icon'=>'fas fa-music','title'=>'Trang Phục Quan Họ','text'=>'Hình ảnh liền chị quan họ trong tà áo tứ thân nâu non, đầu đội nón quai thao đã trở thành biểu tượng văn hóa Bắc Bộ.'],
            ['icon'=>'fas fa-leaf','title'=>'Bốn Vạt Áo','text'=>'Hai vạt trước buộc lại, hai vạt sau buông thõng. Kỹ thuật may này tạo nên dáng áo mềm mại, ôm nhẹ vào thân.'],
            ['icon'=>'fas fa-gem','title'=>'Phụ Kiện Đi Kèm','text'=>'Một bộ hoàn chỉnh gồm: áo tứ thân, yếm đào bên trong, váy lụa đen, thắt lưng bao, dải lụa mỏ quạ và nón quai thao.'],
            ['icon'=>'fas fa-camera','title'=>'Từ Quê Ra Phố','text'=>'Ngày nay, vẻ đẹp mộc mạc của áo tứ thân đang chinh phục trái tim thế hệ trẻ Việt Nam trên các sàn diễn nghệ thuật lớn.']
        ],
    ],
    'ao-dai' => [
        'title'    => 'Áo Dài — Linh Hồn Phụ Nữ Việt',
        'era'      => 'Thế kỷ 18 đến nay',
        'hero_img' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=1400',
        'intro'    => 'Áo dài — trang phục quốc hồn quốc túy của người Việt, là hiện thân của vẻ đẹp duyên dáng, thanh lịch. Mỗi tà áo dài là một bài thơ.',
        'chapters' => [
            ['icon'=>'fas fa-scroll','title'=>'Hành Trình Tiến Hóa','text'=>'Từ áo giao lĩnh, qua áo ngũ thân, đến cuộc cách mạng Le Mur năm 1930 — áo dài đã tiến hóa để có hình dáng hoàn mỹ nay.'],
            ['icon'=>'fas fa-globe','title'=>'Đại Sứ Văn Hóa','text'=>'Xuất hiện trên các sàn diễn Paris, Milan; được UNESCO công nhận. Áo dài mang theo cả nền văn minh 4000 năm ra thế giới.'],
            ['icon'=>'fas fa-palette','title'=>'Nghệ Thuật Đường May','text'=>'Ôm sát nhưng không bó, tà áo bay phất phới mà vẫn kín đáo. Người thợ may giỏi phải nắm được "nhịp thở" của người mặc.'],
            ['icon'=>'fas fa-heart','title'=>'Vĩnh Cửu Trong Thời Đại Mới','text'=>'Thế hệ trẻ mặc áo dài cách tân, kết hợp phụ kiện đương đại. Áo dài không cũ đi mà ngày càng đẹp hơn, phong phú hơn.']
        ],
    ],
    'ao-ba-ba' => [
        'title'    => 'Áo Bà Ba — Hồn Miền Tây Sông Nước',
        'era'      => 'Thế kỷ 19 đến nay',
        'hero_img' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=1400',
        'intro'    => 'Áo bà ba — biểu tượng của sự giản dị, chân thật và duyên dáng của người phụ nữ Nam Bộ. Gắn bó thiết tha với vùng sông nước Cửu Long.',
        'chapters' => [
            ['icon'=>'fas fa-water','title'=>'Sinh Ra Bên Sông','text'=>'Xuất hiện ở Nam Bộ đầu thế kỷ 19. Thiết kế đơn giản, chẻ tà, có hai túi to, phù hợp tuyệt đối với lối sống miệt vườn.'],
            ['icon'=>'fas fa-tint','title'=>'Màu Sắc Miền Tây','text'=>'Thường có màu đen, trắng hoặc màu tươi sáng như lụa mỡ gà. Vải mỏng thoáng mát phù hợp với khí hậu nóng ẩm miền Nam.'],
            ['icon'=>'fas fa-star','title'=>'Biểu Tượng Văn Học','text'=>'Áo bà ba xuất hiện dày đặc trong thơ ca, vọng cổ. "Cô gái áo bà ba" là hình ảnh không thể thiếu trong ký ức người Nam Bộ.'],
            ['icon'=>'fas fa-leaf','title'=>'Thanh Lịch Hiện Đại','text'=>'Ngày nay, áo bà ba được may từ lụa tơ tằm cao cấp, cách tân tinh tế, tạo tổng thể thanh lịch vừa truyền thống vừa hiện đại.']
        ],
    ],
];

// Lấy cốt truyện
$story = null;
foreach ($stories as $key => $s) {
    if ($key === $dm['slug'] || strpos(strtolower($ten_dm), str_replace('ao-','',$key)) !== false) {
        $story = $s; break;
    }
}
if (!$story) {
    $story = [
        'title'    => $ten_dm . ' — Di Sản Trang Phục Việt',
        'era'      => 'Lịch sử Việt Nam',
        'hero_img' => 'https://images.unsplash.com/photo-1558769132-cb1aea458c5e?w=1400',
        'intro'    => 'Trang phục truyền thống không chỉ là lụa là gấm vóc, mà là sự thêu dệt tinh hoa của cả một nền văn minh.',
        'chapters' => [
            ['icon'=>'fas fa-scroll','title'=>'Dấu Tích Thời Gian','text'=>'Trang phục này gắn liền với lịch sử phát triển của người Việt, là chứng nhân của thăng trầm văn hóa.'],
            ['icon'=>'fas fa-palette','title'=>'Nghệ Thuật Thủ Công','text'=>'Từng chi tiết được tạo ra bởi bàn tay khéo léo của nghệ nhân: dệt vải, nhuộm màu đến thêu hoa văn.'],
            ['icon'=>'fas fa-heart','title'=>'Chiều Sâu Văn Hóa','text'=>'Ngôn ngữ không lời biểu đạt địa vị, phẩm chất và tư duy thẩm mỹ tuyệt vời của người Việt.'],
            ['icon'=>'fas fa-star','title'=>'Tiếp Bước Tương Lai','text'=>'Thế hệ trẻ hôm nay đang khoác lên mình trang phục này với niềm tự hào mãnh liệt về dân tộc.']
        ]
    ];
}

include 'resources/views/layouts/header.php';
?>

<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<style>
/* MÀU ĐỎ ĐÔ ĐỒNG NHẤT TOÀN TRANG */
:root{ --cr:#8B0000; --go:#C9A84C; --pa:#FAF6EE; --ink:#1A0A0A; --mu:#666; --bd:#E8E1D5; }
body{ font-family:'EB Garamond',Georgia,serif; background:#fff; color:var(--ink); overflow-x:hidden; }

/* BREADCRUMB */
.bc{background:var(--pa); border-bottom:1px solid var(--bd); padding:10px 0; font-size:.85rem; font-family: 'Poppins', sans-serif;}
.bc-inner{max-width:1200px; margin:0 auto; padding:0 24px; display:flex; gap:8px; align-items:center;}
.bc a{color:var(--cr); text-decoration:none; font-weight: 500;}.bc .sep{color:#ccc;}

/* HERO BANNER */
.dm-hero{position:relative; height:88vh; min-height:520px; display:flex; align-items:center; justify-content:center; text-align:center; overflow:hidden;}
.dm-hero-bg{position:absolute; inset:0; background-image:url('<?= $story['hero_img'] ?>'); background-size:cover; background-position:center; filter:brightness(0.4); transition:transform 8s ease; transform:scale(1.05);}
.dm-hero:hover .dm-hero-bg{transform:scale(1);}
.dm-hero-content{position:relative; z-index:2; max-width:900px; padding:0 24px;}
.dm-era{display:inline-block; background:rgba(201,168,76,.15); border:1px solid rgba(201,168,76,.4); color:var(--go); font-size:.8rem; font-weight:700; letter-spacing:3px; text-transform:uppercase; padding:6px 20px; border-radius:30px; margin-bottom:20px; font-family: 'Poppins', sans-serif;}
.dm-hero h1{font-family:'Cormorant Garamond',Georgia,serif; font-size:clamp(2.5rem, 5vw, 4rem); font-weight:700; color:#fff; line-height:1.2; margin-bottom:20px; text-shadow:0 4px 15px rgba(0,0,0,.6);}
.dm-hero-intro{font-size:1.2rem; color:rgba(255,255,255,.85); line-height:1.8; max-width:700px; margin:0 auto 30px; font-style: italic;}
.btn-explore{padding:12px 32px; background:var(--go); color:#1A0A0A; border:2px solid var(--go); border-radius:30px; font-family:'Cormorant Garamond',Georgia,serif; font-size:1.05rem; font-weight:700; letter-spacing:1px; cursor:pointer; text-decoration:none; transition:all .3s; display:inline-block;}
.btn-explore:hover{background:transparent; color:var(--go); transform:translateY(-3px); box-shadow:0 10px 20px rgba(201,168,76,.2);}
.btn-outline-explore{background:transparent; color:white; border:2px solid white;}
.btn-outline-explore:hover{background:white; color:var(--cr);}

.scroll-down{position:absolute; bottom:28px; left:50%; transform:translateX(-50%); z-index:3; animation:bounce 2s infinite;}
@keyframes bounce{0%,100%{transform:translateX(-50%) translateY(0)} 50%{transform:translateX(-50%) translateY(-8px)}}
.scroll-down i{color:rgba(255,255,255,.6); font-size:1.5rem;}

/* AI NARRATOR (Cuốn thư) */
.ai-narrator { background: url('https://www.transparenttextures.com/patterns/rice-paper.png') var(--pa); padding: 80px 0; }
.ai-scroll-box { max-width: 900px; margin: 0 auto; background: #fff; border: 1px solid var(--go); padding: 50px 60px; position: relative; box-shadow: 0 15px 40px rgba(0,0,0,0.08); border-radius: 4px; }
.ai-scroll-box::before, .ai-scroll-box::after { content:''; position:absolute; left: -10px; right: -10px; height: 12px; background: var(--cr); border-radius: 6px; }
.ai-scroll-box::before { top: -6px; } .ai-scroll-box::after { bottom: -6px; }
.ai-label{display:inline-flex; align-items:center; gap:10px; color:var(--cr); font-size:1rem; font-weight:700; font-family: 'Cormorant Garamond', serif; border-bottom: 2px solid var(--go); padding-bottom: 10px; margin-bottom: 25px;}
.ai-label i{color: var(--go); font-size: 1.2rem; animation:pulse 2s infinite;}
.ai-text{font-size:1.15rem; line-height:2.1; color:#333; text-align: justify;}
.ai-text .highlight{color:var(--cr); font-weight: 600; font-family: 'Cormorant Garamond', serif; font-size: 1.3rem;}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}

.quote-box { margin-top:30px; padding:20px 25px; border-left:3px solid var(--go); background:rgba(201,168,76,.06); }
.quote-text { font-size:1.1rem; font-style:italic; color:#555; line-height:1.8; margin:0; }
.quote-author { margin-top:12px; font-size:.75rem; color:var(--cr); letter-spacing:2px; text-transform:uppercase; font-family: 'Poppins', sans-serif;}

/* CHAPTERS */
.chapters{padding:80px 0; background: #fff;}
.chapter-grid{display:grid; grid-template-columns:repeat(2, 1fr); gap:30px; margin-top:50px; max-width: 1100px; margin-left: auto; margin-right: auto;}
.chapter-card{background:#fff; border:1px solid var(--bd); padding:35px; border-radius: 10px; transition:all .3s; position: relative; z-index: 1;}
.chapter-card::after { content:''; position:absolute; top:0; left:0; width:0; height:100%; background:var(--pa); z-index: -1; transition: width 0.4s ease; }
.chapter-card:hover::after { width: 100%; }
.chapter-card:hover{transform:translateY(-5px); border-color: var(--go); box-shadow:0 15px 30px rgba(201,168,76,.15);}
.chapter-icon{width:60px; height:60px; border-radius:50%; background:var(--pa); border: 1px dashed var(--go); display:flex; align-items:center; justify-content:center; color:var(--cr); font-size:1.4rem; margin-bottom:20px;}
.chapter-title{font-family:'Cormorant Garamond',Georgia,serif; font-size:1.4rem; font-weight:700; color:var(--ink); margin-bottom:12px;}
.chapter-text{font-size:.95rem; color:var(--mu); line-height:1.8;}

/* ========================================================
   KHUNG SẢN PHẨM GỐC: KHÔNG BO TRÒN, NÚT XẾP DỌC VUÔNG VỨC
   ======================================================== */
.products-section{padding:80px 0; background:var(--pa);}
.section-title{font-family:'Cormorant Garamond',Georgia,serif; font-size:2.5rem; font-weight:700; color:var(--cr); text-align:center; margin-bottom:10px;}
.section-sub{text-align:center; color:var(--mu); font-size:1rem; margin-bottom:50px; font-family: 'Poppins', sans-serif;}
.product-grid{display:grid; grid-template-columns:repeat(4, 1fr); gap:22px; max-width: 1200px; margin: 0 auto; padding: 0 24px;}

.product-card{background:#fff; border:none; border-radius:0; box-shadow:0 2px 10px rgba(0,0,0,.05); transition:transform .3s, box-shadow .3s;}
.product-card:hover{transform:translateY(-6px); box-shadow:0 16px 40px rgba(0,0,0,.15);}

.product-img-wrap{position:relative; padding-top:140%; background: var(--pa); overflow: hidden;}
.product-img-wrap img{position:absolute; inset:0; width:100%; height:100%; object-fit:contain; padding: 10px; transition:transform .5s;}
.product-card:hover .product-img-wrap img{transform:scale(1.08);}

/* Nhãn (Badges) góc cạnh y như ảnh */
.product-badge-hot{position:absolute; top:12px; left:12px; background:var(--cr); color:var(--go); font-size:.7rem; font-weight:700; padding:6px 12px; border-radius:0; font-family: 'Cormorant Garamond', serif; z-index:2; letter-spacing: 1px;}
.product-badge-sale{position:absolute; top:12px; right:12px; background:var(--go); color:var(--cr); font-size:.75rem; font-weight:700; padding:6px 10px; border-radius:0; font-family: 'Cormorant Garamond', serif; z-index:2;}

/* Overlay tối màu đỏ nâu giống hệt hình */
.product-overlay{position:absolute; inset:0; background:rgba(128, 48, 48, 0.85); display:flex; flex-direction:column; align-items:center; justify-content:center; gap:15px; opacity:0; transition:opacity .3s;}
.product-card:hover .product-overlay{opacity:1;}

/* Nút bấm trong ảnh: Không bo tròn, xếp dọc */
.btn-overlay{width: 65%; padding:12px 0; border-radius:0; font-size:.9rem; font-weight:600; text-decoration:none; transition:all .2s; font-family: 'Cormorant Garamond', serif; border:none; cursor:pointer; text-align: center; letter-spacing: 1px;}
.btn-view{background:var(--go); color:var(--cr);}
.btn-buy{background:transparent; color:#fff; border: 1px solid rgba(255,255,255,0.6);}
.btn-view:hover{background:#fff; color:var(--cr);} 
.btn-buy:hover{background:#fff; color:var(--cr); border-color: #fff;}

/* Khu vực Text bên dưới */
.product-info{padding:20px 20px 25px 20px; text-align: left; background: #fff;}
.product-category{font-size: .75rem; color: var(--go); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 8px; font-family: 'Cormorant Garamond', serif;}
.product-name{font-family:'Cormorant Garamond',Georgia,serif; font-size:1.3rem; font-weight:700; margin-bottom:10px; line-height: 1.4;}
.product-name a{text-decoration:none; color:var(--ink); transition: color 0.2s;}
.product-name a:hover{color:var(--cr);}
.product-desc{font-size: .9rem; color: var(--mu); margin-bottom: 12px; line-height: 1.5; font-family: 'EB Garamond', serif;}
.product-price{font-family:'Cormorant Garamond',Georgia,serif; font-size:1.4rem; font-weight:700; color:var(--cr);}
.product-price-old{font-size:.95rem; color:#aaa; text-decoration:line-through; margin-left:10px;}
.out-stock{font-size:.8rem; color:#dc2626; margin-top:5px; font-weight: 600;}

/* CTA */
.cta-section{background:linear-gradient(135deg, var(--cr), #3D0000); padding:72px 0; text-align:center; color:#fff;}
.cta-section h2{font-family:'Cormorant Garamond',Georgia,serif; font-size:2.5rem; font-weight:700; margin-bottom:12px;}
.cta-section p{font-size:1rem; color:rgba(255,255,255,.75); margin-bottom:28px;}

/* KHÁM PHÁ THÊM */
.related-dm-section{padding:60px 0; background: #fff;}
.related-dm-title{font-family:'Cormorant Garamond',Georgia,serif; font-size:1.8rem; font-weight:700; color:var(--ink); text-align:center; margin-bottom:28px;}
.related-grid{display:grid; grid-template-columns:repeat(4,1fr); gap:16px; max-width:1200px; margin:0 auto; padding:0 24px;}
.related-card{display:block; background:var(--pa); border:1px solid var(--bd); border-radius:6px; padding:18px; text-align:center; text-decoration:none; transition:all .2s;}
.related-card:hover{border-color:var(--cr); transform:translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.05);}
.related-card i{font-size:1.5rem; color:var(--go); margin-bottom:8px; display:block;}
.related-name{font-family:'Cormorant Garamond',Georgia,serif; font-weight:700; color:var(--ink); font-size:.95rem;}
.related-txt{font-size:.72rem; color:var(--mu); margin-top:4px;}

@media(max-width:992px){.product-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:768px){.chapter-grid{grid-template-columns:1fr}.product-grid{grid-template-columns:repeat(2,1fr)}.related-grid{grid-template-columns:repeat(2,1fr)}.ai-scroll-box{padding: 30px;}.dm-hero{height:70vh}}
@media(max-width:480px){.product-grid{grid-template-columns:1fr}}
</style>

<div class="dm-hero">
    <div class="dm-hero-bg"></div>
    <div class="dm-hero-content">
        <span class="dm-era" data-aos="fade-down"><i class="fas fa-history me-2"></i><?= htmlspecialchars($story['era']) ?></span>
        <h1 data-aos="zoom-in" data-aos-delay="100"><?= htmlspecialchars($story['title']) ?></h1>
        <p class="dm-hero-intro" data-aos="fade-up" data-aos-delay="200">"<?= htmlspecialchars($story['intro']) ?>"</p>
        <div data-aos="fade-up" data-aos-delay="300">
            <a href="#san-pham" class="btn-explore me-3">Khám Phá Cổ Phục</a>
            <a href="#cau-chuyen" class="btn-explore btn-outline-explore">Lắng Nghe Lịch Sử</a>
        </div>
    </div>
    <div class="scroll-down"><i class="fas fa-chevron-down"></i></div>
</div>

<div class="bc">
    <div class="bc-inner">
        <a href="index.php">Trang chủ</a><span class="sep">/</span>
        <?php if ($dm_cha): ?>
        <a href="bosuutap.php?danh_muc=<?= $dm_cha['id'] ?>"><?= htmlspecialchars($dm_cha['ten_danh_muc']) ?></a><span class="sep">/</span>
        <?php endif; ?>
        <span><?= htmlspecialchars($ten_dm) ?></span>
    </div>
</div>

<section class="ai-narrator" id="cau-chuyen">
    <div class="ai-scroll-box" data-aos="fade-up">
        <div class="ai-label"><i class="fas fa-magic"></i> AI Kể Chuyện Lịch Sử Vân Y Các</div>
        <p class="ai-text">
            Chào bạn, lữ khách vượt thời gian! Tôi là <span class='highlight'>Trí tuệ nhân tạo của Vân Y Các</span>. Hãy ngồi xuống đây, nhấp một ngụm trà, và để tôi dệt lại cho bạn nghe về huyền thoại của <span class='highlight'><?= htmlspecialchars($ten_dm) ?></span>. 
            <br><br>
            Bạn biết không, ẩn sau những đường kim mũi chỉ tưởng chừng như vô tri kia là cả một thời kỳ <span class='highlight'><?= htmlspecialchars($story['era']) ?></span> oai hùng. Chiếc áo này không đơn thuần là lụa là gấm vóc che thân, mà nó là bản di chúc văn hóa, là khí chất và đạo lý làm người mà ông cha ta đã gửi gắm lại cho ngàn đời sau.
        </p>
        <div class="quote-box" data-aos="fade-up" data-aos-delay="150">
            <p class="quote-text">"<?= htmlspecialchars($story['intro']) ?>"</p>
            <div class="quote-author">— Vân Y Các · AI Storyteller</div>
        </div>
    </div>
</section>

<section class="chapters">
    <div style="text-align:center;" data-aos="fade-down">
        <h2 style="font-family:'Cormorant Garamond',serif; font-size:2.2rem; font-weight:700; color:var(--cr);">Lật Mở Lịch Sử</h2>
        <p style="color:var(--mu); font-family: 'Poppins', sans-serif;">Bốn mảnh ghép tạo nên kiệt tác <?= htmlspecialchars($ten_dm) ?></p>
    </div>
    <div class="chapter-grid">
        <?php foreach ($story['chapters'] as $idx => $ch): ?>
        <div class="chapter-card" data-aos="fade-up" data-aos-delay="<?= $idx * 100 ?>">
            <div class="chapter-icon"><i class="<?= $ch['icon'] ?>"></i></div>
            <div class="chapter-title"><?= htmlspecialchars($ch['title']) ?></div>
            <p class="chapter-text"><?= htmlspecialchars($ch['text']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="products-section" id="san-pham">
    <div style="max-width:1200px; margin:0 auto; padding:0 24px">
        <h2 class="section-title" data-aos="fade-up">Tuyệt Tác <?= htmlspecialchars($ten_dm) ?></h2>
        <p class="section-sub" data-aos="fade-up">Được phục dựng tỉ mỉ bởi nghệ nhân Vân Y Các</p>

        <?php if ($sp_list && $sp_list->num_rows > 0): ?>
        <div class="product-grid">
            <?php while ($sp = $sp_list->fetch_assoc()): ?>
            <div class="product-card" data-aos="fade-up">
                <div class="product-img-wrap">
                    <?php if ($sp['noi_bat']): ?><div class="product-badge-hot">NỔI BẬT</div><?php endif; ?>
                    <?php if ($sp['gia_goc'] && $sp['gia_goc'] > $sp['gia_ban']): ?>
                    <div class="product-badge-sale">-<?= round(($sp['gia_goc']-$sp['gia_ban'])/$sp['gia_goc']*100) ?>%</div>
                    <?php endif; ?>
                    
                    <img src="image/<?= htmlspecialchars($sp['duong_dan'] ?? 'no-image.jpg') ?>" onerror="this.src='https://placehold.co/400x500/FAF6EE/8B0000?text=Vân+Y+Các'" alt="SP" loading="lazy">
                    
                    <div class="product-overlay">
                        <a href="sanpham.php?id=<?= $sp['id'] ?>" class="btn-overlay btn-view"><i class="fas fa-eye me-2"></i>Xem Chi Tiết</a>
                        <?php if ($sp['so_luong_ton'] > 0): ?>
                            <?php if ($da_dang_nhap): ?>
                            <a href="sanpham.php?id=<?= $sp['id'] ?>" class="btn-overlay btn-buy"><i class="fas fa-shopping-bag me-2"></i>Chọn Mua</a>
                            <?php else: ?>
                            <button class="btn-overlay btn-buy" data-bs-toggle="modal" data-bs-target="#loginModal"><i class="fas fa-shopping-bag me-2"></i>Chọn Mua</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="btn-overlay" style="background:transparent; border:1px solid #ccc; color:#ccc;">Hết Hàng</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="product-info">
                    <div class="product-category">
                        <?= htmlspecialchars($ten_dm_cha ? $ten_dm_cha . ' · ' : '') ?><?= htmlspecialchars($ten_dm) ?>
                    </div>
                    <h3 class="product-name"><a href="sanpham.php?id=<?= $sp['id'] ?>"><?= htmlspecialchars($sp['ten_vi']) ?></a></h3>
                    <?php if ($sp['mo_ta_ngan']): ?>
                    <p class="product-desc"><?= htmlspecialchars(mb_substr($sp['mo_ta_ngan'],0,60)).'...' ?></p>
                    <?php endif; ?>
                    <div>
                        <span class="product-price"><?= number_format($sp['gia_ban'],0,',','.') ?> ₫</span>
                        <?php if ($sp['gia_goc'] && $sp['gia_goc'] > $sp['gia_ban']): ?>
                        <span class="product-price-old"><?= number_format($sp['gia_goc'],0,',','.') ?> ₫</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($sp['so_luong_ton'] <= 0): ?><div class="out-stock">Tạm hết hàng</div><?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
       <div style="text-align:center; margin-top:50px;" data-aos="fade-up">
    <a href="bosuutap.php" class="btn-explore" style="color:#fff; background:var(--cr); border:none; border-radius: 30px;">Xem Tất Cả Sản Phẩm →</a>
</div>
        <?php else: ?>
        <div class="no-products" data-aos="fade-up">
            <i class="fas fa-box-open"></i>
            <p>Vân Y Các đang chế tác thêm các mẫu <?= htmlspecialchars($ten_dm) ?>. Bạn vui lòng quay lại sau nhé!</p>
            <a href="bosuutap.php" style="color:var(--cr);font-weight:700">Xem tất cả sản phẩm khác →</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<section class="cta-section">
    <div style="max-width:700px; margin:0 auto; padding:0 24px" data-aos="zoom-in">
        <h2>Đặt May Riêng <?= htmlspecialchars($ten_dm) ?> Cho Bạn</h2>
        <p>Chúng tôi nhận may đo theo số đo cá nhân, đảm bảo vừa vặn hoàn hảo với từng vóc dáng và tôn lên khí chất riêng của bạn.</p>
        <a href="bosuutap.php?danh_muc=<?= $dm['id'] ?>" class="btn-explore me-3" style="border-radius: 30px;"><i class="fas fa-shopping-bag me-2"></i>Xem Mẫu</a>
        <a href="#" class="btn-explore" style="background:transparent; border:2px solid var(--go); color:var(--go); border-radius: 30px;"><i class="fas fa-phone me-2"></i>Tư Vấn Ngay</a>
    </div>
</section>

<?php
$dm_lienquan = $conn->query("SELECT * FROM danh_muc WHERE id_cha IS NOT NULL AND id!={$dm['id']} AND trang_thai=1 ORDER BY thu_tu ASC LIMIT 4");
if ($dm_lienquan && $dm_lienquan->num_rows > 0):
?>
<section class="related-dm-section">
    <div style="max-width:1200px; margin:0 auto; padding:0 24px">
        <h3 class="related-dm-title" data-aos="fade-up">Khám Phá Thêm Cổ Phục</h3>
        <div class="related-grid">
            <?php while ($dlq = $dm_lienquan->fetch_assoc()): ?>
            <a href="danh-muc-detail.php?id=<?= $dlq['id'] ?>" class="related-card" data-aos="fade-up" data-aos-delay="100">
                <i class="fas fa-scroll"></i>
                <div class="related-name"><?= htmlspecialchars($dlq['ten_danh_muc']) ?></div>
                <div class="related-txt">Xem câu chuyện lịch sử →</div>
            </a>
            <?php endwhile; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include 'resources/views/layouts/footer.php'; ?>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init({ once: true, duration: 800, offset: 60 });
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) { target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    });
});
</script>