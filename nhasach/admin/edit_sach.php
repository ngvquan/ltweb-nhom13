<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/admin_db.php';

if (!isAdmin()) {
    header('Location: admin_login.php');
    exit;
}

$db = new DB();
$conn = $db->getConnection();
$allCategories = $db->getAllCategories();


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

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$currentBook = null;
$flashMessage = $_SESSION['flash_message'] ?? '';
$flashSuccess = !empty($_SESSION['flash_success']);
unset($_SESSION['flash_message'], $_SESSION['flash_success']);

if ($id > 0 && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare('SELECT * FROM SACH WHERE MASACH = ?');
    if ($stmt) {
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $currentBook = $result ? ($result->fetch_assoc() ?: null) : null;
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['capnhat'])) {
    $tensach = trim($_POST['tensach'] ?? '');
    $matl = (int) ($_POST['matl'] ?? 0);
    $gia = trim($_POST['gia'] ?? '0');
    $tacgia = trim($_POST['tacgia'] ?? '');
    $mota = trim($_POST['mota'] ?? '');
    $anhSelect = trim($_POST['anh_select'] ?? '');
    $anhPath = '';
    if ($anhSelect !== '' && (strpos($anhSelect, 'img/') === 0 || strpos($anhSelect, 'admin/img/') === 0)) {
        $anhPath = $anhSelect;
    }

    $errors = [];

    if ($id <= 0) {
        $errors[] = 'Mã sách không hợp lệ.';
    }
    if ($tensach === '') {
        $errors[] = 'Tên sách không được để trống.';
    }
    if ($gia === '' || !is_numeric($gia) || (float) $gia < 0) {
        $errors[] = 'Giá phải là số không âm.';
    }
    if ($matl <= 0) {
        $errors[] = 'Vui lòng chọn thể loại.';
    }

    if (!empty($errors)) {
        $_SESSION['flash_message'] = implode("\n", $errors);
        $_SESSION['flash_success'] = false;
        $_SESSION['old_inputs'] = [
            'tensach' => $tensach,
            'matl' => $matl,
            'gia' => $gia,
            'tacgia' => $tacgia,
            'mota' => $mota,
        ];
        header('Location: edit_sach.php?id=' . urlencode($id));
        exit;
    }

    $price = (float) $gia;

    if ($anhPath !== '') {
        $stmt = $conn->prepare('UPDATE SACH SET TENSACH = ?, MATL = ?, GIA = ?, TACGIA = ?, MOTA = ?, ANH = ? WHERE MASACH = ?');
    } else {
        $stmt = $conn->prepare('UPDATE SACH SET TENSACH = ?, MATL = ?, GIA = ?, TACGIA = ?, MOTA = ? WHERE MASACH = ?');
    }
    if ($stmt) {
        if ($anhPath !== '') {
            $stmt->bind_param('sidsssi', $tensach, $matl, $price, $tacgia, $mota, $anhPath, $id);
        } else {
            $stmt->bind_param('sidssi', $tensach, $matl, $price, $tacgia, $mota, $id);
        }
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = 'Cập nhật thành công!';
            $_SESSION['flash_success'] = true;
            unset($_SESSION['old_inputs']);
        } else {
            $_SESSION['flash_message'] = 'Lỗi khi cập nhật: ' . $stmt->error;
            $_SESSION['flash_success'] = false;
        }
        $stmt->close();
    } else {
        $_SESSION['flash_message'] = 'Lỗi chuẩn bị câu lệnh cập nhật.';
        $_SESSION['flash_success'] = false;
    }

    header('Location: edit_sach.php?id=' . urlencode($id));
    exit;
}

