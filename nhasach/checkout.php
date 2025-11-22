<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: text/html; charset=UTF-8');

$db = new DB();
$categories = [];
try {
    $categories = $db->getBookCategories();
} catch (Throwable $exception) {
    $categories = [];
}
$isLoggedIn = isset($_SESSION['user_id']);
$isAdminSession = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';

if (!isLoggedIn()) {
    $_SESSION['flash_message'] = 'Vui lòng đăng nhập trước khi thanh toán.';
    $_SESSION['flash_type'] = 'error';
    header('Location: login.php');
    exit;
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
$cartItems = $db->getCart($userId);
if (empty($cartItems)) {
    $_SESSION['flash_message'] = 'Giỏ hàng đang trống. Hãy chọn sách trước khi thanh toán.';
    $_SESSION['flash_type'] = 'error';
    header('Location: cart.php');
    exit;
}

$cartCount = count($cartItems);

$shippingOptions = [
    'standard' => [
        'label' => 'Giao tiêu chuẩn',
        'description' => 'Giao hàng toàn quốc trong 2-4 ngày làm việc.',
        'fee' => 35000,
    ],
];

$paymentMethods = [
    'cod' => [
        'label' => 'Thanh toán khi nhận hàng (COD)',
        'description' => 'Thanh toán bằng tiền mặt cho nhân viên giao hàng khi nhận đơn.',
    ],
];

$selectedShipping = $_POST['shipping_method'] ?? $_SESSION['checkout_shipping'] ?? 'standard';
if (!array_key_exists($selectedShipping, $shippingOptions)) {
    $selectedShipping = 'standard';
}
$_SESSION['checkout_shipping'] = $selectedShipping;

$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += (float) $item['GIA'] * (int) $item['SOLUONG'];
}
$shippingFee = $shippingOptions[$selectedShipping]['fee'];
$total = $subtotal + $shippingFee;

