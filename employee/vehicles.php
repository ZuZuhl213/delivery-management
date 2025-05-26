<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$error = '';
$success = '';

// Lấy thông tin nhân viên
$user_id = $_SESSION['user_id'];
$employee = getEmployeeById($conn, $user_id);

// Xử lý trả phương tiện
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'return') {
    try {
        $stmt = $conn->prepare("UPDATE NhanVien SET phuongtien_id = NULL WHERE nhanvien_id = ?");
        $stmt->execute([$user_id]);
        
        $success = 'Trả phương tiện thành công';
        
        // Cập nhật lại thông tin nhân viên
        $employee = getEmployeeById($conn, $user_id);
    } catch (PDOException $e) {
        $error = 'Lỗi hệ thống: ' . $e->getMessage();
    }
}

// Xử lý chọn phương tiện
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'select') {
    $phuongtien_id = $_POST['phuongtien_id'] ?? '';
    
    if (empty($phuongtien_id)) {
        $error = 'Vui lòng chọn phương tiện';
    } else {
        try {
            // Kiểm tra xem phương tiện đã được gán cho nhân viên khác chưa
            $stmt = $conn->prepare("SELECT COUNT(*) FROM NhanVien WHERE phuongtien_id = ? AND nhanvien_id != ?");
            $stmt->execute([$phuongtien_id, $user_id]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Phương tiện này đã được gán cho nhân viên khác';
            } else {
                // Gán phương tiện cho nhân viên
                $stmt = $conn->prepare("UPDATE NhanVien SET phuongtien_id = ? WHERE nhanvien_id = ?");
                $stmt->execute([$phuongtien_id, $user_id]);
                
                $success = 'Chọn phương tiện thành công';
                
                // Cập nhật lại thông tin nhân viên
                $employee = getEmployeeById($conn, $user_id);
            }
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

// Lấy danh sách phương tiện
try {
    // Lấy danh sách phương tiện chưa được gán
    $stmt = $conn->query("
        SELECT * FROM PhuongTien 
        WHERE phuongtien_id NOT IN (
            SELECT phuongtien_id FROM NhanVien 
            WHERE phuongtien_id IS NOT NULL
        )
        ORDER BY loai, bien_so
    ");
    $vehicles = $stmt->fetchAll();
    
    // Lấy thông tin phương tiện hiện tại
    $currentVehicle = null;
    if ($employee['phuongtien_id']) {
        $currentVehicle = getVehicleById($conn, $employee['phuongtien_id']);
    }
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Quản lý phương tiện</h1>
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
                    <h5 class="card-title mb-0">Phương tiện hiện tại</h5>
                </div>
                <div class="card-body">
                    <?php if ($currentVehicle): ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <i class="bi bi-truck" style="font-size: 3rem;"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-1"><?php echo htmlspecialchars($currentVehicle['loai']); ?></h5>
                                <p class="text-muted mb-0">Biển số: <?php echo htmlspecialchars($currentVehicle['bien_so']); ?></p>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="return">
                                <button type="submit" class="btn btn-warning" onclick="return confirm('Bạn có chắc chắn muốn trả phương tiện này?')">
                                    <i class="bi bi-x-circle"></i> Trả phương tiện
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Bạn chưa được gán phương tiện.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if (!$currentVehicle && !empty($vehicles)): ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Chọn phương tiện</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="select">
                            
                            <div class="mb-3">
                                <label for="phuongtien_id" class="form-label">Phương tiện <span class="text-danger">*</span></label>
                                <select class="form-select" id="phuongtien_id" name="phuongtien_id" required>
                                    <option value="">-- Chọn phương tiện --</option>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                        <option value="<?php echo $vehicle['phuongtien_id']; ?>">
                                            <?php echo htmlspecialchars($vehicle['loai'] . ' - ' . $vehicle['bien_so']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">Chọn phương tiện</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php elseif (!$currentVehicle && empty($vehicles)): ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Chọn phương tiện</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            Hiện tại không có phương tiện nào khả dụng. Vui lòng liên hệ quản trị viên.
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
