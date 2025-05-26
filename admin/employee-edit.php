<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';

// Lấy thông tin nhân viên
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /delivery-management/admin/employees.php");
    exit();
}

$id = $_GET['id'];

try {
    // Lấy thông tin nhân viên
    $stmt = $conn->prepare("SELECT * FROM NhanVien WHERE nhanvien_id = ?");
    $stmt->execute([$id]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        header("Location: /delivery-management/admin/employees.php");
        exit();
    }
    
    // Lấy danh sách phương tiện
    $stmt = $conn->query("SELECT * FROM PhuongTien ORDER BY phuongtien_id");
    $vehicles = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ho_ten = $_POST['ho_ten'] ?? '';
    $sdt = $_POST['sdt'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'nhanvien';
    $phuongtien_id = !empty($_POST['phuongtien_id']) ? $_POST['phuongtien_id'] : null;
    
    if (empty($ho_ten) || empty($email) || empty($username)) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
    } else {
        try {
            // Kiểm tra username đã tồn tại chưa (nếu thay đổi)
            if ($username !== $employee['username']) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM NhanVien WHERE username = ? AND nhanvien_id != ?");
                $stmt->execute([$username, $id]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Tên đăng nhập đã tồn tại';
                }
            }
            
            // Kiểm tra email đã tồn tại chưa (nếu thay đổi)
            if (empty($error) && $email !== $employee['email']) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM NhanVien WHERE email = ? AND nhanvien_id != ?");
                $stmt->execute([$email, $id]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Email đã tồn tại';
                }
            }
            
            if (empty($error)) {
                // Cập nhật thông tin nhân viên
                if (!empty($password)) {
                    // Nếu có cập nhật mật khẩu
                    $hashedPassword = hashPassword($password);
                    $stmt = $conn->prepare("
                        UPDATE NhanVien 
                        SET ho_ten = ?, sdt = ?, email = ?, username = ?, password = ?, role = ?, phuongtien_id = ?
                        WHERE nhanvien_id = ?
                    ");
                    $stmt->execute([$ho_ten, $sdt, $email, $username, $hashedPassword, $role, $phuongtien_id, $id]);
                } else {
                    // Không cập nhật mật khẩu
                    $stmt = $conn->prepare("
                        UPDATE NhanVien 
                        SET ho_ten = ?, sdt = ?, email = ?, username = ?, role = ?, phuongtien_id = ?
                        WHERE nhanvien_id = ?
                    ");
                    $stmt->execute([$ho_ten, $sdt, $email, $username, $role, $phuongtien_id, $id]);
                }
                
                $success = 'Cập nhật thông tin nhân viên thành công';
                
                // Cập nhật lại thông tin nhân viên
                $stmt = $conn->prepare("SELECT * FROM NhanVien WHERE nhanvien_id = ?");
                $stmt->execute([$id]);
                $employee = $stmt->fetch();
            }
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
        <h1 class="h2">Chỉnh sửa thông tin nhân viên</h1>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="ho_ten" class="form-label">Họ tên <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="ho_ten" name="ho_ten" value="<?php echo htmlspecialchars($employee['ho_ten']); ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="sdt" class="form-label">Số điện thoại</label>
                        <input type="text" class="form-control" id="sdt" name="sdt" value="<?php echo htmlspecialchars($employee['sdt'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="username" class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($employee['username']); ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Mật khẩu <small class="text-muted">(để trống nếu không thay đổi)</small></label>
                        <input type="password" class="form-control" id="password" name="password">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="role" class="form-label">Vai trò</label>
                        <select class="form-select" id="role" name="role">
                            <option value="nhanvien" <?php echo $employee['role'] === 'nhanvien' ? 'selected' : ''; ?>>Nhân viên</option>
                            <option value="admin" <?php echo $employee['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="phuongtien_id" class="form-label">Phương tiện</label>
                    <select class="form-select" id="phuongtien_id" name="phuongtien_id">
                        <option value="">-- Không gán phương tiện --</option>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <?php 
                            // Kiểm tra xem phương tiện đã được gán cho nhân viên khác chưa
                            $isAssigned = false;
                            if ($vehicle['phuongtien_id'] != $employee['phuongtien_id']) {
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM NhanVien WHERE phuongtien_id = ? AND nhanvien_id != ?");
                                $stmt->execute([$vehicle['phuongtien_id'], $id]);
                                $isAssigned = $stmt->fetchColumn() > 0;
                            }
                            ?>
                            <option value="<?php echo $vehicle['phuongtien_id']; ?>" 
                                <?php echo $vehicle['phuongtien_id'] == $employee['phuongtien_id'] ? 'selected' : ''; ?>
                                <?php echo $isAssigned ? 'disabled' : ''; ?>>
                                <?php echo htmlspecialchars($vehicle['loai'] . ' - ' . $vehicle['bien_so']); ?>
                                <?php echo $isAssigned ? ' (Đã gán cho nhân viên khác)' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                    <a href="/delivery-management/admin/employees.php" class="btn btn-secondary">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
