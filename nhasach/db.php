<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

class DB {
    public $host = 'localhost';
    public $user = 'root';
    public $pass = '';
    public $dbname = 'nhasach';
    private $db;

    public function __construct() {
        $this->db = new mysqli($this->host, $this->user, $this->pass, $this->dbname);

        if ($this->db->connect_error) {
            die('Ket noi that bai: ' . $this->db->connect_error);
        }

        $this->db->set_charset('utf8mb4');
    }

    public function getConnection(): mysqli {
        return $this->db;
    }

    public function login(string $username, string $password) {
        $password = md5($password);
        $sql = "SELECT * FROM KHACHHANG WHERE TK = ? AND MK = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $username, $password);
        if (!$stmt->execute()) { return false; }
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $_SESSION['user_type'] = 'user';
            $_SESSION['user_id'] = $user['MAKH'];
            $_SESSION['username'] = $user['TK'];
            $_SESSION['tenkh'] = $user['TENKH'];
           
            return true;
        }

        return false;
    }

    public function registerCustomer(string $tenkh, string $email, string $sdt, string $diachi, string $tk, string $mk) {
        $sql = "INSERT INTO KHACHHANG (TENKH, EMAIL, SDT, DIACHI, TK, MK) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $mk = md5($mk);
        $stmt->bind_param('ssssss', $tenkh, $email, $sdt, $diachi, $tk, $mk);
        return $stmt->execute();
    }

    public function getAllBooks() {
        $sql = "SELECT s.*, tl.TENTL AS LOAISACH
                FROM SACH s
                LEFT JOIN THELOAI tl ON tl.MATL = s.MATL
                ORDER BY s.MASACH DESC";
        $result = $this->db->query($sql);
        $books = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $books[] = $row;
            }
        }

        return $books;
    }

    public function getBooksByCategory(string $categoryName) {
        $sql = "SELECT s.*, tl.TENTL AS LOAISACH
                FROM SACH s
                JOIN THELOAI tl ON tl.MATL = s.MATL
                WHERE tl.TENTL = ?
                ORDER BY s.MASACH DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $categoryName);
        if (!$stmt->execute()) { return []; }
        $result = $stmt->get_result();

        $books = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $books[] = $row;
            }
        }

        return $books;
    }

    public function getBookCategories() {
        $sql = "SELECT TENTL FROM THELOAI ORDER BY TENTL ASC";
        $result = $this->db->query($sql);
        $categories = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row['TENTL'];
            }
        }

        return $categories;
    }

    public function getAllCategories() {
        $sql = "SELECT MATL, TENTL FROM THELOAI ORDER BY TENTL ASC";
        $result = $this->db->query($sql);
        $cats = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $cats[] = $row;
            }
        }
        return $cats;
    }

    public function getBookById(int $bookId) {
        $sql = "SELECT s.*, tl.TENTL AS LOAISACH
                FROM SACH s
                LEFT JOIN THELOAI tl ON tl.MATL = s.MATL
                WHERE s.MASACH = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $bookId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? ($result->fetch_assoc() ?: null) : null;
    }


    public function addToCart(int $makh, int $masach, int $soluong = 1) {
        $quantity = max(1, min(999, $soluong));

        $selectSql = "SELECT SOLUONG FROM GIOHANG WHERE MAKH = ? AND MASACH = ? LIMIT 1";
        $selectStmt = $this->db->prepare($selectSql);
        $selectStmt->bind_param('ii', $makh, $masach);
        $selectStmt->execute();
        $selectResult = $selectStmt->get_result();
        $existing = $selectResult ? $selectResult->fetch_assoc() : null;
        if ($selectResult instanceof mysqli_result) {
            $selectResult->free();
        }
        $selectStmt->close();

        if ($existing) {
            $newQuantity = min(999, ((int) $existing['SOLUONG']) + $quantity);
            $updateSql = "UPDATE GIOHANG SET SOLUONG = ? WHERE MAKH = ? AND MASACH = ?";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->bind_param('iii', $newQuantity, $makh, $masach);
            $success = $updateStmt->execute();
            $updateStmt->close();
            return $success;
        }

        $insertSql = "INSERT INTO GIOHANG (MAKH, MASACH, SOLUONG) VALUES (?, ?, ?)";
        $insertStmt = $this->db->prepare($insertSql);
        $insertStmt->bind_param('iii', $makh, $masach, $quantity);
        $result = $insertStmt->execute();
        $insertStmt->close();
        return $result;
    }

    public function getCart(int $makh) {
        $sql = "SELECT g.*, s.TENSACH, s.GIA, s.ANH
                FROM GIOHANG g
                JOIN SACH s ON g.MASACH = s.MASACH
                WHERE g.MAKH = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $makh);
        $stmt->execute();
        $result = $stmt->get_result();
        $cart = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $cart[] = $row;
            }
        }

        return $cart;
    }

    public function updateCartQuantity(int $makh, int $masach, int $soluong) {
        if ($soluong < 1) {
            return $this->removeCartItem($makh, $masach);
        }

        $sql = "UPDATE GIOHANG SET SOLUONG = ? WHERE MAKH = ? AND MASACH = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iii', $soluong, $makh, $masach);
        return $stmt->execute();
    }

    public function removeCartItem(int $makh, int $masach) {
        $sql = "DELETE FROM GIOHANG WHERE MAKH = ? AND MASACH = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $makh, $masach);
        return $stmt->execute();
    }

    public function getCustomerById(int $makh) {
        $sql = "SELECT MAKH, TENKH, EMAIL, SDT, DIACHI, TK FROM KHACHHANG WHERE MAKH = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $makh);
        $stmt->execute();
        $result = $stmt->get_result();
        return ($result && $result->num_rows === 1) ? $result->fetch_assoc() : null;
    }

    public function verifyCustomerPassword(int $makh, string $password) {
        $hash = md5($password);
        $sql  = "SELECT 1 FROM KHACHHANG WHERE MAKH = ? AND MK = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('is', $makh, $hash);
        $stmt->execute();
        $result = $stmt->get_result();
        return ($result && $result->num_rows === 1);
    }

    public function updateCustomerAccount(int $makh, string $tenkh, string $email) {
        $sql = "UPDATE KHACHHANG SET TENKH = ?, EMAIL = ? WHERE MAKH = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssi', $tenkh, $email, $makh);
        return $stmt->execute();
    }

    public function updateCustomerPassword(int $makh, string $newPassword) {
        $hash = md5($newPassword);
        $sql  = "UPDATE KHACHHANG SET MK = ? WHERE MAKH = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $hash, $makh);
        return $stmt->execute();
    }

    public function updateCustomerProfile(int $makh, string $tenkh, string $sdt, string $diachi) {
        $sql = "UPDATE KHACHHANG SET TENKH = ?, SDT = ?, DIACHI = ? WHERE MAKH = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sssi', $tenkh, $sdt, $diachi, $makh);
        return $stmt->execute();
    }

    public function close(): void {
        if ($this->db instanceof mysqli) {
            $this->db->close();
        }
    }

    public function __destruct() {
        $this->close();
    }

    public function createOrder(int $makh, $orderData, $cartItems): ?int
    {
        
        $this->db->begin_transaction();
        
        try {
           
            $tongTien = (float) $orderData['total'];
            $diaChi = trim($orderData['address']) . ', ' . trim($orderData['city']);
            $ghiChu = trim($orderData['notes'] ?? '');
            
          
            $sql = "INSERT INTO DONHANG (MAKH, TONGTIEN, DIACHI, GHICHU, TRANGTHAI) 
                    VALUES (?, ?, ?, ?, 'pending')";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('idss', $makh, $tongTien, $diaChi, $ghiChu);
            
            if (!$stmt->execute()) {
                throw new Exception('Không thể tạo đơn hàng');
            }
            
            $madh = $this->db->insert_id;
            
           
            $sqlDetail = "INSERT INTO CHITIETDONHANG (MADH, MASACH, SOLUONG, DONGIA) 
                          VALUES (?, ?, ?, ?)";
            $stmtDetail = $this->db->prepare($sqlDetail);
            
            foreach ($cartItems as $item) {
                $masach = (int) $item['MASACH'];
                $soluong = (int) $item['SOLUONG'];
                $dongia = (float) $item['GIA'];
                
                $stmtDetail->bind_param('iiid', $madh, $masach, $soluong, $dongia);
                if (!$stmtDetail->execute()) {
                    throw new Exception('Không thể thêm chi tiết đơn hàng');
                }
            }
            
            
            $sqlClearCart = "DELETE FROM GIOHANG WHERE MAKH = ?";
            $stmtClear = $this->db->prepare($sqlClearCart);
            $stmtClear->bind_param('i', $makh);
            $stmtClear->execute();
            
            $this->db->commit();
            
            return $madh;
            
        } catch (Exception $e) {
            /
            $this->db->rollback();
            error_log('Create order error: ' . $e->getMessage());
            return null;
        }
    }

   
    public function getCustomerOrderHistory(int $makh)
    {
        $sql = "SELECT 
                    dh.MADH,
                    dh.NGAYDAT,
                    dh.TONGTIEN,
                    dh.TRANGTHAI,
                    dh.DIACHI
                FROM DONHANG dh
                WHERE dh.MAKH = ?
                ORDER BY dh.NGAYDAT DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $makh);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /
    public function getOrderDetailsByCustomer(int $makh, int $madh)
    {
        
        $sql = "SELECT * FROM DONHANG WHERE MADH = ? AND MAKH = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $madh, $makh);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result ? $result->fetch_assoc() : null;
        
        if (!$order) {
            return null;
        }
        
        // Lấy chi tiết sản phẩm
        $sqlItems = "SELECT 
                        ct.MASACH,
                        s.TENSACH,
                        s.ANH,
                        ct.SOLUONG,
                        ct.DONGIA,
                        (ct.SOLUONG * ct.DONGIA) AS THANHTIEN
                     FROM CHITIETDONHANG ct
                     LEFT JOIN SACH s ON s.MASACH = ct.MASACH
                     WHERE ct.MADH = ?";
        
        $stmtItems = $this->db->prepare($sqlItems);
        $stmtItems->bind_param('i', $madh);
        $stmtItems->execute();
        $resultItems = $stmtItems->get_result();
        
        $order['items'] = $resultItems ? $resultItems->fetch_all(MYSQLI_ASSOC) : [];
        
        return $order;
    }
}

function connectDB(): mysqli {
    return (new DB())->getConnection();
}

function formatCurrency(float $amount): string {
    return number_format($amount, 0, ',', '.') . ' VND';
}

function isLoggedIn() {
    if (isset($_SESSION['user_type'])) {
        return $_SESSION['user_type'] === 'user';
    }

    return isset($_SESSION['user_id']);
}
