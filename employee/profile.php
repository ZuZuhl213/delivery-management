<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$error = '';
$success = '';

// Lấy thông tin nhân viên
$user_id = $_SESSION['user_id'];
$employee = getEmployeeById($conn, $user_id);

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ho_ten = $_POST['ho_ten'] ?? '';
    $sdt = $_POST['sdt'] ?? '';
    $email = $_POST['email'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($ho_ten) || empty($email)) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
    } elseif ($email !== $employee['email']) {
        // Kiểm tra email đã tồn tại chưa
        $stmt = $conn->prepare("SELECT COUNT(*) FROM NhanVien WHERE email = ? AND nhanvien_id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Email đã tồn tại';
        }
    }
    
    // Kiểm tra mật khẩu nếu muốn thay đổi
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $error = 'Vui lòng nhập mật khẩu hiện tại';
        } elseif (!verifyPassword($current_password, $employee['password'])) {
            $error = 'Mật khẩu hiện tại không đúng';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Mật khẩu mới không khớp';
        }
    }
    
    if (empty($error)) {
        try {
            if (!empty($new_password)) {
                // Cập nhật thông tin và mật khẩu
                $hashedPassword = hashPassword($new_password);
                $stmt = $conn->prepare("
                    UPDATE NhanVien 
                    SET ho_ten = ?, sdt = ?, email = ?, password = ?
                    WHERE nhanvien_id = ?
                ");
                $stmt->execute([$ho_ten, $sdt, $email, $hashedPassword, $user_id]);
            } else {
                // Chỉ cập nhật thông tin
                $stmt = $conn->prepare("
                    UPDATE NhanVien 
                    SET ho_ten = ?, sdt = ?, email = ?
                    WHERE nhanvien_id = ?
                ");
                $stmt->execute([$ho_ten, $sdt, $email, $user_id]);
            }
            
            $success = 'Cập nhật thông tin thành công';
            
            // Cập nhật lại thông tin nhân viên
            $employee = getEmployeeById($conn, $user_id);
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Hồ sơ cá nhân</h1>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Thông tin cá nhân</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="ho_ten" class="form-label">Họ tên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ho_ten" name="ho_ten" value="<?php echo htmlspecialchars($employee['ho_ten']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="sdt" class="form-label">Số điện thoại</label>
                            <input type="text" class="form-control" id="sdt" name="sdt" value="<?php echo htmlspecialchars($employee['sdt'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Tên đăng nhập</label>
                            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($employee['username']); ?>" readonly>
                            <div class="form-text">Tên đăng nhập không thể thay đổi</div>
                        </div>
                        
                        <hr>
                        
                        <h5>Đổi mật khẩu</h5>
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Mật khẩu mới</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                        
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">Cập nhật</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Thông tin phương tiện</h5>
                </div>
                <div class="card-body">
                    <?php if ($employee['phuongtien_id']): ?>
                        <?php
                        $vehicle = getVehicleById($conn, $employee['phuongtien_id']);
                        ?>
                        <table class="table">
                            <tr>
                                <th style="width: 30%">Loại:</th>
                                <td><?php echo htmlspecialchars($vehicle['loai']); ?></td>
                            </tr>
                            <tr>
                                <th>Biển số:</th>
                                <td><?php echo htmlspecialchars($vehicle['bien_so']); ?></td>
                            </tr>
                        </table>
                        
                        <div class="mb-3">
                            <form method="POST" action="/delivery-management/employee/vehicles.php">
                                <input type="hidden" name="action" value="return">
                                <button type="submit" class="btn btn-warning">
                                    <i class="bi bi-x-circle"></i> Trả phương tiện
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Bạn chưa được gán phương tiện.
                        </div>
                        
                        <div class="mb-3">
                            <a href="/delivery-management/employee/vehicles.php" class="btn btn-primary">
                                <i class="bi bi-truck"></i> Chọn phương tiện
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Thông tin lương</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Lấy thông tin lương gần nhất
                    $stmt = $conn->prepare("
                        SELECT * FROM Luong 
                        WHERE nhanvien_id = ?
                        ORDER BY thang DESC
                        LIMIT 3
                    ");
                    $stmt->execute([$user_id]);
                    $salaries = $stmt->fetchAll();
                    ?>
                    
                    <?php if (empty($salaries)): ?>
                        <div class="alert alert-info">
                            Chưa có thông tin lương.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Tháng</th>
                                        <th>Lương cơ bản</th>
                                        <th>Lương theo đơn</th>
                                        <th>Tổng lương</th>
                                        <th>Ngày trả</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($salaries as $salary): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($salary['thang']); ?></td>
                                            <td><?php echo formatCurrency($salary['luong_co_ban']); ?></td>
                                            <td><?php echo formatCurrency($salary['luong_theo_order'] ?? 0); ?></td>
                                            <td><?php echo formatCurrency($salary['tong_luong']); ?></td>
                                            <td><?php echo $salary['ngay_tra'] ? formatDate($salary['ngay_tra']) : 'Chưa trả'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
