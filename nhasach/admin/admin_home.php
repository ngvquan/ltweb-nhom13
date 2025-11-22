<?php
session_start();
require_once __DIR__ . '/admin_db.php';

if (!isAdmin()) {
    header('Location: admin_login.php');
    exit;
}

$username = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang quản trị</title>
    <link rel="stylesheet" href="../style.css">
    </head>
<body>
    <nav class="top-nav">
        <div class="brand">BookBuy</div>
        <div class="nav-links">
            <span class="welcome">Quản trị: <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></span>
            <a class="btn-link" href="../logout.php">Đăng xuất</a>
        </div>
    </nav>

    <main class="container">
        <section class="card">
            <h1>Bảng điều khiển quản trị</h1>
            <p>Chọn tác vụ bạn muốn thực hiện.</p>
            <div class="actions">
                <a class="btn" href="admin.php">Quản lý sách</a>
                <a class="btn ghost" href="admin_login.php">Quay lại đăng nhập</a>
            </div>
        </section>
    </main>
</body>
</html>