$oldInputs = $_SESSION['old_inputs'] ?? null;
if (is_array($oldInputs)) {
    $currentBook = array_merge($currentBook ?? [], $oldInputs);
    unset($_SESSION['old_inputs']);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="../style.css">
    <title>Chỉnh sửa thông tin sách</title>
</head>
<body>
    <nav class="top-nav">
        <div class="brand">Nhà Sách</div>
        <div class="nav-links">
            <a class="btn-link" href="admin.php">Quản trị</a>
            <a class="btn-link" href="../logout.php">Đăng xuất</a>
        </div>
    </nav>

    <main class="container">
        <h1>Chỉnh sửa thông tin sách</h1>

        <?php if ($flashMessage !== ''): ?>
            <div class="flash <?= $flashSuccess ? 'success' : 'error' ?>">
                <?= nl2br(htmlspecialchars($flashMessage)) ?>
            </div>
        <?php endif; ?>

        <?php if (!$currentBook): ?>
            <p class="muted">Không tìm thấy sách với mã được cung cấp.</p>
        <?php else: ?>
            <form method="post" action="edit_sach.php?id=<?= htmlspecialchars((string) $id) ?>" class="add-form">
                <label for="tensach">Tên sách</label>
                <input id="tensach" type="text" name="tensach" maxlength="160" value="<?= htmlspecialchars($currentBook['tensach'] ?? $currentBook['TENSACH'] ?? '') ?>">

                <label for="matl">Thể loại</label>
                <select id="matl" name="matl" required>
                    <option value="">-- Chọn thể loại --</option>
                    <?php foreach ($allCategories as $cat): ?>
                        <option value="<?= (int)$cat['MATL'] ?>" <?= ((int)($currentBook['matl'] ?? $currentBook['MATL'] ?? 0) === (int)$cat['MATL']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['TENTL']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="gia">Giá</label>
                <input id="gia" type="number" step="0.01" min="0" name="gia" value="<?= htmlspecialchars($currentBook['gia'] ?? $currentBook['GIA'] ?? 0) ?>">

                <label for="tacgia">Tác giả</label>
                <input id="tacgia" type="text" name="tacgia" maxlength="160" value="<?= htmlspecialchars($currentBook['tacgia'] ?? $currentBook['TACGIA'] ?? '') ?>">

                <label for="anh_select">Chọn ảnh từ thư viện</label>
                <select id="anh_select" name="anh_select">
                    <option value="">-- Chọn ảnh --</option>
                    <?php foreach ($imageChoices as $relPath): ?>
                        <?php $isSel = (isset($currentBook['ANH']) && (string)$currentBook['ANH'] === $relPath); ?>
                        <option value="<?= htmlspecialchars($relPath) ?>" <?= $isSel ? 'selected' : '' ?>>
                            <?= htmlspecialchars(basename($relPath)) ?> (<?= htmlspecialchars($relPath) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="mota">Mô tả</label>
                <textarea id="mota" name="mota" maxlength="10000" rows="6"><?= htmlspecialchars($currentBook['mota'] ?? $currentBook['MOTA'] ?? '') ?></textarea>

                <div class="form-actions">
                    <button type="submit" name="capnhat" class="btn primary">Cập nhật</button>
                    <a class="btn ghost" href="admin.php">Hủy</a>
                </div>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>

<script>
(function(){
  var sel = document.getElementById('anh_select');
  var img = document.createElement('img');
  img.id = 'preview-img';
  img.alt = 'Xem trước ảnh';
  img.style.cssText = 'max-width:140px; max-height:180px; display:none; border:1px solid #e5e7eb; border-radius:8px; background:#fff; margin-top:8px';
  if (sel && sel.parentElement) sel.parentElement.appendChild(img);
  function computeSrc(p){
    if(!p) return '';
    if(/^https?:\/\//i.test(p)) return p;
    p = p.replace(/^\/+/, '');
    return '../' + p; // from /admin/* to project root
  }
  function updatePreview(){
    var p = sel && sel.value || '';
    var src = computeSrc(p);
    if(src){ img.src = src; img.style.display = 'inline-block'; }
    else { img.removeAttribute('src'); img.style.display = 'none'; }
  }
  if(sel){ sel.addEventListener('change', updatePreview); }
  document.addEventListener('DOMContentLoaded', updatePreview);
  updatePreview();
})();
</script>
