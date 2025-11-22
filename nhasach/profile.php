<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: text/html; charset=UTF-8');

if (!isLoggedIn()) {
    $_SESSION['flash_message'] = 'Vui lòng đăng nhập để truy cập trang tài khoản.';
    $_SESSION['flash_type'] = 'error';
    header('Location: login.php');
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new DB();
$userId = (int) ($_SESSION['user_id'] ?? 0);

try {
    $customer = $db->getCustomerById($userId);
} catch (mysqli_sql_exception $exception) {
    $customer = null;
}

if (!$customer) {
    $_SESSION['flash_message'] = 'Không thể tải hồ sơ của bạn. Vui lòng đăng nhập lại.';
    $_SESSION['flash_type'] = 'error';
    header('Location: logout.php');
    exit;
}

$displayName     = trim((string) ($customer['TENKH'] ?? ($_SESSION['tenkh'] ?? ($_SESSION['username'] ?? 'bạn'))));
$customerPhone   = trim((string) ($customer['SDT'] ?? ''));
$customerAddress = trim((string) ($customer['DIACHI'] ?? ''));
$customerEmail   = trim((string) ($customer['EMAIL'] ?? ''));

$navTabs = [
    'orders'  => 'ĐƠN HÀNG',
    'details' => 'THÔNG TIN TÀI KHOẢN',
];

$errors = [];
$tab = strtolower($_GET['tab'] ?? 'orders');
if (!array_key_exists($tab, $navTabs)) {
    $tab = 'orders';
}

$orders = [];
$orderHistoryWithItems = [];
if ($tab === 'orders') {
    try {
        $orders = $db->getCustomerOrders($userId, 8);
    } catch (Throwable $e) {
        $orders = [];
    }

    try {
        $orderHistory = $db->getCustomerOrderHistory($userId);
    } catch (Throwable $e) {
        $orderHistory = [];
    }

    foreach ($orderHistory as $historyEntry) {
        $details = $db->getOrderDetailsByCustomer($userId, (int) $historyEntry['MADH']);
        $historyEntry['items'] = $details['items'] ?? [];
        $orderHistoryWithItems[] = $historyEntry;
    }
}

$statusLabels = [
    'pending' => 'Chờ xử lý',
    'processing' => 'Đang xử lý',
    'shipping' => 'Đang giao',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã huỷ'
];
$statusBadgeClasses = [
    'pending' => 'history-badge--pending',
    'processing' => 'history-badge--processing',
    'shipping' => 'history-badge--shipping',
    'completed' => 'history-badge--completed',
    'cancelled' => 'history-badge--cancelled'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'details') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    $currentPass = (string)($_POST['current_password'] ?? '');
    $newPass     = (string)($_POST['new_password'] ?? '');
    $confirmPass = (string)($_POST['confirm_password'] ?? '');

    if ($fullName === '') { $errors[] = 'Vui lòng nhập Họ và tên.'; }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email không hợp lệ.'; }
    if ($phone !== '' && !preg_match('/^[0-9\s+\-]{6,20}$/', $phone)) { $errors[] = 'Số điện thoại không hợp lệ.'; }

    $wantsChangePassword = ($currentPass !== '' || $newPass !== '' || $confirmPass !== '');
    if ($wantsChangePassword) {
        if ($currentPass === '' || $newPass === '' || $confirmPass === '') {
            $errors[] = 'Vui lòng điền đủ các trường mật khẩu.';
        } elseif ($newPass !== $confirmPass) {
            $errors[] = 'Mật khẩu mới và xác nhận không khớp.';
        } elseif (strlen($newPass) < 6) {
            $errors[] = 'Mật khẩu mới phải từ 6 ký tự trở lên.';
        } elseif (!$db->verifyCustomerPassword($userId, $currentPass)) {
            $errors[] = 'Mật khẩu hiện tại không đúng.';
        }
    }

    if (empty($errors)) {
        try {
            $ok = $db->updateCustomerAccount($userId, $fullName, $email);
            $ok = $ok && $db->updateCustomerProfile($userId, $fullName, $phone, $address);
            if ($ok && $wantsChangePassword) { $ok = $db->updateCustomerPassword($userId, $newPass); }

            if ($ok) {
                $_SESSION['tenkh'] = $fullName;
                $_SESSION['flash_message'] = 'Đã lưu thay đổi tài khoản.';
                $_SESSION['flash_type'] = 'success';
                header('Location: profile.php?tab=details');
                exit;
            }
            $errors[] = 'Không thể lưu thay đổi ngay lúc này.';
        } catch (mysqli_sql_exception $exception) {
            $errors[] = 'Có lỗi khi cập nhật: ' . $exception->getMessage();
        }
    }

    $customer['TENKH']  = $fullName;
    $customer['EMAIL']  = $email;
    $customer['SDT']    = $phone;
    $customer['DIACHI'] = $address;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang tài khoản</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <?php $styleVersion = @filemtime(__DIR__ . '/style.css') ?: time(); ?>
    <link rel="stylesheet" href="style.css?v=<?= $styleVersion ?>">
</head>
<body class="home account-page">
    <header class="site-header">
        <div class="logo">
            <i class="fa-solid fa-book-open-reader" aria-hidden="true"></i>
            BookBuy
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Trang chủ</a></li>
            </ul>
        </nav>
        <div class="header-icons">
            <a href="cart.php" aria-label="Giỏ hàng"><i class="fa fa-shopping-cart"></i><span>0</span></a>
        </div>
    </header>

    <section class="account-hero" aria-hidden="true">
        <img src="https://images.unsplash.com/photo-1526304640581-d334cdbbf45e?w=1920" alt="Sách trưng bày">
    </section>

    <main class="account-container">
        <div class="account-heading">
            <h1 class="account-title">Trang tài khoản</h1>
            <span class="account-divider" aria-hidden="true"></span>
        </div>

        <?php if ($tab === 'details' && !empty($errors)): ?>
            <div class="err account-error">
                <?php foreach ($errors as $message): ?>
                    <div><?= htmlspecialchars($message) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="account-layout">
            <aside class="account-aside" aria-label="Điều hướng tài khoản">
                <nav>
                    <ul class="account-nav">
                        <?php foreach ($navTabs as $slug => $label): ?>
                            <li>
                                <a class="<?= $tab === $slug ? 'active' : '' ?>" href="profile.php?tab=<?= urlencode($slug) ?>">
                                    <?= $label ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li class="account-nav__logout">
                            <a href="logout.php">ĐĂNG XUẤT</a>
                        </li>
                    </ul>
                </nav>
            </aside>

            <section class="account-content">
                <?php if ($tab === 'orders'): ?>
                    <div class="account-card account-orders">
                        <div class="account-card__header">
                            <h2>Đơn hàng gần đây</h2>
                            <a class="account-link" href="index.php">Tiếp tục mua sắm</a>
                        </div>
                        <?php if (empty($orderHistoryWithItems)): ?>
                            <p class="account-empty">Bạn chưa có đơn hàng nào.</p>
                        <?php else: ?>
                            <div class="account-history-grid">
                                <?php foreach ($orderHistoryWithItems as $history): ?>
                                    <?php
                                        $statusKey = strtolower($history['TRANGTHAI'] ?? '');
                                        $statusLabel = $statusLabels[$statusKey] ?? ($history['TRANGTHAI'] ?? 'Chờ xử lý');
                                        $badgeClass = $statusBadgeClasses[$statusKey] ?? 'history-badge--default';
                                        $historyDate = $history['NGAYDAT'] ? date('d/m/Y H:i', strtotime($history['NGAYDAT'])) : '--';
                                    ?>
                                    <article class="history-card history-card--compact">
                                        <div class="history-card__header">
                                            <div>
                                                <div class="history-card__id">#<?= htmlspecialchars((string) $history['MADH']) ?></div>
                                                <div class="history-card__date"><?= htmlspecialchars($historyDate) ?></div>
                                            </div>
                                            <span class="history-badge <?= htmlspecialchars($badgeClass) ?>"><?= htmlspecialchars($statusLabel) ?></span>
                                        </div>
                                        <div class="history-card__items">
                                            <?php foreach ($history['items'] as $item): ?>
                                                <div class="history-item">
                                                    <div class="history-item__details">
                                                        <div class="history-item__title"><?= htmlspecialchars($item['TENSACH'] ?? 'Sách') ?></div>
                                                        <div class="history-item__meta">
                                                            <?= (int) $item['SOLUONG'] ?> × <?= htmlspecialchars(formatCurrency((float) $item['DONGIA'])) ?>
                                                        </div>
                                                    </div>
                                                    <div class="history-item__amount"><?= htmlspecialchars(formatCurrency((float) $item['THANHTIEN'])) ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="history-card__total">
                                            <span>Thành tiền</span>
                                            <strong><?= htmlspecialchars(formatCurrency((float) $history['TONGTIEN'])) ?></strong>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="account-card account-card--form">
                        <div class="account-card__header">
                            <h2>THÔNG TIN TÀI KHOẢN</h2>
                        </div>
                        <form class="auth-form" method="post" autocomplete="off">
                            <div class="form-group">
                                <label for="full_name">Họ và tên <span aria-hidden="true">*</span></label>
                                <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($customer['TENKH'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email <span aria-hidden="true">*</span></label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($customer['EMAIL'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Số điện thoại</label>
                                <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($customer['SDT'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="address">Địa chỉ</label>
                                <textarea id="address" name="address" rows="3"><?= htmlspecialchars($customer['DIACHI'] ?? '') ?></textarea>
                            </div>

                            <h3 class="account-subtitle">Thay đổi mật khẩu</h3>
                            <div class="form-group">
                                <label for="current_password">Mật khẩu hiện tại</label>
                                <input type="password" id="current_password" name="current_password" autocomplete="current-password">
                            </div>
                            <div class="form-group">
                                <label for="new_password">Mật khẩu mới</label>
                                <input type="password" id="new_password" name="new_password" autocomplete="new-password">
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Xác nhận mật khẩu mới</label>
                                <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">
                            </div>

                            <button class="btn" type="submit">Lưu thay đổi</button>
                        </form>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</body>
</html>
