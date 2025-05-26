<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';

// Lấy thông tin phương tiện
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /delivery-management/admin/vehicles.php");
    exit();
}

$id = $_GET['id'];

try {
    $stmt = $conn->prepare("SELECT * FROM PhuongTien WHERE phuongtien_id = ?");
    $stmt->execute([$id]);
    $vehicle = $stmt->fetch();
    
    if (!$vehicle) {
        header("Location: /delivery-management/admin/vehicles.php");
        exit();
    }
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loai = $_POST['loai'] ?? '';
    $bien_so = $_POST['bien_so'] ?? '';
    
    if (empty($loai) || empty($bien_so)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } else {
        try {
            // Kiểm tra biển số đã tồn tại chưa (nếu thay đổi)
            if ($bien_so !== $vehicle['bien_so']) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM PhuongTien WHERE bien_so = ? AND phuongtien_id != ?");
                $stmt->execute([$bien_so, $id]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Biển số đã tồn tại';
                }
            }
            
            if (empty($error)) {
                // Cập nhật thông tin phương tiện
                $stmt = $conn->prepare("UPDATE PhuongTien SET loai = ?, bien_so = ? WHERE phuongtien_id = ?");
                $stmt->execute([$loai, $bien_so, $id]);
                
                $success = 'Cập nhật thông tin phương tiện thành công';
                
                // Cập nhật lại thông tin phương tiện
                $stmt = $conn->prepare("SELECT * FROM PhuongTien WHERE phuongtien_id = ?");
                $stmt->execute([$id]);
                $vehicle = $stmt->fetch();
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
        <h1 class="h2">Chỉnh sửa thông tin phương tiện</h1>
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
                <div class="mb-3">
                    <label for="loai" class="form-label">Loại phương tiện <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="loai" name="loai" value="<?php echo htmlspecialchars($vehicle['loai']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="bien_so" class="form-label">Biển số <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="bien_so" name="bien_so" value="<?php echo htmlspecialchars($vehicle['bien_so']); ?>" required>
                </div>
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                    <a href="/delivery-management/admin/vehicles.php" class="btn btn-secondary">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
