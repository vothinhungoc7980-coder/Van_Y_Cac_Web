<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Đồng bộ session key
if (isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id']   = $_SESSION['user']['id'];
    $_SESSION['ho_ten']    = $_SESSION['user']['fullname'];
    $_SESSION['tai_khoan'] = $_SESSION['user']['username'];
    $_SESSION['vai_tro']   = $_SESSION['user']['role'];
}

function requireAdmin(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /admin/login.php'); exit;
    }
    if (($_SESSION['vai_tro'] ?? '') !== 'Quản trị viên') {
        session_destroy();
        header('Location: /admin/login.php?err=no_permission'); exit;
    }
}
function isAdmin(): bool {
    return ($_SESSION['vai_tro'] ?? '') === 'Quản trị viên';
}