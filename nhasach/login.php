<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: text/html; charset=UTF-8');

$db = new DB();

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header('Location: admin/theloai.php');
    exit;
}

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } elseif ($db->login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Sai tên đăng nhập hoặc mật khẩu.';
    }
}

$text = [
    'page_title' => 'Đăng nhập',
    'username'   => 'Tên đăng nhập',
    'password'   => 'Mật khẩu',
    'button'     => 'Đăng nhập',
    'no_account' => 'Chưa có tài khoản?',
    'register'   => 'Tạo tài khoản',
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($text['page_title']) ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="style.css">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="description" content="Đăng nhập tài khoản BookBuy">
  <meta name="format-detection" content="telephone=no">
</head>
<body class="home auth-page">
  <header class="site-header">
    <div class="logo">
      <i class="fa-solid fa-book-open-reader" aria-hidden="true"></i>
      BookBuy
    </div>
    <nav>
      <ul>
        <li><a href="index.php">Trang chủ</a></li>
        <li><a href="register.php">Tài khoản</a></li>
      </ul>
    </nav>
    <div class="header-icons">
      <a href="cart.php" aria-label="Giỏ hàng"><i class="fa fa-shopping-cart"></i><span>0</span></a>
    </div>
  </header>

  <main class="auth-hero" role="main">
    <div class="auth-card">
      <div class="auth-card__body">
        <h1 class="auth-title"><?= htmlspecialchars($text['page_title']) ?></h1>

        <?php if ($error): ?>
          <div class="err auth-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form class="auth-form auth-form--modern" method="post" autocomplete="off">
          <div class="auth-field">
            <label for="username"><?= htmlspecialchars($text['username']) ?></label>
            <input type="text" name="username" id="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
          </div>

          <div class="auth-field">
            <label for="password"><?= htmlspecialchars($text['password']) ?></label>
            <input type="password" name="password" id="password" required>
          </div>

          <button class="btn auth-submit" type="submit">
            <i class="fa-solid fa-arrow-right-to-bracket" aria-hidden="true"></i>
            <?= htmlspecialchars($text['button']) ?>
          </button>
        </form>

        <p class="muted auth-switch">
          <?= htmlspecialchars($text['no_account']) ?>
          <a href="register.php"><?= htmlspecialchars($text['register']) ?></a>
        </p>
      </div>
    </div>
  </main>

  <footer>
    © 2025 BookBuy
  </footer>
</body>
</html>
