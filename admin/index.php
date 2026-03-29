<?php
// Redirect tất cả vào panel.php
require_once __DIR__ . '/config/auth.php';
requireAdmin();
header('Location: panel.php?page=dashboard');
exit;