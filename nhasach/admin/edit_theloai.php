<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/admin_db.php';

if (!isAdmin()) {
    header('Location: admin_login.php');
    exit;
}

$db = new AdminDB();
$matl = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($matl <= 0) {
    header('Location: theloai.php');
    exit;
}

$category = $db->getCategoryById($matl);
if (!$category) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Không tìm thấy thể loại.'];
    header('Location: theloai.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $tentl = trim($_POST['tentl'] ?? '');
    if ($tentl === '') {
        $errors[] = 'Vui lòng nhập tên thể loại.';
    } else {
        $ok = $db->updateCategory($matl, $tentl);
        if ($ok) {
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Đã cập nhật thể loại.'];
            header('Location: theloai.php');
            exit;
        } else {
            $errors[] = 'Không thể cập nhật thể loại.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa thể loại</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <nav class="top-nav">
        <div class="brand">Nhà Sách</div>
        <div class="nav-links">
            <a class="btn-link" href="theloai.php">Quay lại</a>
            <a class="btn-link" href="../logout.php">Đăng xuất</a>
        </div>
    </nav>

    <main class="container">
        <h1>Sửa thể loại</h1>

        <?php if (!empty($errors)): ?>
            <div class="flash error">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" class="add-form">
            <div class="form-group">
                <label for="tentl">Tên thể loại</label>
                <input type="text" id="tentl" name="tentl" required value="<?= htmlspecialchars($_POST['tentl'] ?? $category['TENTL'] ?? '') ?>">
            </div>
            <div class="form-actions">
                <button type="submit" name="update_category" class="btn">Cập nhật</button>
                <a class="btn ghost" href="theloai.php">Hủy</a>
            </div>
        </form>
    </main>
</body>
</html>
