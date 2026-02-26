<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang cá nhân</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">

    <h2>Xin chào <?php echo $_SESSION['user']['fullname']; ?> 🎉</h2>
    <p>Vai trò: <?php echo $_SESSION['user']['role']; ?></p>

    <a href="index.php" class="btn btn-primary mt-3">Về trang chủ</a>

</body>
</html>