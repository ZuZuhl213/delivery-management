<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Chỉ admin mới có quyền tạo tài khoản
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ho_ten = $_POST['ho_ten'] ?? '';
    $sdt = $_POST['sdt'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'nhanvien';

    if (empty($ho_ten) || empty($email) || empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
    } else {
        try {
            // Kiểm tra username đã tồn tại chưa
            $stmt = $conn->prepare("SELECT COUNT(*) FROM NhanVien WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Tên đăng nhập đã tồn tại';
            } else {
                // Kiểm tra email đã tồn tại chưa
                $stmt = $conn->prepare("SELECT COUNT(*) FROM NhanVien WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Email đã tồn tại';
                } else {
                    // Tạo tài khoản mới
                    $hashedPassword = hashPassword($password);
                    $stmt = $conn->prepare("
                        INSERT INTO NhanVien (ho_ten, sdt, email, username, password, role) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$ho_ten, $sdt, $email, $username, $hashedPassword, $role]);
                    
                    $success = 'Tạo tài khoản thành công';
                }
            }
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản - Hệ Thống Quản Lý Vận Chuyển</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="container mt-4">
        <h2>Đăng ký tài khoản mới</h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="ho_ten" class="form-label">Họ tên <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="ho_ten" name="ho_ten" required>
                    </div>
                    <div class="mb-3">
                        <label for="sdt" class="form-label">Số điện thoại</label>
                        <input type="text" class="form-control" id="sdt" name="sdt">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Vai trò</label>
                        <select class="form-select" id="role" name="role">
                            <option value="nhanvien">Nhân viên</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Đăng ký</button>
                    <a href="/delivery-management/admin/employees.php" class="btn btn-secondary">Quay lại</a>
                </form>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
