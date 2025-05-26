<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';

// Lấy thông tin đơn hàng
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /delivery-management/admin/orders.php");
    exit();
}

$id = $_GET['id'];

try {
    $stmt = $conn->prepare("SELECT * FROM DonHang WHERE order_id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        header("Location: /delivery-management/admin/orders.php");
        exit();
    }
    
    // Lấy danh sách nhân viên
    $stmt = $conn->query("SELECT * FROM NhanVien ORDER BY ho_ten");
    $employees = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $khach_hang = $_POST['khach_hang'] ?? '';
    $dia_chi = $_POST['dia_chi'] ?? '';
    $ngay_giao = $_POST['ngay_giao'] ?? '';
    $nhanvien_id = !empty($_POST['nhanvien_id']) ? $_POST['nhanvien_id'] : null;
    $trang_thai = $_POST['trang_thai'] ?? 'dang_giao';
    
    if (empty($khach_hang) || empty($dia_chi) || empty($ngay_giao)) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
    } else {
        try {
            // Cập nhật thông tin đơn hàng
            $stmt = $conn->prepare("
                UPDATE DonHang 
                SET khach_hang = ?, dia_chi = ?, ngay_giao = ?, nhanvien_id = ?, trang_thai = ?
                WHERE order_id = ?
            ");
            $stmt->execute([$khach_hang, $dia_chi, $ngay_giao, $nhanvien_id, $trang_thai, $id]);
            
            $success = 'Cập nhật thông tin đơn hàng thành công';
            
            // Cập nhật lại thông tin đơn hàng
            $stmt = $conn->prepare("SELECT * FROM DonHang WHERE order_id = ?");
            $stmt->execute([$id]);
            $order = $stmt->fetch();
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
        <h1 class="h2">Chỉnh sửa đơn hàng</h1>
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
                    <label for="khach_hang" class="form-label">Tên khách hàng <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="khach_hang" name="khach_hang" value="<?php echo htmlspecialchars($order['khach_hang']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="dia_chi" class="form-label">Địa chỉ giao hàng <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="dia_chi" name="dia_chi" rows="3" required><?php echo htmlspecialchars($order['dia_chi']); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="ngay_giao" class="form-label">Ngày giao <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="ngay_giao" name="ngay_giao" value="<?php echo $order['ngay_giao']; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="nhanvien_id" class="form-label">Nhân viên giao hàng</label>
                    <select class="form-select" id="nhanvien_id" name="nhanvien_id">
                        <option value="">-- Chọn nhân viên --</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo $employee['nhanvien_id']; ?>" <?php echo $order['nhanvien_id'] == $employee['nhanvien_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($employee['ho_ten']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="trang_thai" class="form-label">Trạng thái</label>
                    <select class="form-select" id="trang_thai" name="trang_thai">
                        <option value="dang_giao" <?php echo $order['trang_thai'] === 'dang_giao' ? 'selected' : ''; ?>>Đang giao</option>
                        <option value="hoan_thanh" <?php echo $order['trang_thai'] === 'hoan_thanh' ? 'selected' : ''; ?>>Hoàn thành</option>
                        <option value="huy" <?php echo $order['trang_thai'] === 'huy' ? 'selected' : ''; ?>>Hủy</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                    <a href="/delivery-management/admin/order-view.php?id=<?php echo $id; ?>" class="btn btn-info">Xem chi tiết</a>
                    <a href="/delivery-management/admin/orders.php" class="btn btn-secondary">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
