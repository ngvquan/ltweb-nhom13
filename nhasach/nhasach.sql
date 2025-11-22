  SET NAMES utf8mb4;
  SET time_zone = '+00:00';

  CREATE DATABASE IF NOT EXISTS `nhasach` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  USE `nhasach`;


DROP TABLE IF EXISTS `CHITIETDONHANG`;
DROP TABLE IF EXISTS `DONHANG`;
DROP TABLE IF EXISTS `GIOHANG`;
DROP TABLE IF EXISTS `TINNHAN`;
DROP TABLE IF EXISTS `SACH`;
DROP TABLE IF EXISTS `THELOAI`;
DROP TABLE IF EXISTS `KHACHHANG`;
DROP TABLE IF EXISTS `ADMIN`;

  -- ADMIN
  CREATE TABLE `ADMIN` (
    `TK` VARCHAR(60) NOT NULL UNIQUE, 
    `MK` VARCHAR(120) NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

  -- mk = md5('123456')
  INSERT INTO `ADMIN` (`TK`,`MK`) VALUES ('admin','e10adc3949ba59abbe56e057f20f883e');

  -- KHÁCH HÀNG
  CREATE TABLE `KHACHHANG` (
    `MAKH` INT AUTO_INCREMENT PRIMARY KEY,
    `TENKH` VARCHAR(120) NOT NULL,
    `EMAIL` VARCHAR(150) NOT NULL UNIQUE,
    `SDT` VARCHAR(20) NOT NULL UNIQUE,
    `DIACHI` VARCHAR(255) NOT NULL,
    `TK` VARCHAR(60) NOT NULL UNIQUE,
    `MK` VARCHAR(120) NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

  -- mk = md5('123456')
  INSERT INTO `KHACHHANG` (`TENKH`,`EMAIL`,`SDT`,`DIACHI`,`TK`,`MK`) VALUES
  ('Nguyễn Văn A','vana@example.com','0900000001','123 Đường A, Quận 1','khach1','e10adc3949ba59abbe56e057f20f883e'),
  ('Trần Thị B','thib@example.com','0900000002','456 Đường B, Quận 3','khach2','e10adc3949ba59abbe56e057f20f883e');

  -- THỂ LOẠI
  CREATE TABLE `THELOAI` (
    `MATL` INT AUTO_INCREMENT PRIMARY KEY,
    `TENTL` VARCHAR(100) NOT NULL UNIQUE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

  INSERT INTO `THELOAI` (`TENTL`) VALUES
  ('Tiểu thuyết'),
  ('Công nghệ'),
  ('Kinh doanh'),
  ('Trinh thám'),
  ('Thiếu nhi'),
  ('Tâm lý học'),
  ('Khoa học'),
  ('Văn học cổ điển');

  -- SÁCH
  CREATE TABLE `SACH` (
    `MASACH` INT AUTO_INCREMENT PRIMARY KEY,
    `MATL` INT,
    `TENSACH` VARCHAR(160) NOT NULL,
    `GIA` DECIMAL(12,0) NOT NULL DEFAULT 0,
    `ANH` VARCHAR(255) DEFAULT NULL,
    `TACGIA` VARCHAR(160) DEFAULT NULL,
    `MOTA` TEXT DEFAULT NULL,
    CONSTRAINT `fk_sach_theloai`
      FOREIGN KEY (`MATL`) REFERENCES `THELOAI` (`MATL`)
      ON UPDATE CASCADE ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

  INSERT INTO `SACH` (`MATL`,`TENSACH`,`GIA`,`ANH`,`TACGIA`,`MOTA`) VALUES
  (1,'Đồi Gió Hú',132000,'/nhasach/img/doigiohu.png','Emily Brontë','Bi kịch tình yêu trên cánh đồng hoang Yorkshire.'),
  (1,'Thép đã tôi thế đấy',90000,'/nhasach/img/thepdatoitheday.png','Nikolai Ostrovsky','Hành trình trưởng thành và ý chí kiên cường.'),
  (1,'Ông già và biển cả',110000,'/nhasach/img/onggiavabienca.png','Ernest Hemingway','Cuộc chiến bền bỉ giữa con người và thiên nhiên.'),
  (1,'Chuông nguyện hồn ai',140000,'/nhasach/img/chuongnguyenhonai.png','Ernest Hemingway','Tình yêu và chiến tranh, khắc họa số phận con người.'),
  (8,'Những người khốn khổ',160000,'/nhasach/img/nhungnguoikhonkho.png','Victor Hugo','Bức tranh xã hội Pháp, hành trình chuộc lỗi và nhân ái.'),
  (8,'Kiêu hãnh và định kiến',155000,'/nhasach/img/kieuhanhdinhkien.png','Jane Austen','Tình yêu và định kiến giai cấp trong xã hội Anh.'),
  (8,'Túp lều bác Tôm',130000,'/nhasach/img/tupleubactom.png','Harriet Beecher Stowe','Lên án chế độ nô lệ và kêu gọi tự do, nhân quyền.'),
  (8,'Đại gia Gatsby',145000,'/nhasach/img/daigiagatsby.png','F. Scott Fitzgerald','Giấc mơ Mỹ, tình yêu và bi kịch thời Hoàng kim.'),
  (2,'Clean Code',245000,'/nhasach/img/cleancode.png','Robert C. Martin','Nguyên tắc và thực hành để viết mã sạch, dễ bảo trì.'),
  (2,'JavaScript: The Good Parts',160000,'/nhasach/img/javaScript.png','Douglas Crockford','Những phần tinh túy của JavaScript và cách sử dụng hiệu quả.'),
  (2,'Lập trình PHP & MySQL',175000,'/nhasach/img/laptrinhPHP.png','Robin Nixon','Hướng dẫn xây dựng ứng dụng web từ cơ bản đến nâng cao.'),
  (2,'Python Crash Course',220000,'/nhasach/img/python.png','Eric Matthes','Khóa học Python thực hành cho người mới bắt đầu.'),
  (2,'Code Dạo Ký Sự',180000,'/nhasach/img/codedaokisu.png','Phạm Huy Hoàng','Những câu chuyện nghề lập trình và bài học kinh nghiệm.'),
  (3,'Start With Why',189000,'/nhasach/img/staywithwhy.png','Simon Sinek','Khơi dậy động lực và lãnh đạo truyền cảm hứng.'),
  (3,'7 Thói quen hiệu quả',175000,'/nhasach/img/7thoiquen.png','Stephen R. Covey','Thay đổi tư duy và hình thành thói quen thành công.'),
  (3,'Đắc nhân tâm',99000,'/nhasach/img/dacnhantam.png','Dale Carnegie','Nghệ thuật ứng xử, xây dựng quan hệ bền vững.'),
  (3,'Tư duy nhanh và chậm',215000,'/nhasach/img/tuduynhanhvacham.png','Daniel Kahneman','Hai hệ thống tư duy và các thiên kiến nhận thức.'),
  (3,'Cha giàu cha nghèo',125000,'/nhasach/img/chagiauchagheo.png','Robert Kiyosaki','Tư duy tài chính cá nhân và con đường tự do tài chính.'),
  (4,'Sherlock Holmes Toàn tập',195000,'/nhasach/img/sherlock.png','Arthur Conan Doyle','Tuyển tập vụ án kinh điển với suy luận logic sắc bén.'),
  (4,'Án mạng trên sông Nile',165000,'/nhasach/img/anmang.png','Agatha Christie','Vụ án ly kỳ trên du thuyền xa hoa cùng thám tử Poirot.'),
  (4,'Và rồi chẳng còn ai',150000,'/nhasach/img/muoinguoidaden.png','Agatha Christie','Mười người trên đảo hoang và bí ẩn kẻ sát nhân.'),
  (5,'Dế Mèn phiêu lưu ký',88000,'/nhasach/img/demenphieuluuky.png','Tô Hoài','Hành trình phiêu lưu sinh động và giàu ý nghĩa.'),
  (5,'Harry Potter và Hòn đá Phù thủy',175000,'/nhasach/img/harrypotter.png','J.K. Rowling','Khởi đầu thế giới phù thủy diệu kỳ của Harry Potter.'),
  (5,'Alice ở xứ sở thần tiên',120000,'/nhasach/img/alicesusuthantien.png','Lewis Carroll','Câu chuyện huyền ảo và trí tưởng tượng phong phú.'),
  (6,'Tâm lý học đám đông',135000,'/nhasach/img/tamlyhocdamdong.png','Gustave Le Bon','Hiện tượng tâm lý của đám đông và ảnh hưởng xã hội.'),
  (6,'Trí tuệ cảm xúc',180000,'/nhasach/img/trituecamxuc.png','Daniel Goleman','Vai trò của EQ trong thành công và cuộc sống.'),
  (6,'Tâm lý tội phạm',195000,'/nhasach/img/tamlytoipham.png','David Canter','Phân tích hành vi tội phạm và hồ sơ tâm lý.'),
  (7,'Vũ trụ trong vỏ hạt dẻ',230000,'/nhasach/img/vutrutrongvohatde.png','Stephen Hawking','Khám phá vũ trụ, lỗ đen và thời gian.'),
  (7,'Sapiens: Lược sử loài người',220000,'/nhasach/img/luocsuloainguoi.png','Yuval Noah Harari','Lịch sử phát triển của loài người và văn minh.'),
  (7,'Lược sử thời gian',210000,'/nhasach/img/luocsuthoigian.png','Stephen Hawking','Giới thiệu những khái niệm vật lý vũ trụ cơ bản.');

  -- GIỎ HÀNG
  CREATE TABLE `GIOHANG` (
    `MAGH` INT AUTO_INCREMENT PRIMARY KEY,
    `MAKH` INT NOT NULL,
    `MASACH` INT NOT NULL,
    `SOLUONG` INT NOT NULL DEFAULT 1,
    CONSTRAINT `fk_gh_kh` FOREIGN KEY (`MAKH`) REFERENCES `KHACHHANG`(`MAKH`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_gh_sach` FOREIGN KEY (`MASACH`) REFERENCES `SACH`(`MASACH`) ON UPDATE CASCADE ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



  -- TIN NHẮN
  CREATE TABLE `TINNHAN` (
  `MATN` INT AUTO_INCREMENT PRIMARY KEY,
  `MAKH` INT NOT NULL,
  `TENKH` VARCHAR(120) NOT NULL,
  `NOIDUNG` TEXT NOT NULL,
  `CREATED_AT` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `TRANGTHAI` ENUM('pending','done') DEFAULT 'pending',
  `TRALOI` TEXT NULL,
  CONSTRAINT `fk_tn_khach` FOREIGN KEY (`MAKH`)
    REFERENCES `KHACHHANG` (`MAKH`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

  -- DONHANG 
  CREATE TABLE IF NOT EXISTS `DONHANG` (
    `MADH` int(11) NOT NULL AUTO_INCREMENT,
    `MAKH` int(11) NOT NULL,
    `NGAYDAT` datetime DEFAULT CURRENT_TIMESTAMP,
    `TONGTIEN` decimal(15,2) NOT NULL DEFAULT 0.00,
    `TRANGTHAI` enum('pending','processing','shipping','completed','cancelled') DEFAULT 'pending',
    `DIACHI` varchar(500) DEFAULT NULL,
    `GHICHU` text DEFAULT NULL,
    PRIMARY KEY (`MADH`),
    KEY `idx_makh` (`MAKH`),
    KEY `idx_trangthai` (`TRANGTHAI`),
    KEY `idx_ngaydat` (`NGAYDAT`),
    CONSTRAINT `fk_donhang_khachhang` FOREIGN KEY (`MAKH`) REFERENCES `KHACHHANG` (`MAKH`) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

  -- CHITIETDONHANG
  CREATE TABLE IF NOT EXISTS `CHITIETDONHANG` (
    `MACT` int(11) NOT NULL AUTO_INCREMENT,
    `MADH` int(11) NOT NULL,
    `MASACH` int(11) NOT NULL,
    `SOLUONG` int(11) NOT NULL DEFAULT 1,
    `DONGIA` decimal(15,2) NOT NULL,
    PRIMARY KEY (`MACT`),
    KEY `idx_madh` (`MADH`),
    KEY `idx_masach` (`MASACH`),
    CONSTRAINT `fk_chitiet_donhang` FOREIGN KEY (`MADH`) REFERENCES `DONHANG` (`MADH`) ON DELETE CASCADE,
    CONSTRAINT `fk_chitiet_sach` FOREIGN KEY (`MASACH`) REFERENCES `SACH` (`MASACH`) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


  -- Đơn hàng mẫu 1
  INSERT INTO `DONHANG` (`MAKH`, `NGAYDAT`, `TONGTIEN`, `TRANGTHAI`, `DIACHI`, `GHICHU`) 
  VALUES 
  (1, '2025-11-10 10:30:00', 450000, 'completed', '123 Nguyễn Huệ, Q.1, TP.HCM', 'Giao hàng buổi sáng'),
  (1, '2025-11-12 14:20:00', 280000, 'shipping', '456 Lê Lợi, Q.1, TP.HCM', NULL),
  (1, '2025-11-14 09:15:00', 350000, 'processing', '789 Trần Hưng Đạo, Q.5, TP.HCM', 'Gọi trước khi giao'),
  (1, '2025-11-15 11:00:00', 520000, 'pending', '321 Hai Bà Trưng, Q.3, TP.HCM', NULL);

  -- Đơn hàng 1
  INSERT INTO `CHITIETDONHANG` (`MADH`, `MASACH`, `SOLUONG`, `DONGIA`) 
  VALUES 
  (1, 1, 2, 150000),
  (1, 2, 1, 150000);

  -- Đơn hàng 2
  INSERT INTO `CHITIETDONHANG` (`MADH`, `MASACH`, `SOLUONG`, `DONGIA`) 
  VALUES 
  (2, 3, 1, 180000),
  (2, 4, 1, 100000);

  -- Đơn hàng 3
  INSERT INTO `CHITIETDONHANG` (`MADH`, `MASACH`, `SOLUONG`, `DONGIA`) 
  VALUES 
  (3, 1, 1, 150000),
  (3, 5, 1, 200000);

  -- Đơn hàng 4
  INSERT INTO `CHITIETDONHANG` (`MADH`, `MASACH`, `SOLUONG`, `DONGIA`) 
  VALUES 
  (4, 2, 2, 150000),
  (4, 3, 1, 180000),
  (4, 6, 1, 40000);