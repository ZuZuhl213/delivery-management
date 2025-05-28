create database delivery_management;

USE delivery_management;

CREATE TABLE PhuongTien (
  phuongtien_id INT AUTO_INCREMENT PRIMARY KEY,
  loai VARCHAR(50) NOT NULL,
  bien_so VARCHAR(20) UNIQUE
);

CREATE TABLE NhanVien (
  nhanvien_id INT AUTO_INCREMENT PRIMARY KEY,
  ho_ten VARCHAR(100) NOT NULL,
  sdt VARCHAR(20),
  email VARCHAR(100) UNIQUE NOT NULL,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(100) NOT NULL,
  phuongtien_id INT,
  role VARCHAR(20) DEFAULT 'nhanvien',
  FOREIGN KEY (phuongtien_id) REFERENCES PhuongTien(phuongtien_id) ON DELETE SET NULL
);

CREATE TABLE DonHang (
  order_id INT AUTO_INCREMENT PRIMARY KEY,
  khach_hang VARCHAR(100) NOT NULL,
  dia_chi TEXT NOT NULL,
  ngay_giao DATE,
  nhanvien_id INT,
  trang_thai ENUM('dang_giao', 'hoan_thanh', 'huy') DEFAULT 'dang_giao',
  FOREIGN KEY (nhanvien_id) REFERENCES NhanVien(nhanvien_id) ON DELETE SET NULL
);

CREATE TABLE ChiTietDonHang (
  ct_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT,
  ten_san_pham VARCHAR(100) NOT NULL,
  so_luong INT NOT NULL,
  don_gia DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES DonHang(order_id) ON DELETE CASCADE
);

CREATE TABLE Luong (
  luong_id INT AUTO_INCREMENT PRIMARY KEY,
  nhanvien_id INT,
  thang VARCHAR(7) NOT NULL,
  luong_co_ban DECIMAL(10,2) NOT NULL,
  luong_theo_order DECIMAL(10,2),
  tong_luong DECIMAL(10,2) GENERATED ALWAYS AS (luong_co_ban + IFNULL(luong_theo_order, 0)) STORED,
  ngay_tra DATE,
  FOREIGN KEY (nhanvien_id) REFERENCES NhanVien(nhanvien_id) ON DELETE CASCADE
);

-- Tạo tài khoản admin
INSERT INTO NhanVien (ho_ten, sdt, email, username, password, role) 
VALUES ('Admin', '0123456789', 'admin@example.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
