<?php
// Kết nối đến cơ sở dữ liệu
require_once 'config/database.php';

// Kiểm tra xem đã có admin chưa
$stmt = $conn->query("SELECT COUNT(*) FROM NhanVien WHERE role = 'admin'");
$adminCount = $stmt->fetchColumn();

// Nếu đã submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ho_ten = $_POST['ho_ten'] ?? '';
    $sdt = $_POST['sdt'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Kiểm tra dữ liệu
    if (empty($ho_ten)) {
        $errors[] = 'Họ tên không được để trống';
    }
    
    if (empty($email)) {
        $errors[] = 'Email không được để trống';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ';
    } else {
        // Kiểm tra email đã tồn tại chưa
        $stmt = $conn->prepare("SELECT COUNT(*) FROM NhanVien WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Email đã tồn tại';
        }
    }
    
    if (empty($username)) {
        $errors[] = 'Tên đăng nhập không được để trống';
    } else {
        // Kiểm tra username đã tồn tại chưa
        $stmt = $conn->prepare("SELECT COUNT(*) FROM NhanVien WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Tên đăng nhập đã tồn tại';
        }
    }
    
    if (empty($password)) {
        $errors[] = 'Mật khẩu không được để trống';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Mật khẩu xác nhận không khớp';
    }
    
    // Nếu không có lỗi, thêm admin vào cơ sở dữ liệu
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("
                INSERT INTO NhanVien (ho_ten, sdt, email, username, password, role) 
                VALUES (?, ?, ?, ?, ?, 'admin')
            ");
            $stmt->execute([$ho_ten, $sdt, $email, $username, $hashedPassword]);
            
            $success = 'Tạo tài khoản admin thành công! <a href="/delivery-management/auth/login.php">Đăng nhập ngay</a>';
            
            // Cập nhật số lượng admin
            $adminCount++;
        } catch (PDOException $e) {
            $errors[] = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo tài khoản Admin - Hệ Thống Quản Lý Vận Chuyển</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        .form-container {
            max-width: 500px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .form-title {
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="form-title">
                <h2>Tạo tài khoản Admin</h2>
                <p class="text-muted">Hệ Thống Quản Lý Vận Chuyển</p>
            </div>
            
            <?php if ($adminCount > 0 && !isset($success)): ?>
                <div class="alert alert-warning">
                    <strong>Cảnh báo!</strong> Đã có <?php echo $adminCount; ?> tài khoản admin trong hệ thống. 
                    Bạn có thể <a href="/delivery-management/auth/login.php">đăng nhập</a> với tài khoản admin hiện có.
                </div>
            <?php endif; ?>
            
            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="ho_ten" class="form-label">Họ tên <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="ho_ten" name="ho_ten" value="<?php echo $_POST['ho_ten'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="sdt" class="form-label">Số điện thoại</label>
                        <input type="text" class="form-control" id="sdt" name="sdt" value="<?php echo $_POST['sdt'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo $_POST['username'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">Mật khẩu phải có ít nhất 6 ký tự</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Tạo tài khoản Admin</button>
                        <a href="/delivery-management/auth/login.php" class="btn btn-outline-secondary">Quay lại đăng nhập</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
