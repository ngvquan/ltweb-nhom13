<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/admin_db.php';

if (!isAdmin()) {
    header('Location: admin_login.php');
    exit;
}

$db = new AdminDB();
$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $tentl = trim($_POST['tentl'] ?? '');
        if ($tentl === '') {
            $errors[] = 'Vui lòng nhập tên thể loại.';
        } else {
            $ok = $db->addCategory($tentl);
            if ($ok) {
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Đã thêm thể loại: ' . $tentl];
                header('Location: theloai.php');
                exit;
            } else {
                $errors[] = 'Không thể thêm thể loại.';
            }
        }
    }

    if (isset($_POST['delete_category'])) {
        $matl = (int)($_POST['matl'] ?? 0);
        if ($matl > 0) {
            $ok = $db->deleteCategory($matl);
            if ($ok) {
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Đã xóa thể loại ID ' . $matl];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Không thể xóa thể loại.'];
            }
        }
        header('Location: theloai.php');
        exit;
    }
}

$categories = $db->getAllCategories();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản trị - Thể loại</title>
  <link rel="stylesheet" href="../style.css">
</head>
<body class="admin-app">

  <div class="sidebar">
    <h2>ADMIN</h2>
    <a href="admin.php">Quản lý Sách</a>
    <a href="donhang.php">Quản lý Đơn hàng</a>
    <a class="active" href="theloai.php">Quản lý Thể loại</a>
    <a href="phanhoi.php">Quản lý Phản hồi</a>
  </div>

  <div class="main">
    <div class="header">
      <h1>Quản lý thể loại</h1>
      <a class="btn-logout" href="../logout.php">Đăng xuất</a>
    </div>

    <div class="content">
      <?php if ($flash): ?>
        <div class="flash <?= htmlspecialchars($flash['type']) ?>">
          <?= htmlspecialchars($flash['msg']) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="flash error">
          <ul>
            <?php foreach ($errors as $err): ?>
              <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="card">
        <h3>Thêm thể loại</h3>
        <form method="post" class="add-form">
          <div class="form-group">
            <label for="tentl">Tên thể loại</label>
            <input type="text" id="tentl" name="tentl" required value="<?= htmlspecialchars($_POST['tentl'] ?? '') ?>">
          </div>
          <button type="submit" name="add_category" class="btn">Thêm</button>
        </form>
      </div>

      <div class="card">
        <h3>Danh sách thể loại</h3>
        <?php if (empty($categories)): ?>
          <p class="muted">Chưa có thể loại nào.</p>
        <?php else: ?>
          <div class="table-wrapper">
            <table class="category-table">
              <thead>
                <tr>
                  <th>Mã TL</th>
                  <th>Tên thể loại</th>
                  <th>Hành động</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($categories as $cat): ?>
                  <tr>
                    <td><?= (int)$cat['MATL'] ?></td>
                    <td><?= htmlspecialchars($cat['TENTL']) ?></td>
                    <td>
                      <div class="table-actions">
                        <a class="btn ghost" href="edit_theloai.php?id=<?= (int)$cat['MATL'] ?>">Sửa</a>
                        <form method="post" onsubmit="return confirm('Xóa thể loại này?');">
                          <input type="hidden" name="matl" value="<?= (int)$cat['MATL'] ?>">
                          <button type="submit" name="delete_category" class="btn danger">Xóa</button>
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
    </div>
  </div>

</body>
</html>

