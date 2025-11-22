<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/admin_db.php';

if (!isAdmin()) {
    if (!isLoggedIn()) {
        header('Location: admin_login.php');
    } else {
        header('Location: ../index.php');
    }
    exit;
}

$db = new AdminDB();

$madh = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($madh <= 0) {
    header('Location: donhang.php');
    exit;
}

// Flash messages
$flashMessage = $_SESSION['flash_message'] ?? null;
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Xử lý cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $trangthai = trim($_POST['trangthai'] ?? '');
    
    if ($trangthai !== '') {
        $updated = $db->updateOrderStatus($madh, $trangthai);
        if ($updated) {
            $_SESSION['flash_message'] = 'Đã cập nhật trạng thái đơn hàng.';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Không thể cập nhật trạng thái.';
            $_SESSION['flash_type'] = 'error';
        }
        header('Location: chitiet_donhang.php?id=' . $madh);
        exit;
    }
}

$order = $db->getOrderById($madh);
if (!$order) {
    $_SESSION['flash_message'] = 'Không tìm thấy đơn hàng.';
    $_SESSION['flash_type'] = 'error';
    header('Location: donhang.php');
    exit;
}

$orderDetails = $db->getOrderDetails($madh);

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
  <title>Chi tiết đơn hàng #<?= $madh ?></title>
  <link rel="stylesheet" href="../style.css">
  <style>
    .detail-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      margin-bottom: 2rem;
    }
    .detail-section {
      background: white;
      padding: 1.5rem;
      border-radius: 8px;
      border: 1px solid #e5e7eb;
    }
    .detail-section h3 {
      margin-top: 0;
      margin-bottom: 1rem;
      font-size: 1.125rem;
      color: #111827;
    }
    .detail-row {
      display: flex;
      justify-content: space-between;
      padding: 0.75rem 0;
      border-bottom: 1px solid #f3f4f6;
    }
    .detail-row:last-child {
      border-bottom: none;
    }
    .detail-label {
      color: #6b7280;
      font-weight: 500;
    }
    .detail-value {
      color: #111827;
      text-align: right;
    }
    .product-item {
      display: flex;
      gap: 1rem;
      padding: 1rem 0;
      border-bottom: 1px solid #f3f4f6;
    }
    .product-item:last-child {
      border-bottom: none;
    }
    .product-img {
      width: 60px;
      height: 80px;
      object-fit: cover;
      border-radius: 4px;
      border: 1px solid #e5e7eb;
    }
    .product-info {
      flex: 1;
    }
    .product-name {
      font-weight: 600;
      color: #111827;
      margin-bottom: 0.25rem;
    }
    .product-price {
      color: #6b7280;
      font-size: 0.875rem;
    }
    .status-form {
      display: flex;
      gap: 1rem;
      align-items: center;
      margin-top: 1rem;
    }
    .status-form select {
      flex: 1;
    }
  </style>
</head>
<body class="admin-app">

  <div class="sidebar">
    <h2>ADMIN</h2>
    <a href="admin.php">Quản lý Sách</a>
    <a href="theloai.php">Quản lý Thể loại</a>
    <a class="active" href="donhang.php">Quản lý Đơn hàng</a>
    <a href="phanhoi.php">Quản lý Phản hồi</a>
  </div>

  <div class="main">
    <div class="header">
      <h1>Chi tiết đơn hàng #<?= $madh ?></h1>
      <a class="btn-logout" href="donhang.php">← Quay lại</a>
    </div>

    <div class="content content-books">
      <?php if ($flashMessage): ?>
        <div class="flash <?= htmlspecialchars($flashType) ?>">
          <?= htmlspecialchars($flashMessage) ?>
        </div>
      <?php endif; ?>

      <div class="detail-grid">
        <!-- Thông tin khách hàng -->
        <div class="detail-section">
          <h3>Thông tin khách hàng</h3>
          <div class="detail-row">
            <span class="detail-label">Họ tên:</span>
            <span class="detail-value"><?= htmlspecialchars($order['TENKH'] ?? 'N/A') ?></span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Email:</span>
            <span class="detail-value"><?= htmlspecialchars($order['EMAIL'] ?? 'N/A') ?></span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Số điện thoại:</span>
            <span class="detail-value"><?= htmlspecialchars($order['SDT'] ?? 'N/A') ?></span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Địa chỉ:</span>
            <span class="detail-value"><?= htmlspecialchars($order['DIACHI'] ?? 'N/A') ?></span>
          </div>
        </div>

        <!-- Thông tin đơn hàng -->
        <div class="detail-section">
          <h3>Thông tin đơn hàng</h3>
          <div class="detail-row">
            <span class="detail-label">Mã đơn hàng:</span>
            <span class="detail-value"><strong>#<?= $madh ?></strong></span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Ngày đặt:</span>
            <span class="detail-value"><?= htmlspecialchars($order['NGAYDAT'] ?? '') ?></span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Trạng thái:</span>
            <span class="detail-value">
              <strong><?= htmlspecialchars($statusLabels[$order['TRANGTHAI']] ?? $order['TRANGTHAI']) ?></strong>
            </span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Tổng tiền:</span>
            <span class="detail-value">
              <strong style="color:#ef4444; font-size:1.125rem">
                <?= formatCurrency((float) $order['TONGTIEN']) ?>
              </strong>
            </span>
          </div>
          <?php if (!empty($order['GHICHU'])): ?>
          <div class="detail-row">
            <span class="detail-label">Ghi chú:</span>
            <span class="detail-value"><?= htmlspecialchars($order['GHICHU']) ?></span>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Cập nhật trạng thái -->
      <div class="card">
        <h3>Cập nhật trạng thái</h3>
        <form method="post" class="status-form">
          <select name="trangthai" required>
            <?php foreach ($statusLabels as $status => $label): ?>
              <option value="<?= htmlspecialchars($status) ?>" <?= $order['TRANGTHAI'] === $status ? 'selected' : '' ?>>
                <?= htmlspecialchars($label) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" name="update_status" class="btn">Cập nhật</button>
        </form>
      </div>

      <!-- Chi tiết sản phẩm -->
      <div class="card">
        <h3>Sản phẩm trong đơn hàng</h3>
        <?php if (empty($orderDetails)): ?>
          <p class="muted">Không có sản phẩm nào.</p>
        <?php else: ?>
          <?php foreach ($orderDetails as $item): ?>
            <div class="product-item">
              <img class="product-img" 
                   src="<?= htmlspecialchars($item['ANH'] ?? '/nhasach/img/placeholder.png') ?>" 
                   alt="<?= htmlspecialchars($item['TENSACH'] ?? '') ?>">
              <div class="product-info">
                <div class="product-name"><?= htmlspecialchars($item['TENSACH'] ?? 'N/A') ?></div>
                <div class="product-price">
                  <?= formatCurrency((float) $item['DONGIA']) ?> × <?= (int) $item['SOLUONG'] ?>
                </div>
              </div>
              <div style="text-align:right; font-weight:600; color:#111827">
                <?= formatCurrency((float) $item['THANHTIEN']) ?>
              </div>
            </div>
          <?php endforeach; ?>
          
          <div class="detail-row" style="margin-top:1rem; padding-top:1rem; border-top:2px solid #e5e7eb">
            <span class="detail-label" style="font-size:1.125rem"><strong>Tổng cộng:</strong></span>
            <span class="detail-value" style="font-size:1.25rem; color:#ef4444; font-weight:700">
              <?= formatCurrency((float) $order['TONGTIEN']) ?>
            </span>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</body>
</html>
