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
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}

// Xử lý thêm sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ten_san_pham = $_POST['ten_san_pham'] ?? '';
    $so_luong = $_POST['so_luong'] ?? '';
    $don_gia = $_POST['don_gia'] ?? '';
    
    if (empty($ten_san_pham) || empty($so_luong) || empty($don_gia)) {
        $error = 'Vui lòng nhập đầy đủ thông tin sản phẩm';
    } elseif (!is_numeric($so_luong) || $so_luong <= 0) {
        $error = 'Số lượng phải là số dương';
    } elseif (!is_numeric($don_gia) || $don_gia <= 0) {
        $error = 'Đơn giá phải là số dương';
    } else {
        try {
            // Thêm sản phẩm vào đơn hàng
            $stmt = $conn->prepare("
                INSERT INTO ChiTietDonHang (order_id, ten_san_pham, so_luong, don_gia) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$id, $ten_san_pham, $so_luong, $don_gia]);
            
            $success = 'Thêm sản phẩm thành công';
            
            // Xóa dữ liệu form sau khi thêm thành công
            $ten_san_pham = '';
            $so_luong = '';
            $don_gia = '';
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
        <h1 class="h2">Thêm sản phẩm vào đơn hàng #<?php echo $id; ?></h1>
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
                    <label for="ten_san_pham" class="form-label">Tên sản phẩm <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="ten_san_pham" name="ten_san_pham" value="<?php echo isset($ten_san_pham) ? htmlspecialchars($ten_san_pham) : ''; ?>" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="so_luong" class="form-label">Số lượng <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="so_luong" name="so_luong" min="1" value="<?php echo isset($so_luong) ? htmlspecialchars($so_luong) : '1'; ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="don_gia" class="form-label">Đơn giá (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="don_gia" name="don_gia" min="0" step="1000" value="<?php echo isset($don_gia) ? htmlspecialchars($don_gia) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Thêm sản phẩm</button>
                    <a href="/delivery-management/admin/order-view.php?id=<?php echo $id; ?>" class="btn btn-secondary">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
