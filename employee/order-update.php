<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$error = '';
$success = '';

// Lấy thông tin nhân viên
$user_id = $_SESSION['user_id'];

// Lấy thông tin đơn hàng
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /employee/orders.php");
    exit();
}

$id = $_GET['id'];

try {
    // Kiểm tra xem đơn hàng có thuộc về nhân viên này không
    $stmt = $conn->prepare("SELECT COUNT(*) FROM DonHang WHERE order_id = ? AND nhanvien_id = ?");
    $stmt->execute([$id, $user_id]);
    if ($stmt->fetchColumn() == 0) {
        header("Location: /employee/orders.php");
        exit();
    }
    
    // Lấy thông tin đơn hàng
    $stmt = $conn->prepare("SELECT * FROM DonHang WHERE order_id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    
    // Kiểm tra xem đơn hàng có đang ở trạng thái "đang giao" không
    if ($order['trang_thai'] !== 'dang_giao') {
        header("Location: /employee/order-view.php?id=$id");
        exit();
    }
    
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}

// Xử lý cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trang_thai = $_POST['trang_thai'] ?? '';
    
    if (empty($trang_thai)) {
        $error = 'Vui lòng chọn trạng thái';
    } else {
        try {
            // Cập nhật trạng thái đơn hàng
            $stmt = $conn->prepare("UPDATE DonHang SET trang_thai = ? WHERE order_id = ?");
            $stmt->execute([$trang_thai, $id]);
            
            $success = 'Cập nhật trạng thái đơn hàng thành công';
            
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
        <h1 class="h2">Cập nhật trạng thái đơn hàng #<?php echo $id; ?></h1>
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
                    <h5 class="card-title mb-0">Thông tin đơn hàng</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th style="width: 30%">Mã đơn hàng:</th>
                            <td><?php echo $order['order_id']; ?></td>
                        </tr>
                        <tr>
                            <th>Khách hàng:</th>
                            <td><?php echo htmlspecialchars($order['khach_hang']); ?></td>
                        </tr>
                        <tr>
                            <th>Địa chỉ:</th>
                            <td><?php echo htmlspecialchars($order['dia_chi']); ?></td>
                        </tr>
                        <tr>
                            <th>Ngày giao:</th>
                            <td><?php echo formatDate($order['ngay_giao']); ?></td>
                        </tr>
                        <tr>
                            <th>Trạng thái hiện tại:</th>
                            <td>
                                <span class="badge bg-warning">Đang giao</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Cập nhật trạng thái</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="trang_thai" class="form-label">Trạng thái mới <span class="text-danger">*</span></label>
                            <select class="form-select" id="trang_thai" name="trang_thai" required>
                                <option value="">-- Chọn trạng thái --</option>
                                <option value="hoan_thanh">Hoàn thành</option>
                                <option value="huy">Hủy</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">Cập nhật</button>
                            <a href="/delivery-management/employee/order-view.php?id=<?php echo $id; ?>" class="btn btn-secondary">Quay lại</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
