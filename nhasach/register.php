<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: text/html; charset=UTF-8');

$db = new DB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($fullname === '') {
        $errors[] = 'Vui lòng nhập họ và tên.';
    }
    if ($phone === '') {
        $errors[] = 'Vui lòng nhập số điện thoại.';
    }
    if ($address === '') {
        $errors[] = 'Vui lòng nhập địa chỉ.';
    }
    if ($username === '') {
        $errors[] = 'Tên đăng nhập không được để trống.';
    }
    if ($password === '' || $confirmPassword === '') {
        $errors[] = 'Mật khẩu và xác nhận mật khẩu không được để trống.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } elseif ($password !== $confirmPassword) {
        $errors[] = 'Xác nhận mật khẩu không khớp.';
    }

    if (empty($errors)) {
        try {
            $fallbackEmail = $email !== '' ? $email : ($username . '@example.local');
            $db->registerCustomer($fullname, $fallbackEmail, $phone, $address, $username, $password);

            if ($db->login($username, $password)) {
                $_SESSION['flash_message'] = 'Đăng ký thành công! Bạn đã được đăng nhập.';
                $_SESSION['flash_type'] = 'success';
                header('Location: index.php');
                exit;
            }

            $_SESSION['flash_message'] = 'Đăng ký thành công! Vui lòng đăng nhập.';
            $_SESSION['flash_type'] = 'success';
            header('Location: login.php');
            exit;
        } catch (mysqli_sql_exception $exception) {
            if ((int) $exception->getCode() === 1062) {
                $errors[] = 'Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.';
            } else {
                $errors[] = 'Không thể đăng ký: ' . $exception->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng ký tài khoản</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="style.css">
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
        <li><a href="login.php">Tài khoản</a></li>
      </ul>
    </nav>
    <div class="header-icons">
      <a href="cart.php" aria-label="Giỏ hàng"><i class="fa fa-shopping-cart"></i><span>0</span></a>
      <a href="#" aria-label="Yêu thích"><i class="fa fa-heart"></i><span>0</span></a>
    </div>
  </header>

  <main class="auth-hero" role="main">
    <div class="auth-card">
      <div class="auth-card__body">
        <h1 class="auth-title">Tạo tài khoản</h1>
        <?php if (!empty($errors)): ?>
          <div class="err auth-error">
            <ul>
              <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form class="auth-form auth-form--modern auth-form--register" method="post" autocomplete="off">
          <div class="auth-fields-row">
            <div class="auth-field">
              <label for="fullname">Họ và tên</label>
              <input type="text" id="fullname" name="fullname" required value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>">
            </div>
            <div class="auth-field">
              <label for="phone">Số điện thoại</label>
              <input type="tel" id="phone" name="phone" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
          </div>

          <div class="auth-field">
            <label for="address">Địa chỉ</label>
            <input type="text" id="address" name="address" required value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
          </div>

          <div class="auth-field">
            <label for="username">Tên đăng nhập</label>
            <input type="text" id="username" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
          </div>

          <div class="auth-fields-row">
            <div class="auth-field">
              <label for="password">Mật khẩu</label>
              <input type="password" id="password" name="password" required>
            </div>
            <div class="auth-field">
              <label for="confirm_password">Xác nhận mật khẩu</label>
              <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
          </div>

          <button class="btn auth-submit" type="submit">Đăng ký</button>
        </form>

        <p class="muted auth-switch">
          Đã có tài khoản?
          <a href="login.php">Đăng nhập</a>
        </p>
      </div>
    </div>
  </main>
  <footer>
    &copy; 2025 BookBuy
  </footer>
</body>
</html>
