<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
// Kiểm tra đăng nhập
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Kiểm tra quyền admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Chuyển hướng nếu chưa đăng nhập
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /delivery-management/auth/login.php");
        exit();
    }
}

// Chuyển hướng nếu không phải admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: /employee/index.php");
        exit();
    }
}

// Hàm lấy thông tin nhân viên theo ID
function getEmployeeById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM NhanVien WHERE nhanvien_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Hàm lấy thông tin phương tiện theo ID
function getVehicleById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM PhuongTien WHERE phuongtien_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Hàm lấy thông tin đơn hàng theo ID
function getOrderById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM DonHang WHERE order_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Hàm tính tổng giá trị đơn hàng
function calculateOrderTotal($conn, $orderId) {
    $stmt = $conn->prepare("SELECT SUM(so_luong * don_gia) as total FROM ChiTietDonHang WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

// Hàm lấy danh sách chi tiết đơn hàng
function getOrderDetails($conn, $orderId) {
    $stmt = $conn->prepare("SELECT * FROM ChiTietDonHang WHERE order_id = ?");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

// Hàm định dạng tiền tệ
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' VNĐ';
}

// Hàm định dạng ngày tháng
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

// Hàm lấy danh sách phương tiện chưa được gán
function getAvailableVehicles($conn) {
    $stmt = $conn->prepare("
        SELECT * FROM PhuongTien 
        WHERE phuongtien_id NOT IN (
            SELECT phuongtien_id FROM NhanVien 
            WHERE phuongtien_id IS NOT NULL
        )
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Hàm lấy danh sách đơn hàng theo nhân viên
function getOrdersByEmployee($conn, $employeeId) {
    $stmt = $conn->prepare("SELECT * FROM DonHang WHERE nhanvien_id = ? ORDER BY ngay_giao DESC");
    $stmt->execute([$employeeId]);
    return $stmt->fetchAll();
}

// Hàm lấy thông tin lương theo nhân viên và tháng
function getSalaryByEmployeeAndMonth($conn, $employeeId, $month) {
    $stmt = $conn->prepare("SELECT * FROM Luong WHERE nhanvien_id = ? AND thang = ?");
    $stmt->execute([$employeeId, $month]);
    return $stmt->fetch();
}

// Hàm tạo mật khẩu băm
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Hàm kiểm tra mật khẩu
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
?>
