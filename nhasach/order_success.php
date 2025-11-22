<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: text/html; charset=UTF-8');

$db = new DB();

$isLoggedIn = isset($_SESSION['user_id']);
$isAdminSession = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';

if (!$isLoggedIn) {
    header('Location: login.php');
    exit;
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
$orderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($orderId <= 0) {
    header('Location: index.php');
    exit;
}

// Lấy thông tin đơn hàng
$order = $db->getOrderDetailsByCustomer($userId, $orderId);

if (!$order) {
    $_SESSION['flash_message'] = 'Không tìm thấy đơn hàng.';
    $_SESSION['flash_type'] = 'error';
    header('Location: index.php');
    exit;
}

$cartItems = $db->getCart($userId);
$cartCount = count($cartItems);

$statusLabels = [
    'pending' => 'Chờ xử lý',
    'processing' => 'Đang xử lý',
    'shipping' => 'Đang giao',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đặt hàng thành công - BookBuy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            background: #f9fafb;
            color: #0f172a;
        }
        main {
            flex: 1;
            padding: 48px 0 80px;
        }
        .success-wrapper {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 32px;
        }
        .success-card {
            background: white;
            border-radius: 18px;
            padding: 48px;
            box-shadow: 0 10px 30px rgba(15,23,42,0.08);
            text-align: center;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: #dcfce7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        .success-icon i {
            font-size: 40px;
            color: #16a34a;
        }
        .success-title {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #16a34a;
        }
        .success-subtitle {
            font-size: 18px;
            color: #64748b;
            margin-bottom: 32px;
        }
        .order-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 24px;
            margin: 32px 0;
            text-align: left;
        }
        .order-info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .order-info-row:last-child {
            border-bottom: none;
        }
        .order-info-label {
            color: #64748b;
            font-weight: 500;
        }
        .order-info-value {
            color: #0f172a;
            font-weight: 600;
        }
        .order-items {
            margin: 32px 0;
            text-align: left;
        }
        .order-items h3 {
            font-size: 20px;
            margin-bottom: 16px;
        }
        .order-item {
            display: flex;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .order-item-img {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .order-item-info {
            flex: 1;
        }
        .order-item-name {
            font-weight: 600;
            margin-bottom: 4px;
        }
        .order-item-price {
            color: #64748b;
            font-size: 14px;
        }
        .order-item-total {
            font-weight: 600;
            color: #0f172a;
        }
        .action-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-top: 32px;
        }
        .btn {
            padding: 12px 32px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #16a34a;
            color: white;
        }
        .btn-primary:hover {
            background: #15803d;
        }
        .btn-secondary {
            background: white;
            color: #0f172a;
            border: 2px solid #e5e7eb;
        }
        .btn-secondary:hover {
            background: #f9fafb;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            background: #fef3c7;
            color: #92400e;
        }
        footer {
            background: #fff;
            border-top: 1px solid rgba(0,0,0,0.08);
            text-align: center;
            padding: 24px 0;
            color: #64748b;
        }
    </style>
</head>
<body class="home">

<header class="site-header">
    <div class="logo">
        <i class="fa-solid fa-book-open-reader" aria-hidden="true"></i>
        BookBuy
    </div>
    <nav>
        <ul>
            <li><a href="#gioi-thieu">Giới thiệu</a></li>
            <li><a href="index.php">Tất cả sách</a></li>
            <li><a href="support.php">Liên hệ</a></li>
            <?php if ($isLoggedIn): ?>
                <li><a href="profile.php">Tài khoản</a></li>
                <li><a href="logout.php">Đăng xuất</a></li>
            <?php else: ?>
                <li><a href="login.php">Đăng nhập</a></li>
            <?php endif; ?>
            <?php if ($isAdminSession): ?>
                <li><a href="admin/theloai.php">Quản trị</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="header-icons">
        <a href="cart.php" aria-label="Giỏ hàng"><i class="fa fa-shopping-cart"></i><span><?= $cartCount ?></span></a>
        <a href="#" aria-label="Yêu thích"><i class="fa fa-heart"></i><span>0</span></a>
    </div>
</header>

<main>
    <div class="success-wrapper">
        <div class="success-card">
            <div class="success-icon">
                <i class="fa-solid fa-check"></i>
            </div>
            <h1 class="success-title">Đặt hàng thành công!</h1>
            <p class="success-subtitle">
                Cảm ơn bạn đã tin tưởng BookBuy. Chúng tôi sẽ liên hệ xác nhận và giao hàng trong thời gian sớm nhất.
            </p>

            <div class="order-info">
                <div class="order-info-row">
                    <span class="order-info-label">Mã đơn hàng:</span>
                    <span class="order-info-value">#<?= $orderId ?></span>
                </div>
                <div class="order-info-row">
                    <span class="order-info-label">Ngày đặt:</span>
                    <span class="order-info-value"><?= date('d/m/Y H:i', strtotime($order['NGAYDAT'])) ?></span>
                </div>
                <div class="order-info-row">
                    <span class="order-info-label">Trạng thái:</span>
                    <span class="status-badge"><?= htmlspecialchars($statusLabels[$order['TRANGTHAI']] ?? $order['TRANGTHAI']) ?></span>
                </div>
                <div class="order-info-row">
                    <span class="order-info-label">Địa chỉ giao hàng:</span>
                    <span class="order-info-value"><?= htmlspecialchars($order['DIACHI']) ?></span>
                </div>
                <div class="order-info-row">
                    <span class="order-info-label">Tổng tiền:</span>
                    <span class="order-info-value" style="color:#ef4444; font-size:20px">
                        <?= formatCurrency((float) $order['TONGTIEN']) ?>
                    </span>
                </div>
            </div>

            <?php if (!empty($order['items'])): ?>
            <div class="order-items">
                <h3>Sản phẩm đã đặt</h3>
                <?php foreach ($order['items'] as $item): ?>
                <div class="order-item">
                    <img class="order-item-img" 
                         src="<?= htmlspecialchars($item['ANH'] ?? 'images/placeholder.png') ?>" 
                         alt="<?= htmlspecialchars($item['TENSACH']) ?>">
                    <div class="order-item-info">
                        <div class="order-item-name"><?= htmlspecialchars($item['TENSACH']) ?></div>
                        <div class="order-item-price">
                            <?= formatCurrency((float) $item['DONGIA']) ?> × <?= (int) $item['SOLUONG'] ?>
                        </div>
                    </div>
                    <div class="order-item-total">
                        <?= formatCurrency((float) $item['THANHTIEN']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="action-buttons">
                <a href="index.php" class="btn btn-primary">
                    <i class="fa-solid fa-home"></i> Tiếp tục mua sắm
                </a>
                <a href="profile.php" class="btn btn-secondary">
                    <i class="fa-solid fa-receipt"></i> Xem đơn hàng
                </a>
            </div>
        </div>
    </div>
</main>

<footer>
    &copy; 2025 BookBuy | Read More, Live More
</footer>

</body>
</html>