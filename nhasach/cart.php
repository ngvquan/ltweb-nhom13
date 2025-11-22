<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: text/html; charset=UTF-8');

$db = new DB();

$isLoggedIn = isset($_SESSION['user_id']);
$isAdminSession = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';

if (!$isLoggedIn) {
    $_SESSION['flash_message'] = 'Vui lòng đăng nhập để xem giỏ hàng.';
    $_SESSION['flash_type'] = 'error';
    header('Location: login.php');
    exit;
}

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_item'])) {
        $bookId = (int) $_POST['remove_item'];
        if ($bookId > 0 && $db->removeCartItem($userId, $bookId)) {
            $_SESSION['flash_message'] = 'Đã xóa sách khỏi giỏ hàng.';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Không thể xóa sách khỏi giỏ hàng lúc này.';
            $_SESSION['flash_type'] = 'error';
        }
        header('Location: cart.php');
        exit;
    }

    if (isset($_POST['update_cart'], $_POST['quantity']) && is_array($_POST['quantity'])) {
        $updated = 0;
        foreach ($_POST['quantity'] as $rawId => $rawQuantity) {
            $bookId = (int) $rawId;
            if ($bookId <= 0) {
                continue;
            }
            $quantity = max(1, min(999, (int) $rawQuantity));
            if ($db->updateCartQuantity($userId, $bookId, $quantity)) {
                $updated++;
            }
        }

        if ($updated > 0) {
            $_SESSION['flash_message'] = 'Đã cập nhật giỏ hàng.';
            $_SESSION['flash_type'] = 'success';
        }

        header('Location: cart.php');
        exit;
    }
}

$flashMessage = $_SESSION['flash_message'] ?? null;
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

$cartItems = $db->getCart($userId);
$categories = $db->getBookCategories();
$cartCount = count($cartItems);
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += (float) $item['GIA'] * (int) $item['SOLUONG'];
}
$shipping = $subtotal > 0 ? 35000 : 0;
$total = $subtotal + $shipping;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - BookBuy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body.cart-page {
            margin: 0;
            background: #f8fafc;
            color: #111;
        }
        .banner {
            background: url('img/banner-cart.jpg') center/cover;
            min-height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            color: #fff;
            text-transform: uppercase;
        }
        main {
            max-width: 1080px;
            margin: 32px auto 60px;
            padding: 0 24px;
        }
        .cart-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }
        @media (max-width: 900px) {
            .cart-container { grid-template-columns: 1fr; }
        }
        .card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid #e5e7eb;
            padding: 24px;
            box-shadow: 0 12px 30px rgba(15,23,42,0.08);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table thead th {
            text-align: left;
            padding-bottom: 10px;
            font-size: 0.9rem;
            color: #6b7280;
        }
        table tbody td {
            padding: 14px 0;
            border-top: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        table tbody img {
            width: 70px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
        }
        .qty-input {
            width: 70px;
            padding: 6px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            text-align: center;
        }
        .remove-btn {
            background: transparent;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-size: 1rem;
        }
        .update-btn,
        .checkout-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            border-radius: 999px;
            padding: 8px 18px;
            font-size: 0.95rem;
            font-weight: 600;
            color: #fff;
            background: #22c55e;
            box-shadow: 0 6px 16px rgba(34, 197, 94, 0.35);
            cursor: pointer;
            text-decoration: none;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .update-btn:hover,
        .checkout-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(34, 197, 94, 0.45);
        }
        .notice {
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 18px;
        }
        .notice.success { background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
        .notice.error { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
        .summary-row {
            display:flex;
            justify-content:space-between;
            margin-bottom:12px;
            font-weight:600;
        }
    </style>
</head>
<body class="home cart-page">
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
            <a href="cart.php" aria-label="Giỏ hàng"><i class="fa fa-shopping-cart"></i><span><?= (int) $cartCount ?></span></a>
        </div>
    </header>

    <div class="banner">Giỏ hàng</div>

    <main>
        <div class="cart-container">
            <div class="card">
                <h1>Giỏ hàng của bạn</h1>

                <?php if ($flashMessage): ?>
                    <div class="notice <?= htmlspecialchars($flashType) ?>">
                        <?= htmlspecialchars($flashMessage) ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($cartItems)): ?>
                    <p>Giỏ hàng hiện đang trống. <a href="index.php">Tiếp tục chọn sách</a>.</p>
                <?php else: ?>
                    <form method="post">
                        <button type="submit" name="update_cart" value="1" class="update-btn">
                            <i class="fa fa-sync-alt"></i> Cập nhật giỏ hàng
                        </button>

                        <table>
                            <thead>
                                <tr>
                                    <th>Ảnh bìa</th>
                                    <th>Sách</th>
                                    <th>Giá</th>
                                    <th>Số lượng</th>
                                    <th>Thành tiền</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $item): ?>
                                    <?php
                                        $img = $item['ANH'] ?: 'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=800';
                                        $lineAmount = (float) $item['GIA'] * (int) $item['SOLUONG'];
                                    ?>
                                    <tr>
                                        <td><img src="<?= htmlspecialchars($img) ?>" alt=""></td>
                                        <td><?= htmlspecialchars($item['TENSACH']) ?></td>
                                        <td><?= htmlspecialchars(formatCurrency((float) $item['GIA'])) ?></td>
                                        <td>
                                            <input type="number"
                                                   name="quantity[<?= (int) $item['MASACH'] ?>]"
                                                   class="qty-input"
                                                   value="<?= (int) $item['SOLUONG'] ?>"
                                                   min="1">
                                        </td>
                                        <td><?= htmlspecialchars(formatCurrency($lineAmount)) ?></td>
                                        <td>
                                            <button type="submit"
                                                    name="remove_item"
                                                    value="<?= (int) $item['MASACH'] ?>"
                                                    class="remove-btn"
                                                    aria-label="Xóa khỏi giỏ hàng">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2>Chi tiết đơn hàng</h2>
                <div class="summary-row"><span>Tạm tính</span><span><?= htmlspecialchars(formatCurrency($subtotal)) ?></span></div>
                <div class="summary-row"><span>Phí vận chuyển</span><span><?= htmlspecialchars(formatCurrency($shipping)) ?></span></div>
                <hr>
                <div class="summary-row"><strong>Tổng cộng</strong><strong><?= htmlspecialchars(formatCurrency($total)) ?></strong></div>
                <a href="checkout.php" class="checkout-btn">Tiến hành thanh toán</a>
            </div>
        </div>
    </main>

    <footer>
        &copy; <?= date('Y') ?> BookBuy | Read More, Live More
    </footer>
</body>
</html>
