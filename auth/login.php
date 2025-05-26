<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Nếu đã đăng nhập, chuyển hướng đến trang phù hợp
if (isLoggedIn()) {
    header("Location: " . (isAdmin() ? "/delivery-management/admin/index.php" : "/delivery-management/employee/index.php"));
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin đăng nhập';
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM NhanVien WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && verifyPassword($password, $user['password'])) {
                // Đăng nhập thành công
                $_SESSION['user_id'] = $user['nhanvien_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'] ?? 'nhanvien';
                
                // Chuyển hướng đến trang phù hợp
                header("Location: " . ($_SESSION['role'] === 'admin' ? "/delivery-management/admin/index.php" : "/delivery-management/employee/index.php"));
                exit();
            } else {
                $error = 'Tên đăng nhập hoặc mật khẩu không đúng';
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
    <title>Đăng nhập - Hệ Thống Quản Lý Vận Chuyển</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-form {
            max-width: 400px;
            width: 100%;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo i {
            font-size: 50px;
            color: #0d6efd;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="login-form">
        <div class="login-logo">
            <i class="bi bi-truck"></i>
            <h2>Quản Lý Vận Chuyển</h2>
            <p class="text-muted">Đăng nhập để tiếp tục</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Tên đăng nhập</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Mật khẩu</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Đăng nhập</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
