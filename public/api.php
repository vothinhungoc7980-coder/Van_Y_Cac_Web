<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

/* =========================
   1. KẾT NỐI DATABASE
========================= */
$conn = new mysqli("localhost", "root", "", "van_y_cac");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Lỗi kết nối database"]);
    exit();
}
$conn->set_charset("utf8");

/* =========================
   2. NHẬN REQUEST
========================= */
$action = $_GET['action'] ?? '';
$data   = json_decode(file_get_contents("php://input"), true) ?? [];

// Nếu action nằm trong body JSON thì ưu tiên lấy từ đó
if (!$action && isset($data['action'])) {
    $action = $data['action'];
}

/* =========================
   HELPER: đếm giỏ hàng
========================= */
function cartCount($conn, $uid) {
    $r = $conn->query("SELECT COALESCE(SUM(so_luong),0) c FROM gio_hang WHERE id_khach_hang=$uid");
    return (int)($r ? $r->fetch_assoc()['c'] : 0);
}

/* =========================
   3. XỬ LÝ API
========================= */
switch ($action) {

    /* ===================================================
       ĐĂNG KÝ
    =================================================== */
    case 'register':

        $hoVaTen     = trim($data['fullname']  ?? '');
        $taiKhoan    = trim($data['username']  ?? '');
        $email       = trim($data['email']     ?? '');
        $soDienThoai = trim($data['phone']     ?? '');
        $matKhau     = password_hash($data['password'] ?? '', PASSWORD_DEFAULT);

        if (empty($hoVaTen) || empty($taiKhoan) || empty($email) || empty($soDienThoai) || empty($data['password'])) {
            echo json_encode(["success" => false, "message" => "Vui lòng nhập đầy đủ thông tin!"]);
            exit();
        }

        // Kiểm tra trùng username, email hoặc phone
        $stmt_check = $conn->prepare("SELECT idKhachHang FROM khachhang WHERE TaiKhoan = ? OR Email = ? OR SoDienThoai = ?");
        $stmt_check->bind_param("sss", $taiKhoan, $email, $soDienThoai);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            echo json_encode(["success" => false, "message" => "Tài khoản, Email hoặc SĐT đã tồn tại!"]);
        } else {
            $vaiTro    = "Khách hàng";
            $trangThai = "Kích hoạt";

            $stmt_insert = $conn->prepare("INSERT INTO khachhang (TaiKhoan, MatKhau, HoVaTen, Email, SoDienThoai, VaiTro, TrangThai) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("sssssss", $taiKhoan, $matKhau, $hoVaTen, $email, $soDienThoai, $vaiTro, $trangThai);

            if ($stmt_insert->execute()) {
                echo json_encode(["success" => true, "message" => "Đăng ký thành công! Vui lòng đăng nhập."]);
            } else {
                echo json_encode(["success" => false, "message" => "Lỗi lưu dữ liệu!"]);
            }
        }
        break;


    /* ===================================================
       ĐĂNG NHẬP
    =================================================== */
    case 'login':

        $taiKhoan    = trim($data['username'] ?? '');
        $matKhauNhap = $data['password'] ?? '';

        $stmt = $conn->prepare("SELECT * FROM khachhang WHERE (TaiKhoan = ? OR Email = ?) AND TrangThai = 'Kích hoạt'");
        $stmt->bind_param("ss", $taiKhoan, $taiKhoan);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            if (password_verify($matKhauNhap, $row['MatKhau'])) {

                // Set session — tương thích cả header cũ và mới
                $_SESSION['user'] = [
                    "id"       => $row['idKhachHang'],
                    "username" => $row['TaiKhoan'],
                    "fullname" => $row['HoVaTen'],
                    "email"    => $row['Email'],
                    "role"     => $row['VaiTro'],
                ];
                // Cũng set các key riêng lẻ để tương thích với cart.php và trang admin
                $_SESSION['user_id']   = $row['idKhachHang'];
                $_SESSION['ho_ten']    = $row['HoVaTen'];
                $_SESSION['tai_khoan'] = $row['TaiKhoan'];
                $_SESSION['vai_tro']   = $row['VaiTro'];

                // Cập nhật lần cuối đăng nhập
                $uid = $row['idKhachHang'];
                $conn->query("UPDATE khachhang SET LanCuoiDangNhap=NOW() WHERE idKhachHang=$uid");

                echo json_encode([
                    "success"  => true,
                    "message"  => "Đăng nhập thành công!",
                    "is_admin" => $row['VaiTro'] === 'Quản trị viên',
                    "user"     => $_SESSION['user'],
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "Sai mật khẩu!"]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Tài khoản không tồn tại hoặc đã bị khoá!"]);
        }
        break;


    /* ===================================================
       KIỂM TRA SESSION
    =================================================== */
    case 'getUser':

        if (isset($_SESSION['user'])) {
            echo json_encode([
                "loggedIn" => true,
                "user"     => $_SESSION['user']
            ]);
        } else {
            echo json_encode(["loggedIn" => false]);
        }
        break;


    /* ===================================================
       ĐĂNG XUẤT
    =================================================== */
    case 'logout':

        session_unset();
        session_destroy();
        echo json_encode(["success" => true]);
        break;


    /* ===================================================
       GIỎ HÀNG — tất cả action cart gộp vào đây
       Các action con: add | update | remove | clear | get
    =================================================== */
    case 'cart':

        // Hỗ trợ cả $_SESSION['user'] và $_SESSION['user_id']
        if (!isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
            echo json_encode([
                "success"    => false,
                "need_login" => true,
                "message"    => "Vui lòng đăng nhập để thực hiện thao tác này."
            ]);
            exit();
        }
        $uid = (int)($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0);
        if (!$uid) {
            echo json_encode(["success"=>false,"need_login"=>true,"message"=>"Phiên đăng nhập không hợp lệ."]);
            exit();
        }
        $cart_action = $data['action'] ?? 'get';

        // --- ĐỔI SIZE ---
        if ($cart_action === 'change_size') {
            $gh_id    = (int)($data['gh_id'] ?? 0);
            $new_size = trim($data['size'] ?? '');
            if (!$gh_id || !$new_size) {
                echo json_encode(["success"=>false,"message"=>"Dữ liệu không hợp lệ"]);
                exit();
            }
            $size_esc = $conn->real_escape_string($new_size);
            // Kiểm tra đã có size này trong giỏ chưa (cùng sản phẩm)
            $cur = $conn->query("SELECT id_san_pham, so_luong FROM gio_hang WHERE id=$gh_id AND id_khach_hang=$uid LIMIT 1")->fetch_assoc();
            if (!$cur) { echo json_encode(["success"=>false,"message"=>"Không tìm thấy sản phẩm trong giỏ"]); exit(); }
            $sp_id = $cur['id_san_pham'];
            $exist = $conn->query("SELECT id, so_luong FROM gio_hang WHERE id_khach_hang=$uid AND id_san_pham=$sp_id AND size='$size_esc' AND id!=$gh_id LIMIT 1")->fetch_assoc();
            if ($exist) {
                // Gộp số lượng vào item cùng size rồi xóa item cũ
                $new_qty = $exist['so_luong'] + $cur['so_luong'];
                $conn->query("UPDATE gio_hang SET so_luong=$new_qty WHERE id={$exist['id']}");
                $conn->query("DELETE FROM gio_hang WHERE id=$gh_id");
            } else {
                $conn->query("UPDATE gio_hang SET size='$size_esc' WHERE id=$gh_id AND id_khach_hang=$uid");
            }
            echo json_encode(["success"=>true, "cart_count"=>cartCount($conn,$uid)]);
            exit();
        }

        // --- THÊM VÀO GIỎ ---
        if ($cart_action === 'add') {
            $sp_id = (int)($data['id_san_pham'] ?? $data['sp_id'] ?? 0);
            $size  = trim($data['size'] ?? 'M');
            $qty   = max(1, (int)($data['so_luong'] ?? $data['qty'] ?? 1));

            if (!$sp_id) {
                echo json_encode(["success" => false, "message" => "Sản phẩm không hợp lệ"]);
                exit();
            }

            // Kiểm tra sản phẩm còn hàng
            $sp = $conn->query("SELECT id, so_luong_ton FROM san_pham WHERE id=$sp_id AND trang_thai=1 LIMIT 1")->fetch_assoc();
            if (!$sp) {
                echo json_encode(["success" => false, "message" => "Sản phẩm không tồn tại"]);
                exit();
            }
            if ($sp['so_luong_ton'] <= 0) {
                echo json_encode(["success" => false, "message" => "Sản phẩm đã hết hàng"]);
                exit();
            }

            // Kiểm tra đã có trong giỏ chưa (cùng sp + size)
            $size_esc = $conn->real_escape_string($size);
            $exist = $conn->query("SELECT id, so_luong FROM gio_hang WHERE id_khach_hang=$uid AND id_san_pham=$sp_id AND size='$size_esc' LIMIT 1")->fetch_assoc();

            if ($exist) {
                $new_qty = min($exist['so_luong'] + $qty, $sp['so_luong_ton']);
                $conn->query("UPDATE gio_hang SET so_luong=$new_qty WHERE id={$exist['id']}");
            } else {
                $conn->query("INSERT INTO gio_hang (id_khach_hang, id_san_pham, so_luong, size) VALUES ($uid, $sp_id, $qty, '$size_esc')");
            }

            echo json_encode([
                "success"    => true,
                "message"    => "Đã thêm vào giỏ hàng!",
                "cart_count" => cartCount($conn, $uid)
            ]);
            exit();
        }

        // --- CẬP NHẬT SỐ LƯỢNG ---
        if ($cart_action === 'update') {
            $gh_id = (int)($data['gh_id'] ?? 0);
            $qty   = (int)($data['so_luong'] ?? 1);

            if ($qty <= 0) {
                $conn->query("DELETE FROM gio_hang WHERE id=$gh_id AND id_khach_hang=$uid");
            } else {
                $conn->query("UPDATE gio_hang SET so_luong=$qty WHERE id=$gh_id AND id_khach_hang=$uid");
            }

            echo json_encode([
                "success"    => true,
                "cart_count" => cartCount($conn, $uid)
            ]);
            exit();
        }

        // --- XÓA 1 SẢN PHẨM ---
        if ($cart_action === 'remove') {
            $gh_id = (int)($data['gh_id'] ?? 0);
            $conn->query("DELETE FROM gio_hang WHERE id=$gh_id AND id_khach_hang=$uid");

            echo json_encode([
                "success"    => true,
                "cart_count" => cartCount($conn, $uid)
            ]);
            exit();
        }

        // --- XÓA TẤT CẢ ---
        if ($cart_action === 'clear') {
            $conn->query("DELETE FROM gio_hang WHERE id_khach_hang=$uid");
            echo json_encode(["success" => true, "cart_count" => 0]);
            exit();
        }

        // --- LẤY GIỎ HÀNG ---
        $items = [];
        $rs = $conn->query("
            SELECT gh.id, gh.id_san_pham, gh.so_luong, gh.size,
                   sp.ten_vi, sp.gia_ban, sp.gia_goc, sp.duong_dan, sp.so_luong_ton
            FROM gio_hang gh
            JOIN san_pham sp ON gh.id_san_pham = sp.id
            WHERE gh.id_khach_hang = $uid AND sp.trang_thai = 1
            ORDER BY gh.ngay_them DESC
        ");
        while ($r = $rs->fetch_assoc()) $items[] = $r;

        echo json_encode([
            "success"    => true,
            "cart_count" => cartCount($conn, $uid),
            "cart_items" => $items
        ]);
        break;


    /* ===================================================
       HỦY ĐƠN HÀNG
    =================================================== */
    case 'cancel_order':

        if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
            echo json_encode(["success"=>false,"message"=>"Chưa đăng nhập."]);
            exit();
        }
        $uid    = (int)($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0);
        $don_id = (int)($data['id'] ?? $_POST['id'] ?? 0);
        $ly_do  = trim($data['ly_do'] ?? $_POST['ly_do'] ?? '');

        if (!$uid || !$don_id) {
            echo json_encode(["success"=>false,"message"=>"Dữ liệu không hợp lệ."]);
            exit();
        }

        // Kiểm tra đơn thuộc user và đang chờ xác nhận
        $don = $conn->query("SELECT * FROM don_hang WHERE id=$don_id AND id_khach_hang=$uid LIMIT 1")->fetch_assoc();
        if (!$don) {
            echo json_encode(["success"=>false,"message"=>"Không tìm thấy đơn hàng."]);
            exit();
        }
        if ($don['trang_thai_dh'] !== 'Chờ xác nhận') {
            echo json_encode(["success"=>false,"message"=>"Đơn hàng không thể hủy ở trạng thái này."]);
            exit();
        }

        // Ghi lý do vào ghi_chu
        $ghi_cu  = $don['ghi_chu'] ?? '';
        $ghi_moi = $ghi_cu ? "$ghi_cu | [Khách hủy] $ly_do" : "[Khách hủy] $ly_do";
        $ghi_esc = $conn->real_escape_string($ghi_moi);

        if ($conn->query("UPDATE don_hang SET trang_thai_dh='Đã hủy', ghi_chu='$ghi_esc' WHERE id=$don_id AND id_khach_hang=$uid")) {
            // Hoàn kho
            $items = $conn->query("SELECT id_san_pham, so_luong FROM chi_tiet_don_hang WHERE id_don_hang=$don_id");
            while ($it = $items->fetch_assoc()) {
                $conn->query("UPDATE san_pham SET so_luong_ton = so_luong_ton + {$it['so_luong']} WHERE id = {$it['id_san_pham']}");
            }
            echo json_encode(["success"=>true,"message"=>"Đơn hàng đã được hủy thành công.","ma_don"=>$don['ma_don_hang']]);
        } else {
            echo json_encode(["success"=>false,"message"=>"Lỗi hệ thống, vui lòng thử lại."]);
        }
        break;

    /* ===================================================
       DEFAULT
    =================================================== */
    default:
        echo json_encode(["success" => false, "message" => "Yêu cầu API không hợp lệ"]);
        break;
}

/* =========================
   4. ĐÓNG KẾT NỐI
========================= */
$conn->close();
?>