<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: text/html; charset=UTF-8');

$db = new DB();

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header('Location: admin/theloai.php');
    exit;
}

$flashMessage = $_SESSION['flash_message'] ?? null;
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        $_SESSION['flash_message'] = 'Vui lòng đăng nhập trước khi thêm sách vào giỏ hàng.';
        $_SESSION['flash_type'] = 'error';
        header('Location: login.php');
        exit;
    }

    $bookId = isset($_POST['book_id']) ? (int) $_POST['book_id'] : 0;
    $quantity = isset($_POST['quantity']) ? max(1, (int) $_POST['quantity']) : 1;

    $book = $db->getBookById($bookId);
    if (!$book) {
        $flashMessage = 'Sách bạn chọn không tồn tại.';
        $flashType = 'error';
    } else {
        try {
            $db->addToCart((int) ($_SESSION['user_id'] ?? 0), $bookId, $quantity);
            $_SESSION['flash_message'] = 'Đã thêm "' . $book['TENSACH'] . '" vào giỏ hàng.';
            $_SESSION['flash_type'] = 'success';
            header('Location: index.php');
            exit;
        } catch (mysqli_sql_exception $exception) {
            $flashMessage = 'Không thể thêm vào giỏ hàng: ' . $exception->getMessage();
            $flashType = 'error';
        }
    }
}

$selectedCategory = null;
if (isset($_GET['category'])) {
    $candidate = trim((string) $_GET['category']);
    if ($candidate !== '' && strcasecmp($candidate, 'all') !== 0) {
        $selectedCategory = substr($candidate, 0, 100);
    }
}

$categories = $db->getBookCategories();
if ($selectedCategory !== null && !in_array($selectedCategory, $categories, true)) {
    $selectedCategory = null;
}

$books = $selectedCategory !== null
    ? $db->getBooksByCategory($selectedCategory)
    : $db->getAllBooks();

$isLoggedIn = isLoggedIn();
$isAdminSession = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
$username = $_SESSION['tenkh'] ?? ($_SESSION['username'] ?? '');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookBuy &mdash; Đọc nhiều hơn, thường xuyên hơn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="style.css">
    <meta name="description" content="Nhà sách trực tuyến BookBuy - Khám phá và mua sách dễ dàng.">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
<style>
.dropdown {
  position: relative;
}

.dropdown > a {
  cursor: pointer;
  display: inline-block;
}

.dropdown-menu {
  display: none !important;
  position: absolute;
  top: 100%;
  left: 0;
  background-color: #fff;
  min-width: 200px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  border-radius: 4px;
  padding: 10px 0;
  z-index: 1000;
  list-style: none;
  margin: 0;
}

.dropdown:hover .dropdown-menu {
  display: block !important;
}

.dropdown-menu li {
  padding: 0;
  margin: 0;
  list-style: none;
}

.dropdown-menu li a {
  display: block !important;
  padding: 10px 20px;
  color: #333;
  text-decoration: none;
  transition: background-color 0.3s ease;
  white-space: nowrap;
}