$errors = [];
$formData = [
    'first_name' => $_POST['first_name'] ?? '',
    'last_name' => $_POST['last_name'] ?? '',
    'phone' => $_POST['phone'] ?? '',
    'email' => $_POST['email'] ?? '',
    'city' => $_POST['city'] ?? '',
    'address' => $_POST['address'] ?? '',
    'notes' => $_POST['notes'] ?? '',
    'payment_method' => $_POST['payment_method'] ?? 'cod',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (trim($formData['first_name']) === '') { $errors[] = 'Vui lòng nhập Họ.'; }
    if (trim($formData['last_name']) === '') { $errors[] = 'Vui lòng nhập Tên.'; }
    if (trim($formData['phone']) === '') { $errors[] = 'Vui lòng nhập Số điện thoại.'; }
    if (trim($formData['email']) === '' || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email không hợp lệ.'; }
    if (trim($formData['city']) === '') { $errors[] = 'Vui lòng nhập Tỉnh/Thành phố.'; }
    if (trim($formData['address']) === '') { $errors[] = 'Vui lòng nhập địa chỉ giao hàng.'; }
    if (!array_key_exists($formData['payment_method'], $paymentMethods)) { $errors[] = 'Vui lòng chọn phương thức thanh toán.'; }

    if (empty($errors)) {
        $orderData = [
            'total' => $total,
            'address' => $formData['address'],
            'city' => $formData['city'],
            'notes' => $formData['notes'],
            'payment_method' => $formData['payment_method'],
            'shipping_method' => $selectedShipping,
        ];
        $orderId = $db->createOrder($userId, $orderData, $cartItems);
        if ($orderId) {
            $fullName = trim($formData['first_name']) . ' ' . trim($formData['last_name']);
            $db->updateCustomerProfile($userId, $fullName, $formData['phone'], $formData['address']);
            $_SESSION['flash_message'] = 'Đặt hàng thành công! Mã đơn #' . $orderId . '. BookBuy sẽ liên hệ xác nhận trong thời gian sớm nhất.';
            $_SESSION['flash_type'] = 'success';
            header('Location: order_success.php?id=' . $orderId);
            exit;
        }
        $errors[] = 'Không thể tạo đơn hàng. Vui lòng thử lại.';
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - BookBuy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="checkout-page">
    <header class="site-header">
        <div class="logo">
            <i class="fa-solid fa-book-open-reader" aria-hidden="true"></i>
            BookBuy
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Trang chủ</a></li>
                <li class="dropdown"><a href="index.php">Thể loại</a>
                    <ul class="dropdown-menu">
                        <?php foreach ($categories as $category): ?>
                            <li><a href="index.php?category=<?= urlencode($category) ?>"><?= htmlspecialchars($category) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li><a href="support.php">Hỗ trợ</a></li>
                <?php if ($isLoggedIn): ?>
                    <li><a href="profile.php">Tài khoản</a></li>
                <?php else: ?>
                    <li><a href="login.php">Đăng nhập</a></li>
                <?php endif; ?>
                <?php if ($isAdminSession): ?>
                    <li><a href="admin/theloai.php">Quản trị</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="header-icons">
            <a href="cart.php" aria-label="Giỏ hàng"><i class="fa fa-shopping-cart"></i><span><?= (int)$cartCount ?></span></a>
        </div>
    </header>

    <main>
        <div class="checkout-wrapper">
            <h1 class="checkout-title">Thanh toán</h1>
            <p class="checkout-subtitle">Hoàn tất đơn hàng để BookBuy giao sách đến bạn trong thời gian sớm nhất.</p>

            <?php if ($errors): ?>
                <div class="notice error">
                    <strong>Có chỗ chưa hợp lệ!</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form class="checkout-form" method="post" action="checkout.php">
                <div class="checkout-grid">
                    <div class="billing-card">
                        <h2>Thông tin giao hàng</h2>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="first_name">Họ *</label>
                                <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($formData['first_name']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="last_name">Tên *</label>
                                <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($formData['last_name']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="phone">Số điện thoại *</label>
                                <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($formData['phone']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="city">Tỉnh / Thành phố *</label>
                                <input type="text" id="city" name="city" value="<?= htmlspecialchars($formData['city']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="address">Địa chỉ *</label>
                                <input type="text" id="address" name="address" value="<?= htmlspecialchars($formData['address']) ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="notes">Ghi chú đơn hàng (tùy chọn)</label>
                            <textarea id="notes" name="notes" rows="3" placeholder="Ví dụ: Giao trước 10h, để lại bảo vệ etc."><?= htmlspecialchars($formData['notes']) ?></textarea>
                        </div>
                    </div>

                    <div class="summary-card">
                        <h2>Đơn hàng của bạn</h2>
                        <table class="order-table">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Tạm tính</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $item): ?>
                                    <?php $lineTotal = (float) $item['GIA'] * (int) $item['SOLUONG']; ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['TENSACH']) ?> × <?= (int) $item['SOLUONG'] ?></td>
                                        <td><?= htmlspecialchars(formatCurrency($lineTotal)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="summary-line">
                            <span>Tạm tính</span>
                            <span><?= htmlspecialchars(formatCurrency($subtotal)) ?></span>
                        </div>

                        <div class="shipping-options">
                            <span style="font-weight:600;">Phí vận chuyển</span>
                            <?php foreach ($shippingOptions as $key => $option): ?>
                                <label>
                                    <input type="radio" name="shipping_method" value="<?= $key ?>" <?= $key === $selectedShipping ? 'checked' : '' ?>>
                                    <div>
                                        <strong><?= htmlspecialchars($option['label']) ?> – <?= htmlspecialchars(formatCurrency($option['fee'])) ?></strong>
                                        <p><?= htmlspecialchars($option['description']) ?></p>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="summary-line">
                            <span>Tổng cộng</span>
                            <span><?= htmlspecialchars(formatCurrency($total)) ?></span>
                        </div>

                        <div class="payment-options">
                            <span style="font-weight:600;">Phương thức thanh toán</span>
                            <?php foreach ($paymentMethods as $key => $method): ?>
                                <label>
                                    <input type="radio" name="payment_method" value="<?= $key ?>" <?= $key === $formData['payment_method'] ? 'checked' : '' ?>>
                                    <div>
                                        <strong><?= htmlspecialchars($method['label']) ?></strong>
                                        <p><?= htmlspecialchars($method['description']) ?></p>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <button type="submit" name="place_order" value="1" class="btn-submit">
                            <i class="fa-solid fa-check"></i> Đặt hàng
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <footer>
        &copy; <?= date('Y') ?> BookBuy | Read More, Live More
    </footer>
</body>
</html>
