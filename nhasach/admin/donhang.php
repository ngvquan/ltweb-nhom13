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

// Flash messages
$flashMessage = $_SESSION['flash_message'] ?? null;
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Xử lý các action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $madh = (int) ($_POST['madh'] ?? 0);
        $trangthai = trim($_POST['trangthai'] ?? '');
        
        if ($madh > 0 && $trangthai !== '') {
            $updated = $db->updateOrderStatus($madh, $trangthai);
            if ($updated) {
                $_SESSION['flash_message'] = 'Đã cập nhật trạng thái đơn hàng #' . $madh;
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = 'Không thể cập nhật trạng thái.';
                $_SESSION['flash_type'] = 'error';
            }
            header('Location: donhang.php');
            exit;
        }
    }
    
    if (isset($_POST['delete_order'])) {
        $madh = (int) ($_POST['delete_id'] ?? 0);
        if ($madh > 0) {
            $deleted = $db->deleteOrder($madh);
            if ($deleted) {
                $_SESSION['flash_message'] = 'Đã xóa đơn hàng #' . $madh;
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = 'Không thể xóa đơn hàng.';
                $_SESSION['flash_type'] = 'error';
            }
            header('Location: donhang.php');
            exit;
        }
    }
}

// Lấy danh sách đơn hàng
$orders = $db->getAllOrders();
$stats = $db->getOrderStatistics();

// Mapping trạng thái
$statusLabels = [
    'pending' => 'Chờ xử lý',
    'processing' => 'Đang xử lý',
    'shipping' => 'Đang giao',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy'
];

$statusColors = [
    'pending' => '#f59e0b',
    'processing' => '#3b82f6',
    'shipping' => '#8b5cf6',
    'completed' => '#10b981',
    'cancelled' => '#ef4444'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản trị - Đơn hàng</title>
  <link rel="stylesheet" href="../style.css">
  <style>
    .orders-layout {
      display: grid;
      grid-template-columns: minmax(220px, 280px) minmax(0, 1fr);
      gap: 1.5rem;
      align-items: flex-start;
      grid-column: 1 / -1;
      width: 100%;
    }
    .stats-panel {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 0.85rem;
      width: 100%;
    }
    .stat-card {
      background: white;
      padding: 1.5rem;
      border-radius: 8px;
      border: 1px solid #e5e7eb;
    }
    .stat-card h4 {
      font-size: 0.875rem;
      color: #6b7280;
      margin: 0 0 0.5rem 0;
    }
    .stat-card .stat-value {
      font-size: 1.5rem;
      font-weight: 600;
      color: #111827;
    }
    .stat-card .stat-money {
      font-size: 0.875rem;
      color: #6b7280;
      margin-top: 0.25rem;
    }
    .status-badge {
      display: inline-block;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.875rem;
      font-weight: 500;
      color: white;
    }
    .order-details-btn {
      font-size: 0.875rem;
      padding: 0.375rem 0.75rem;
    }
    .orders-panel {
      width: 100%;
    }
    .orders-panel .card {
      min-height: 520px;
    }
    .orders-panel .table-wrapper {
      min-height: 460px;
    }
    .order-date {
      font-size: 0.85rem;
      color: #374151;
      letter-spacing: 0.01em;
    }
    @media (max-width: 1100px) {
      .orders-layout {
        grid-template-columns: 1fr;
      }
      .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      }
    }
  </style>
</head>
<body class="admin-app">

  <div class="sidebar">
    <h2>ADMIN</h2>
    <a href="admin.php">Quản lý Sách</a>
    <a class="active" href="donhang.php">Quản lý Đơn hàng</a>
    <a href="theloai.php">Quản lý Thể loại</a>
    <a href="phanhoi.php">Quản lý Phản hồi</a>
  </div>

  <div class="main">
    <div class="header">
      <h1>Quản lý đơn hàng</h1>
      <a class="btn-logout" href="../logout.php">Đăng xuất</a>
    </div>

    <div class="content content-books">
      <?php if ($flashMessage): ?>
        <div class="flash <?= htmlspecialchars($flashType) ?>">
          <?= htmlspecialchars($flashMessage) ?>
        </div>
      <?php endif; ?>

      <!-- Thống kê -->
      <div class="orders-layout">
        <section class="stats-panel">
          <div class="stats-grid">
            <?php foreach ($statusLabels as $status => $label): ?>
              <?php 
                $count = $stats[$status]['count'] ?? 0;
                $total = $stats[$status]['total'] ?? 0;
              ?>
              <div class="stat-card">
                <h4><?= htmlspecialchars($label) ?></h4>
                <div class="stat-value"><?= $count ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </section>

        <section class="orders-panel">
          <div class="card">
            <h3>Danh sách đơn hàng</h3>
            <?php if (empty($orders)): ?>
              <p class="muted">Hiện chưa có đơn hàng nào.</p>
            <?php else: ?>
              <div class="table-wrapper">
                <table class="book-table">
                  <thead>
                    <tr>
                      <th>Mã</th>
                      <th>Khách hàng</th>
                      <th>Ngày đặt</th>
                      <th>Tổng tiền</th>
                      <th>Trạng thái</th>
                      <th>Hành động</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($orders as $order): ?>
                      <tr>
                        <td><strong>#<?= (int) $order['MADH'] ?></strong></td>
                        <td>
                          <?= htmlspecialchars($order['TENKH'] ?? 'N/A') ?><br>
                          <small style="color:#6b7280"><?= htmlspecialchars($order['EMAIL'] ?? '') ?></small>
                        </td>
                        <td class="order-date"><?= htmlspecialchars($order['NGAYDAT'] ?? '') ?></td>
                        <td><strong><?= formatCurrency((float) $order['TONGTIEN']) ?></strong></td>
                        <td>
                          <span class="status-badge" style="background-color: <?= $statusColors[$order['TRANGTHAI']] ?? '#6b7280' ?>">
                            <?= htmlspecialchars($statusLabels[$order['TRANGTHAI']] ?? $order['TRANGTHAI']) ?>
                          </span>
                        </td>
                        <td>
                          <div class="table-actions">
                            <a class="btn ghost order-details-btn" href="chitiet_donhang.php?id=<?= (int) $order['MADH'] ?>">Chi tiết</a>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa đơn hàng này?');">
                              <input type="hidden" name="delete_id" value="<?= (int) $order['MADH'] ?>">
                              <button type="submit" name="delete_order" class="btn danger">Xóa</button>
                            </form>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </section>
      </div>
    </div>
  </div>

</body>
</html>
