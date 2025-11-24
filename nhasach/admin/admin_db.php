<?php
require_once __DIR__ . '/../db.php';

class AdminDB extends DB
{
    public function login(string $username, string $password)
    {
        $password = md5($password);
        $sql = "SELECT * FROM ADMIN WHERE TK = ? AND MK = ?";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('ss', $username, $password);
        if (!$stmt->execute()) {
            return false;
        }
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            $_SESSION['user_type'] = 'admin';
            $_SESSION['username'] = $admin['TK'];
            return true;
        }

        return false;
    }

    public function addBook(string $tensach, int $matl, float $gia, string $anh, string $tacgia, string $mota)
    {
        $sql = "INSERT INTO SACH (TENSACH, MATL, GIA, ANH, TACGIA, MOTA) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('sidsss', $tensach, $matl, $gia, $anh, $tacgia, $mota);
        return $stmt->execute();
    }

    public function deleteBook(int $bookId)
    {
        $sql = "DELETE FROM SACH WHERE MASACH = ?";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('i', $bookId);
        return $stmt->execute();
    }

    public function addCategory(string $tentl)
    {
        $sql = "INSERT INTO THELOAI (TENTL) VALUES (?)";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('s', $tentl);
        return $stmt->execute();
    }

    public function updateCategory(int $matl, string $tentl)
    {
        $sql = "UPDATE THELOAI SET TENTL = ? WHERE MATL = ?";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('si', $tentl, $matl);
        return $stmt->execute();
    }

    public function deleteCategory(int $matl)
    {
        $sql = "DELETE FROM THELOAI WHERE MATL = ?";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('i', $matl);
        return $stmt->execute();
    }

    public function getCategoryById(int $matl)
    {
        $sql = "SELECT * FROM THELOAI WHERE MATL = ?";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('i', $matl);
        if (!$stmt->execute()) {
            return null;
        }
        $result = $stmt->get_result();
        return ($result ? ($result->fetch_assoc() ?: null) : null);
    }
    public function getAllSupportRequests()
    {
        $sql = "SELECT 
                    t.MATN AS id,
                    t.TENKH AS name,
                    t.NOIDUNG AS payload,
                    t.CREATED_AT AS created_at,
                    kh.EMAIL AS account_email,
                    t.TRANGTHAI,
                    t.TRALOI
                FROM TINNHAN t
                JOIN KHACHHANG kh ON kh.MAKH = t.MAKH
                ORDER BY t.MATN DESC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        foreach ($rows as &$row) {
                $row['email'] = $row['account_email'];
                $row['message'] = $row['payload'];
                $row['status'] = $row['TRANGTHAI'] ?? 'pending';
                $row['reply'] = $row['TRALOI'] ?? '';

                $decoded = json_decode($row['payload'], true);
                if (is_array($decoded)) {
                    if (!empty($decoded['email'])) {
                        $row['email'] = (string) $decoded['email'];
                    }
                    if (isset($decoded['message'])) {
                        $row['message'] = (string) $decoded['message'];
                    }
                }

                unset($row['payload'], $row['account_email'], $row['TRANGTHAI'], $row['TRALOI']);
            }
            unset($row);

            return $rows;
        }

    public function deleteSupportRequest(int $id)
    {
        $sql = "DELETE FROM TINNHAN WHERE MATN = ?";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    public function addSupportRequest(string $name, string $email, string $order, string $topic, string $message)
    {
        $makh = (int)($_SESSION['user_id'] ?? 0);
        if ($makh <= 0) {
            return false;
        }

        $tenkh = $name !== '' ? $name : (string)($_SESSION['tenkh'] ?? '');

        $payload = [
            'email' => $email,
            'topic' => $topic,
            'message' => $message,
        ];

        $noiDung = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($noiDung === false) {
            return false;
        }

        $sql = "INSERT INTO TINNHAN (MAKH, TENKH, NOIDUNG) VALUES (?, ?, ?)";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('iss', $makh, $tenkh, $noiDung);
        return $stmt->execute();
    }
    public function replySupportRequest(int $id, string $reply)
    {
        $sql = "UPDATE TINNHAN 
                SET TRALOI = ?, TRANGTHAI = 'done'
                WHERE MATN = ?";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('si', $reply, $id);
        return $stmt->execute();
    }
public function getUserSupportHistory(int $makh)
{
    $sql = "SELECT 
                MATN AS id,
                NOIDUNG,
                TRANGTHAI,
                TRALOI,
                CREATED_AT
            FROM TINNHAN
            WHERE MAKH = ?
            ORDER BY MATN DESC";

    $stmt = $this->getConnection()->prepare($sql);
    $stmt->bind_param('i', $makh);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    foreach ($rows as &$row) {

        // Giải mã JSON
        $decoded = json_decode($row['NOIDUNG'], true);

        $row['email']      = $decoded['email']      ?? '';
        $row['topic']      = $decoded['topic']      ?? '';
        $row['order_code'] = $decoded['order_code'] ?? '';
        $row['message']    = $decoded['message']    ?? '';

        // Trạng thái
        $row['status'] = $row['TRANGTHAI'] ?? 'pending';
        $row['reply']  = $row['TRALOI']    ?? '';

        // FIX LỖI tại đây – nếu created_at null hoặc không có
        $row['created_at'] = $row['CREATED_AT'] ?? '';

        unset($row['NOIDUNG'], $row['TRANGTHAI'], $row['TRALOI'], $row['CREATED_AT']);
    }

    return $rows;
}
/**
 * Lấy tất cả đơn hàng kèm thông tin khách hàng
 */
public function getAllOrders()
{
    $sql = "SELECT 
                dh.MADH,
                dh.MAKH,
                kh.TENKH,
                kh.EMAIL,
                kh.SDT,
                dh.NGAYDAT,
                dh.TONGTIEN,
                dh.TRANGTHAI,
                dh.DIACHI,
                dh.GHICHU
            FROM DONHANG dh
            LEFT JOIN KHACHHANG kh ON kh.MAKH = dh.MAKH
            ORDER BY dh.NGAYDAT DESC";
    
    $stmt = $this->getConnection()->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Lấy thông tin chi tiết một đơn hàng
 */
public function getOrderById(int $madh)
{
    $sql = "SELECT 
                dh.*,
                kh.TENKH,
                kh.EMAIL,
                kh.SDT
            FROM DONHANG dh
            LEFT JOIN KHACHHANG kh ON kh.MAKH = dh.MAKH
            WHERE dh.MADH = ?";
    
    $stmt = $this->getConnection()->prepare($sql);
    $stmt->bind_param('i', $madh);
    
    if (!$stmt->execute()) {
        return null;
    }
    
    $result = $stmt->get_result();
    return $result ? ($result->fetch_assoc() ?: null) : null;
}

/**
 * Lấy chi tiết sản phẩm trong đơn hàng
 */
public function getOrderDetails(int $madh)
{
    $sql = "SELECT 
                ct.MASACH,
                s.TENSACH,
                s.ANH,
                ct.SOLUONG,
                ct.DONGIA,
                (ct.SOLUONG * ct.DONGIA) AS THANHTIEN
            FROM CHITIETDONHANG ct
            LEFT JOIN SACH s ON s.MASACH = ct.MASACH
            WHERE ct.MADH = ?";
    
    $stmt = $this->getConnection()->prepare($sql);
    $stmt->bind_param('i', $madh);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Cập nhật trạng thái đơn hàng
 */
public function updateOrderStatus(int $madh, string $trangthai)
{
    $validStatuses = ['pending', 'processing', 'shipping', 'completed', 'cancelled'];
    
    if (!in_array($trangthai, $validStatuses, true)) {
        return false;
    }
    
    $sql = "UPDATE DONHANG SET TRANGTHAI = ? WHERE MADH = ?";
    $stmt = $this->getConnection()->prepare($sql);
    $stmt->bind_param('si', $trangthai, $madh);
    
    return $stmt->execute();
}

/**
 * Xóa đơn hàng (và chi tiết đơn hàng)
 */
public function deleteOrder(int $madh)
{
    $connection = $this->getConnection();
    
    // Xóa chi tiết đơn hàng trước
    $sql1 = "DELETE FROM CHITIETDONHANG WHERE MADH = ?";
    $stmt1 = $connection->prepare($sql1);
    $stmt1->bind_param('i', $madh);
    $stmt1->execute();
    
    // Sau đó xóa đơn hàng
    $sql2 = "DELETE FROM DONHANG WHERE MADH = ?";
    $stmt2 = $connection->prepare($sql2);
    $stmt2->bind_param('i', $madh);
    
    return $stmt2->execute();
}

/**
 * Thống kê đơn hàng theo trạng thái
 */
public function getOrderStatistics()
{
    $sql = "SELECT 
                TRANGTHAI,
                COUNT(*) AS so_luong,
                SUM(TONGTIEN) AS tong_tien
            FROM DONHANG
            GROUP BY TRANGTHAI";
    
    $stmt = $this->getConnection()->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $stats = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $stats[$row['TRANGTHAI']] = [
                'count' => (int) $row['so_luong'],
                'total' => (float) $row['tong_tien']
            ];
        }
    }
    
    return $stats;
}
}

function isAdmin()
{
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}
?>
