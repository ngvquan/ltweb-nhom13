<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/admin_db.php';

$db = new AdminDB();

if (isAdmin()) {
    header('Location: theloai.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ tài khoản và mật khẩu.';
    } elseif ($db->login($username, $password)) {
        header('Location: theloai.php');
        exit;
    } else {
        $error = 'Tài khoản hoặc mật khẩu không đúng.';
    }
}

$text = [
    'title' => 'Đăng nhập quản trị',
    'username' => 'Tài khoản',
    'password' => 'Mật khẩu',
    'submit' => 'Đăng nhập',
    'back' => 'Quay lại trang chủ',
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($text['title']) ?></title>
    <link rel="stylesheet" href="../style.css">
    </head>
<body>
    <div class="card login-card">
        <h2><?= $text['title'] ?></h2>

        <?php if ($error): ?>
            <div class="err"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" class="auth-form" autocomplete="off">
            <div class="form-group">
                <label for="username"><?= $text['username'] ?></label>
                <input type="text" name="username" id="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password"><?= $text['password'] ?></label>
                <input type="password" name="password" id="password" required>
            </div>

            <button class="btn" type="submit"><?= $text['submit'] ?></button>
        </form>

        <p class="muted">
            <a href="../index.php"><?= $text['back'] ?></a>
        </p>
    </div>
</body>
</html>

