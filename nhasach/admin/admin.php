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
$allCategories = $db->getAllCategories();

// Flash messages
$flashMessage = $_SESSION['flash_message'] ?? null;
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

$errors = [];

// Load available images from img/ and admin/img
$imageChoices = [];
$dirs = [
    realpath(__DIR__ . '/../img') => 'img',
    realpath(__DIR__ . '/img') => 'admin/img',
];
foreach ($dirs as $absDir => $relPrefix) {
    if ($absDir && is_dir($absDir)) {
        foreach (glob($absDir . '/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) as $f) {
            $imageChoices[] = $relPrefix . '/' . basename($f);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_book'])) {
        $name = trim($_POST['tensach'] ?? '');
        $matl = (int) ($_POST['matl'] ?? 0);
        $priceInput = trim($_POST['gia'] ?? '');
        $author = trim($_POST['tacgia'] ?? '');
        $image = trim($_POST['anh'] ?? '');
        $imageSelect = trim($_POST['anh_select'] ?? '');
        if ($imageSelect !== '' && (strpos($imageSelect, 'img/') === 0 || strpos($imageSelect, 'admin/img/') === 0)) {
            $image = $imageSelect;
        }
        $description = trim($_POST['mota'] ?? '');

        if ($name === '') {
            $errors[] = 'Tên sách không được để trống.';
        }

        if ($matl <= 0) {
            $errors[] = 'Vui lòng chọn thể loại.';
        }

        if ($priceInput === '' || !is_numeric($priceInput) || (float) $priceInput <= 0) {
            $errors[] = 'Giá sách phải là số dương.';
        }

        if ($author === '') {
            $errors[] = 'Vui lòng nhập tên tác giả.';
        }

        if ($image === '') {
            $image = 'images/placeholder.png';
        }

        if (empty($errors)) {
            $ok = $db->addBook($name, $matl, (float) $priceInput, $image, $author, $description);
            if ($ok) {
                $_SESSION['flash_message'] = 'Đã thêm sách "' . $name . '".';
                $_SESSION['flash_type'] = 'success';
                header('Location: admin.php');
                exit;
            } else {
                $errors[] = 'Không thể thêm sách.';
            }
        }
    }

    if (isset($_POST['delete_book'])) {
        $deleteId = isset($_POST['delete_id']) ? (int) $_POST['delete_id'] : 0;
        if ($deleteId > 0) {
            $deleted = $db->deleteBook($deleteId);
            if ($deleted) {
                $_SESSION['flash_message'] = 'Đã xóa sách (ID: ' . $deleteId . ').';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = 'Không thể xóa sách hoặc sách không tồn tại.';
                $_SESSION['flash_type'] = 'error';
            }
            header('Location: admin.php');
            exit;
        }
    }
}

$books = $db->getAllBooks();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản trị - Sách</title>
  <link rel="stylesheet" href="../style.css">
  </head>
<body class="admin-app">

  <div class="sidebar">
    <h2>ADMIN</h2>
    <a class="active" href="admin.php">Quản lý Sách</a>
    <a href="donhang.php">Quản lý Đơn hàng</a>
    <a href="theloai.php">Quản lý Thể loại</a>
    <a href="phanhoi.php">Quản lý Phản hồi</a>
  </div>

  <div class="main">
    <div class="header">
      <h1>Quản lý sách</h1>
      <a class="btn-logout" href="../logout.php">Đăng xuất</a>
    </div>

    <div class="content content-books">
      <?php if ($flashMessage): ?>
        <div class="flash <?= htmlspecialchars($flashType) ?>">
          <?= htmlspecialchars($flashMessage) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="flash error">
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="card">
        <h3>Thêm sách mới</h3>
        <form method="post" class="add-form">
          <div class="form-group">
            <label for="tensach">Tên sách</label>
            <input type="text" id="tensach" name="tensach" required value="<?= htmlspecialchars($_POST['tensach'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label for="matl">Thể loại</label>
            <select id="matl" name="matl" required>
              <option value="">-- Chọn thể loại --</option>
              <?php foreach ($allCategories as $cat): ?>
                <option value="<?= (int) $cat['MATL'] ?>" <?= ((int)($_POST['matl'] ?? 0) === (int)$cat['MATL']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat['TENTL']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="tacgia">Tác giả</label>
            <input type="text" id="tacgia" name="tacgia" required value="<?= htmlspecialchars($_POST['tacgia'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label for="gia">Giá (VND)</label>
            <input type="number" id="gia" name="gia" min="0" step="1000" required value="<?= htmlspecialchars($_POST['gia'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label for="anh">Hình (đường dẫn)</label>
            <input type="text" id="anh" name="anh" placeholder="VD: images/sach.png" value="<?= htmlspecialchars($_POST['anh'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label for="anh_select">Chọn ảnh từ thư viện</label>
            <select id="anh_select" name="anh_select">
              <option value="">-- Chọn ảnh --</option>
              <?php foreach ($imageChoices as $relPath): ?>
                <option value="<?= htmlspecialchars($relPath) ?>" <?= (isset($_POST['anh_select']) && $_POST['anh_select'] === $relPath) ? 'selected' : '' ?>>
                  <?= htmlspecialchars(basename($relPath)) ?> (<?= htmlspecialchars($relPath) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Ảnh xem trước</label>
            <div>
              <img id="preview-img" alt="Xem trước ảnh" style="max-width:140px; max-height:180px; display:none; border:1px solid #e5e7eb; border-radius:8px; background:#fff" />
            </div>
          </div>

          <div class="form-group">
            <label for="mota">Mô tả</label>
            <textarea id="mota" name="mota" rows="4"><?= htmlspecialchars($_POST['mota'] ?? '') ?></textarea>
          </div>

          <button type="submit" name="add_book" class="btn">Thêm sách</button>
        </form>
      </div>

      <div class="card">
        <h3>Danh sách sách</h3>
        <?php if (empty($books)): ?>
          <p class="muted">Hiện chưa có sách nào.</p>
        <?php else: ?>
          <div class="table-wrapper">
            <table class="book-table">
              <thead>
                <tr>
                  <th>Mã </th>
                  <th>Tên sách</th>
                  <th>Thể loại</th>
                  <th>Tác giả</th>
                  <th>Giá</th>
                  <th>Hành động</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($books as $book): ?>
                  <tr>
                    <td><?= (int) $book['MASACH'] ?></td>
                    <td><?= htmlspecialchars($book['TENSACH']) ?></td>
                    <td><?= htmlspecialchars($book['LOAISACH'] ?? 'Đang cập nhật') ?></td>
                    <td><?= htmlspecialchars($book['TACGIA'] ?? 'Đang cập nhật') ?></td>
                    <td><?= htmlspecialchars(formatCurrency((float) $book['GIA'])) ?></td>
                    <td>
                      <div class="table-actions">
                        <a class="btn ghost" href="edit_sach.php?id=<?= (int) $book['MASACH'] ?>">Sửa</a>
                        <form method="post" onsubmit="return confirm('Bạn có chắc muốn xóa sách này?');">
                          <input type="hidden" name="delete_id" value="<?= (int) $book['MASACH'] ?>">
                          <button type="submit" name="delete_book" class="btn danger">Xóa</button>
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

<script>
(function(){
  var sel = document.getElementById('anh_select');
  var inp = document.getElementById('anh');
  var img = document.getElementById('preview-img');
  function computeSrc(p){
    if(!p) return '';
    if(/^https?:\/\//i.test(p)) return p;
    p = p.replace(/^\/+/, '');
    return '../' + p; // from /admin/* to project root
  }
  function updatePreview(){
    var p = (sel && sel.value) || (inp && inp.value) || '';
    if(inp && sel && sel.value) { inp.value = sel.value; }
    var src = computeSrc(p);
    if(src){ img.src = src; img.style.display = 'inline-block'; }
    else { img.removeAttribute('src'); img.style.display = 'none'; }
  }
  if(sel) sel.addEventListener('change', updatePreview);
  if(inp) inp.addEventListener('input', updatePreview);
  document.addEventListener('DOMContentLoaded', updatePreview);
  updatePreview();
})();
</script>
</body>
</html>
