<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/admin/admin_db.php'; 
$supportDb = new AdminDB();
$siteDb = new DB();

$isLoggedIn = isLoggedIn();
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header('Location: admin/theloai.php');
    exit;
}
$categories = [];
try {
    $categories = $siteDb->getBookCategories();
} catch (Throwable $exception) {
    $categories = [];
}

$supportTopics = [
    'general' => 'Hỗ trợ chung',
    'order' => 'Tình trạng đơn hàng',
    'payment' => 'Thanh toán & hoàn tiền',
    'shipping' => 'Vận chuyển & giao hàng',
    'account' => 'Tài khoản BookBuy',
];

$formData = [
    'name' => '',
    'email' => '',
    'order' => '',
    'topic' => 'general',
    'message' => '',
];

/* =====================================================
   LẤY LỊCH SỬ HỖ TRỢ CỦA NGƯỜI DÙNG
   ===================================================== */
$userHistory = [];
if ($isLoggedIn) {
    $uid = (int)($_SESSION['user_id'] ?? 0);
    if ($uid > 0) {
        $userHistory = $supportDb->getUserSupportHistory($uid);
    }
}

$feedbackMessages = [];
$feedbackType = 'info';

/* =====================================================
   XỬ LÝ GỬI YÊU CẦU
   ===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name'] = trim((string) ($_POST['name'] ?? ''));
    $formData['email'] = trim((string) ($_POST['email'] ?? ''));
    $formData['message'] = trim((string) ($_POST['message'] ?? ''));

    $errors = [];

    if ($formData['name'] === '') {
        $errors[] = 'Vui lòng nhập họ tên.';
    }

    if ($formData['email'] === '' || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }

    if ($formData['message'] === '') {
        $errors[] = 'Vui lòng mô tả vấn đề bạn gặp phải.';
    }

    if ($formData['order'] !== '' && !preg_match('/^[A-Za-z0-9-]{4,30}$/', $formData['order'])) {
        $errors[] = 'Mã đơn hàng chỉ bao gồm chữ, số hoặc dấu gạch ngang.';
    }

    if (!array_key_exists($formData['topic'], $supportTopics)) {
        $formData['topic'] = 'general';
    }

    if (empty($errors)) {

        $ok = $supportDb->addSupportRequest(
            $formData['name'],
            $formData['email'],
            $formData['order'],
            $formData['topic'],
            $formData['message']
        );

        if (!$ok) {
            $feedbackType = 'error';
            $feedbackMessages[] = $isLoggedIn ? 'Không thể lưu yêu cầu. Vui lòng thử lại.' : 'Vui lòng đăng nhập để gửi yêu cầu hỗ trợ.';
        } else {
            $feedbackType = 'success';
            $feedbackMessages[] = 'Cảm ơn bạn! BookBuy đã ghi nhận yêu cầu và sẽ phản hồi qua email trong vòng 24 giờ làm việc.';


            $formData = [
                'name' => '',
                'email' => '',
                'order' => '',
                'topic' => 'general',
                'message' => '',
            ];
        }
    } else {
        $feedbackType = 'error';
        $feedbackMessages = $errors;
    }
}

$cartCount = 0;
if ($isLoggedIn) {
    try {
        $cartCount = count($siteDb->getCart((int)($_SESSION['user_id'] ?? 0)));
    } catch (Throwable $exception) {
        $cartCount = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookBuy — Hỗ trợ khách hàng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php $styleVersion = @filemtime(__DIR__ . '/style.css') ?: time(); ?>
    <link rel="stylesheet" href="style.css?v=<?= $styleVersion ?>">
</head>
<body class="home support-page">

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
            <li><a href="support.php" aria-current="page">Hỗ trợ</a></li>
            <?php if ($isLoggedIn): ?>
                <li><a href="profile.php">Tài khoản</a></li>
            <?php else: ?>
                <li><a href="login.php">Đăng nhập</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="header-icons">
        <a href="cart.php" aria-label="Giỏ hàng"><i class="fa fa-shopping-cart"></i><span><?= (int)$cartCount ?></span></a>
    </div>
</header>

<main>
    <section class="support-page-layout">

        <!-- FORM GỬI YÊU CẦU -->
        <div class="support-form-card">
            <h1>Gửi yêu cầu trực tuyến</h1>
            
            <?php if (!empty($feedbackMessages)): ?>
                <div class="support-alert <?= htmlspecialchars($feedbackType) ?>">
                    <?php foreach ($feedbackMessages as $message): ?>
                        <p><?= htmlspecialchars($message) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="support.php" class="support-form-simple" novalidate>
                <label>
                    <span>Họ và tên *</span>
                    <input type="text" name="name" value="<?= htmlspecialchars($formData['name']) ?>" required>
                </label>

                <label>
                    <span>Email *</span>
                    <input type="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" required>
                </label>

                <label>
                    <span>Mã đơn hàng (nếu có)</span>
                    <input type="text" name="order" placeholder="VD: BB-1234" value="<?= htmlspecialchars($formData['order']) ?>">
                </label>

                <label>
                    <span>Chủ đề</span>
                    <select name="topic">
                        <?php foreach ($supportTopics as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value) ?>" <?= $formData['topic'] === $value ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="support-form-full">
                    <span>Nội dung yêu cầu *</span>
                    <textarea name="message" rows="6" required><?= htmlspecialchars($formData['message']) ?></textarea>
                </label>

                <button type="submit" class="btn primary support-submit">
                    <i class="fa-solid fa-paper-plane"></i>
                    Gửi yêu cầu
                </button>
            </form>

        </div>

        <!-- LỊCH SỬ HỖ TRỢ -->
        <div class="support-form-card support-history-box">
            <h2>Lịch sử hỗ trợ của bạn</h2>

            <?php if (!$isLoggedIn): ?>
                <p class="muted">Vui lòng đăng nhập để xem lịch sử hỗ trợ.</p>

            <?php else: ?>
                <?php if (empty($userHistory)): ?>
                    <p class="muted">Bạn chưa gửi yêu cầu hỗ trợ nào.</p>

                <?php else: ?>
                    
                    <table class="support-history-table">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Chủ đề</th>
                                <th>Nội dung</th>
                                <th>Trạng thái</th>
                                <th>Phản hồi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userHistory as $h): ?>
                                <tr>
                                    <td><?= htmlspecialchars($h['created_at']) ?></td>
                                    <td><?= htmlspecialchars($supportTopics[$h['topic']] ?? '') ?></td>
                                    <td><?= htmlspecialchars($h['message']) ?></td>
                                    <td>
                                        <?php if ($h['status'] === 'done'): ?>
                                            <span style="color: green; font-weight: bold;">Hoàn thành</span>
                                        <?php else: ?>
                                            <span style="color: #ff9800; font-weight: bold;">Chưa xử lý</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
    <?php if (empty($h['reply'])): ?>
        <span style="color:#777;">(Chưa có phản hồi)</span>
    <?php else: ?>
        <button class="btn primary"
            onclick='showReplyDetail(<?= json_encode($h, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
            Xem phản hồi
        </button>
    <?php endif; ?>
</td>

                                </tr>
                            <?php endforeach; ?>

                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    </section>
</main>

<!-- Modal xem phản hồi -->
<div id="replyModal" style="
    display:none;
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    background:rgba(0,0,0,0.45);
    justify-content:center;
    align-items:center;
    z-index:9999;
">
    <div style="
        background:#fff;
        padding:25px;
        width:600px;
        max-height:90vh;
        overflow-y:auto;
        border-radius:10px;
    ">
        <h3 style="margin-bottom:10px;">Chi tiết phản hồi</h3>

        <p><strong>Ngày gửi:</strong> <span id="r_date"></span></p>
        <p><strong>Chủ đề:</strong> <span id="r_topic"></span></p>
        <p><strong>Nội dung yêu cầu:</strong><br> <span id="r_message"></span></p>
        <p><strong>Phản hồi:</strong><br> <span id="r_reply"></span></p>

        <br>
        <button class="btn danger" onclick="closeReplyModal()">Đóng</button>
    </div>
</div>

<script>
function showReplyDetail(data) {
    document.getElementById('r_date').innerText = data.created_at;
    document.getElementById('r_topic').innerText = data.topic;
    document.getElementById('r_message').innerText = data.message;
    document.getElementById('r_reply').innerText = data.reply || '(Chưa có phản hồi)';
    document.getElementById('replyModal').style.display = 'flex';
}

function closeReplyModal() {
    document.getElementById('replyModal').style.display = 'none';
}
</script>
<footer>
    &copy; 2025 BookBuy | Read More, Live More
</footer>

</body>
</html>
