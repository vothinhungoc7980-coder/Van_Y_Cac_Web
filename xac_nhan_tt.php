<?php
include 'config/db.php';
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("UPDATE don_hang SET trang_thai_tt = 'Đã thanh toán' WHERE id = $id");
    echo "OK";
}
?>