.dropdown-menu li a:hover {
  background-color: #f0f0f0;
  color: #007bff;
}
</style>
</head>
<body class="home">
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
            <?php $cartCount = 0; if ($isLoggedIn) { try { $cartCount = count($db->getCart((int)($_SESSION['user_id'] ?? 0))); } catch (Throwable $e) { $cartCount = 0; } } ?>
            <a href="cart.php" aria-label="Giỏ hàng"><i class="fa fa-shopping-cart"></i><span><?= (int)$cartCount ?></span></a>
        </div>
    </header>

    <!-- HERO -->
    <section class="hero">
        <img src="https://images.unsplash.com/photo-1472851294608-062f824d29cc?w=1600" alt="Sách và không gian đọc">
        <div class="overlay">
            <h5>Đọc nhiều hơn</h5>
            <h1>BookBuy</h1>
            <div class="buttons">
            </div>
        </div>
    </section>

    <?php if ($flashMessage): ?>
        <div class="flash <?= htmlspecialchars($flashType) ?>" style="max-width:1080px;margin:16px auto;">
            <?= htmlspecialchars($flashMessage) ?>
        </div>
    <?php endif; ?>

    <!-- BOOKS -->
    <section class="section">
        <h2>
            Danh sách sách
            <?php if ($selectedCategory !== null): ?>
                — <?= htmlspecialchars($selectedCategory) ?>
            <?php endif; ?>
        </h2>

        <?php if (count($books) === 0): ?>
            <p class="empty">
                <?php if ($selectedCategory !== null): ?>
                    Hiện chưa có sách nào trong danh mục này.
                <?php else: ?>
                    Hiện chưa có sách nào trong hệ thống.
                <?php endif; ?>
            </p>
        <?php else: ?>
            <div class="index-search-bar">
                <input
                    type="search"
                    id="bookSearchInput"
                    placeholder="Tìm sách theo tên, tác giả hoặc thể loại"
                    autocomplete="off"
                >
                <button type="button" id="bookSearchClear">Xóa</button>
            </div>
            <div class="book-grid">
                <?php foreach ($books as $book): ?>
                    <?php
                        $img = trim((string)($book['ANH'] ?? ''));
                        if ($img === '') {
                            $img = 'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=800&h=1000&fit=crop';
                        }
                    ?>
                    <div class="book"
                         data-title="<?= htmlspecialchars($book['TENSACH'] ?? '', ENT_QUOTES) ?>"
                         data-author="<?= htmlspecialchars($book['TACGIA'] ?? '', ENT_QUOTES) ?>"
                         data-category="<?= htmlspecialchars($book['LOAISACH'] ?? '', ENT_QUOTES) ?>">
                        <div class="book-body">
                            <?php if (!empty($book['LOAISACH'])): ?>
                                <div class="tag"><?= htmlspecialchars($book['LOAISACH']) ?></div>
                            <?php endif; ?>
                            <a class="book-cover-link" href="product.php?id=<?= (int) $book['MASACH'] ?>">
                                <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($book['TENSACH']) ?>">
                            </a>
                            <h3>
                                <a href="product.php?id=<?= (int) $book['MASACH'] ?>">
                                    <?= htmlspecialchars($book['TENSACH']) ?>
                                </a>
                            </h3>
                            <p class="author">Tác giả: <?= htmlspecialchars($book['TACGIA'] ?? 'Đang cập nhật') ?></p>
                            <p class="price"><?= htmlspecialchars(formatCurrency((float)$book['GIA'])) ?></p>
                        </div>

                        <div class="book-actions">
                            <?php if ($isLoggedIn): ?>
                                <form method="post">
                                    <input type="hidden" name="book_id" value="<?= (int)$book['MASACH'] ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" name="add_to_cart">
                                        <i class="fa fa-cart-plus" aria-hidden="true"></i> Thêm vào giỏ hàng
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="book-search-empty" id="bookSearchEmpty" style="display:none;">
                Không có sách phù hợp với tìm kiếm.
            </p>
        <?php endif; ?>
    </section>

    <footer>
        &copy; 2025 BookBuy | Read More, Live More
    </footer>

    <script>
    (function () {
        const searchInput = document.getElementById('bookSearchInput');
        const clearButton = document.getElementById('bookSearchClear');
        const cards = Array.from(document.querySelectorAll('.home .book-grid .book'));
        const emptyMessage = document.getElementById('bookSearchEmpty');

        const filterBooks = () => {
            const query = (searchInput ? searchInput.value.trim().toLowerCase() : '');
            let visibleCount = 0;

            cards.forEach((card) => {
                const terms = [
                    card.dataset.title || '',
                    card.dataset.author || '',
                    card.dataset.category || ''
                ].join(' ').toLowerCase();

                const matches = query === '' || terms.includes(query);
                card.style.display = matches ? '' : 'none';
                if (matches) {
                    visibleCount += 1;
                }
            });

            if (emptyMessage) {
                emptyMessage.style.display = query && visibleCount === 0 ? '' : 'none';
            }
        };

        if (searchInput) {
            searchInput.addEventListener('input', filterBooks);
        }

        if (clearButton) {
            clearButton.addEventListener('click', () => {
                if (searchInput) {
                    searchInput.value = '';
                    searchInput.focus();
                }
                filterBooks();
            });
        }

        filterBooks();
    })();
    </script>
</body>
</html>



