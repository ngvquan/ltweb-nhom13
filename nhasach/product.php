<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: text/html; charset=UTF-8');

$db = new DB();

$bookId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$book = null;

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header('Location: admin/theloai.php');
    exit;
}

if ($bookId > 0) {
    $book = $db->getBookById($bookId);
    if (!$book) {
        http_response_code(404);
    }
} else {
    http_response_code(404);
}

$flashMessage = $_SESSION['flash_message'] ?? null;
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'], $_POST['book_id'])) {
    $bookId = (int) $_POST['book_id'];
    $quantity = isset($_POST['quantity']) ? max(1, min(99, (int) $_POST['quantity'])) : 1;

    if (!isLoggedIn()) {
        $_SESSION['flash_message'] = 'Vui lòng đăng nhập trước khi thêm sách vào giỏ hàng.';
        $_SESSION['flash_type'] = 'error';
        header('Location: login.php');
        exit;
    }

    $selectedBook = $db->getBookById($bookId);
    if (!$selectedBook) {
        $flashMessage = 'Sách bạn chọn không tồn tại.';
        $flashType = 'error';
    } else {
        try {
            $db->addToCart((int) ($_SESSION['user_id'] ?? 0), $bookId, $quantity);
            $_SESSION['flash_message'] = 'Đã thêm "' . $selectedBook['TENSACH'] . '" vào giỏ hàng.';
            $_SESSION['flash_type'] = 'success';
            header('Location: product.php?id=' . $bookId);
            exit;
        } catch (mysqli_sql_exception $exception) {
            $flashMessage = 'Không thể thêm vào giỏ hàng: ' . $exception->getMessage();
            $flashType = 'error';
        }
    }

    $book = $selectedBook ?? $book;
}

$isLoggedIn = isLoggedIn();
$username = $_SESSION['tenkh'] ?? ($_SESSION['username'] ?? '');

$categories = $db->getBookCategories();

$img = 'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=800&h=1000&fit=crop';
$description = 'Sách đang được cập nhật mô tả. Vui lòng quay lại sau nhé!';
$author = 'Đang cập nhật';
$bookCategoryLabel = 'Chưa phân loại';
$price = '0 VND';
$hasCategoryLink = false;

if ($book) {
    $bookImage = trim((string) ($book['ANH'] ?? ''));
    if ($bookImage !== '') {
        $img = $bookImage;
    }

    $bookDescription = trim((string) ($book['MOTA'] ?? ''));
    if ($bookDescription !== '') {
        $description = $bookDescription;
    }

    $bookAuthor = trim((string) ($book['TACGIA'] ?? ''));
    if ($bookAuthor !== '') {
        $author = $bookAuthor;
    }

    $bookCategory = trim((string) ($book['LOAISACH'] ?? ''));
    if ($bookCategory !== '') {
        $bookCategoryLabel = $bookCategory;
        $hasCategoryLink = true;
    }

    if (isset($book['GIA'])) {
        $price = formatCurrency((float) $book['GIA']);
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $book ? htmlspecialchars($book['TENSACH']) . ' | BookBuy' : 'Không tìm thấy sách' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="style.css">
</head>
<body class="product-page home">
        <header class="site-header">
        <div class="logo">
            <i class="fa-solid fa-book-open-reader" aria-hidden="true"></i>
            BookBuy
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Trang chủ</a></li>
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
            <?php $cartCount = 0; if ($isLoggedIn) { try { $cartCount = count($db->getCart((int)($_SESSION['user_id'] ?? 0))); } catch (Throwable $e) { $cartCount = 0; } } ?>
            <a href="cart.php" aria-label="Giỏ hàng"><i class="fa fa-shopping-cart"></i><span><?= (int)$cartCount ?></span></a>
            <a href="#" aria-label="Yêu thích"><i class="fa fa-heart"></i><span>0</span></a>
        </div>
    </header>

    <main class="product-detail-wrapper">
        <div class="product-detail">
            <?php if (!$book): ?>
                <div class="product-not-found">
                    <h1>Rất tiếc, sách không tồn tại</h1>
                    <p>Bạn hãy quay lại trang chủ để tiếp tục khám phá những tựa sách khác nhé.</p>
                    <a class="btn" href="index.php"><i class="fa fa-arrow-left" aria-hidden="true"></i> Về trang chủ</a>
                </div>
            <?php else: ?>
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="index.php">Trang chủ</a>
                    <span aria-hidden="true">/</span>
                        <?php if ($hasCategoryLink): ?>
                            <a href="index.php?category=<?= urlencode($bookCategoryLabel) ?>"><?= htmlspecialchars($bookCategoryLabel) ?></a>
                        <span aria-hidden="true">/</span>
                    <?php else: ?>
                        <span><?= htmlspecialchars($bookCategoryLabel) ?></span>
                        <span aria-hidden="true">/</span>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($book['TENSACH']) ?></span>
                </nav>

                <?php if ($flashMessage): ?>
                    <div class="flash <?= htmlspecialchars($flashType) ?>">
                        <?= htmlspecialchars($flashMessage) ?>
                    </div>
                <?php endif; ?>

                <section class="product-hero">
                    <div class="product-cover">
                        <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($book['TENSACH']) ?>">
                    </div>
                    <div class="product-meta">
                        <div class="product-meta-header">
                            <h1><?= htmlspecialchars($book['TENSACH']) ?></h1>
                          
                        </div>
                        <p class="product-author">Tác giả: <strong><?= htmlspecialchars($author) ?></strong></p>
                        <ul class="product-info-list">
                            <li><span>Thể loại:</span><strong><?= htmlspecialchars($bookCategoryLabel) ?></strong></li>
                        </ul>
                       

                        <div class="product-purchase">
                            <div class="product-price"><?= htmlspecialchars($price) ?></div>
                            <?php if ($isLoggedIn): ?>
                                <form method="post" class="product-cart-form">
                                    <input type="hidden" name="book_id" value="<?= (int) $book['MASACH'] ?>">
                                    <label for="quantity" class="sr-only">Số lượng</label>
                                    <input type="number" id="quantity" name="quantity" min="1" max="99" value="1">
                                    <button type="submit" name="add_to_cart">
                                        <i class="fa fa-cart-plus" aria-hidden="true"></i>
                                        Thêm vào giỏ hàng
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="product-login-warning">
                                    Vui lòng <a href="login.php">đăng nhập</a> để thêm vào giỏ hàng.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <section class="product-description-block" aria-labelledby="product-description-title">
                    <h2 id="product-description-title">Mô tả chi tiết</h2>
                    <div class="description-body">
                        <p><?= htmlspecialchars($description) ?></p>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        &copy; 2025 BookBuy | Read More, Live More
    </footer>
</body>
</html>
