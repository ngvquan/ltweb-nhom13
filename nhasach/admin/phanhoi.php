<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/admin_db.php';

if (!isAdmin()) {
    header('Location: admin_login.php');
    exit;
}

$db = new AdminDB();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Xử lý trả lời
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_request'])) {
    $id = (int)($_POST['id'] ?? 0);
    $reply = trim($_POST['reply'] ?? '');

    if ($id > 0 && $reply !== '') {
        $ok = $db->replySupportRequest($id, $reply);
        $_SESSION['flash'] = $ok
            ? ['type' => 'success', 'msg' => 'Đã gửi phản hồi thành công.']
            : ['type' => 'error', 'msg' => 'Không thể gửi phản hồi.'];
    }

    header('Location: phanhoi.php');
    exit;
}

$requests = $db->getAllSupportRequests();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản trị - Phản hồi</title>
  <link rel="stylesheet" href="../style.css">

<style>
/* Modal */
#detailModal {
    display:none;
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    background:rgba(0,0,0,0.45);
    justify-content:center;
    align-items:center;
    z-index:9999;
}

.modal-box {
    background:#fff;
    padding:25px;
    width:600px;
    max-height:90vh;
    overflow-y:auto;
    border-radius:10px;
}

.modal-box textarea {
    width:100%;
    padding:10px;
    border:1px solid #ccc;
    border-radius:6px;
    margin-top:10px;
}

</style>

</head>
<body class="admin-app">

  <div class="sidebar">
    <h2>ADMIN</h2>
    <a href="admin.php">Quản lý Sách</a>
    <a href="donhang.php">Quản lý Đơn hàng</a>
    <a href="theloai.php">Quản lý Thể loại</a>
    <a class="active" href="phanhoi.php">Quản lý Phản hồi</a>
  </div>

  <div class="main">
    <div class="header">
      <h1>Quản lý phản hồi khách hàng</h1>
      <a class="btn-logout" href="../logout.php">Đăng xuất</a>
    </div>

    <div class="content">

      <?php if ($flash): ?>
        <div class="flash <?= htmlspecialchars($flash['type']) ?>">
          <?= htmlspecialchars($flash['msg']) ?>
        </div>
      <?php endif; ?>

      <div class="card">
        <h3>Danh sách yêu cầu hỗ trợ</h3>

        <?php if (empty($requests)): ?>
            <p class="muted">Hiện chưa có yêu cầu hỗ trợ nào.</p>
        <?php else: ?>

        <div class="table-wrapper">
          <table class="category-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Khách hàng</th>
                <th>Hành động</th>
              </tr>
            </thead>

            <tbody>
              <?php foreach ($requests as $req): ?>
              <tr>
                <td><?= (int)$req['id'] ?></td>
                <td><?= htmlspecialchars($req['name']) ?></td>
                <td>
                  <button class="btn ghost"
                    onclick='showDetail(<?= json_encode($req, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                    Xem chi tiết
                  </button>
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

<!-- Modal Chi tiết -->
<div id="detailModal">
    <div class="modal-box">

        <h3 style="margin-bottom:12px;">Chi tiết yêu cầu</h3>

        <p><strong>Họ tên:</strong> <span id="d_name"></span></p>
        <p><strong>Email:</strong> <span id="d_email"></span></p>
        <p><strong>Chủ đề:</strong> <span id="d_topic"></span></p>
        <p><strong>Mã đơn:</strong> <span id="d_order"></span></p>
        <p><strong>Nội dung:</strong><br> <span id="d_message"></span></p>
        <p><strong>Phản hồi:</strong><br> <span id="d_reply"></span></p>
        <p><strong>Ngày gửi:</strong> <span id="d_date"></span></p>

        <hr style="margin:20px 0;">

        <form method="post">
            <input type="hidden" name="id" id="replyId">
            <textarea name="reply" rows="4" placeholder="Nhập phản hồi..." required></textarea>
            <br><br>
            <button class="btn" type="submit" name="reply_request">Gửi phản hồi</button>
            <button type="button" class="btn danger" onclick="closeDetail()">Đóng</button>
        </form>
    </div>
</div>

<script>
function showDetail(data) {
    document.getElementById('d_name').innerText = data.name;
    document.getElementById('d_email').innerText = data.email;
    document.getElementById('d_topic').innerText = data.topic;
    document.getElementById('d_order').innerText = data.order_code || '-';
    document.getElementById('d_message').innerText = data.message;
    document.getElementById('d_reply').innerText = data.reply || '(Chưa có phản hồi)';
    document.getElementById('d_date').innerText = data.created_at;

    document.getElementById('replyId').value = data.id;

    document.getElementById('detailModal').style.display = 'flex';
}

function closeDetail() {
    document.getElementById('detailModal').style.display = 'none';
}
</script>

</body>
</html>
