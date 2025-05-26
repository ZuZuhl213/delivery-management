<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$error = '';
$success = '';

// Lấy thông tin nhân viên
$user_id = $_SESSION['user_id'];

// Lấy danh sách đơn hàng của nhân viên
try {
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    
    $query = "
        SELECT * FROM DonHang
        WHERE nhanvien_id = ?
    ";
    $params = [$user_id];
    
    if (!empty($status)) {
        $query .= " AND trang_thai = ?";
        $params[] = $status;
    }
    
    $query .= " ORDER BY ngay_giao DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Lỗi khi lấy danh sách đơn hàng: ' . $e->getMessage();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Đơn hàng của tôi</h1>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <select class="form-select" name="status" onchange="this.form.submit()">
                        <option value="" <?php echo !isset($_GET['status']) || $_GET['status'] === '' ? 'selected' : ''; ?>>Tất cả trạng thái</option>
                        <option value="dang_giao" <?php echo isset($_GET['status']) && $_GET['status'] === 'dang_giao' ? 'selected' : ''; ?>>Đang giao</option>
                        <option value="hoan_thanh" <?php echo isset($_GET['status']) && $_GET['status'] === 'hoan_thanh' ? 'selected' : ''; ?>>Hoàn thành</option>
                        <option value="huy" <?php echo isset($_GET['status']) && $_GET['status'] === 'huy' ? 'selected' : ''; ?>>Hủy</option>
                    </select>
                </div>
                <?php if (isset($_GET['status'])): ?>
                    <div class="col-md-2">
                        <a href="/delivery-management/employee/orders.php" class="btn btn-outline-secondary w-100">Xóa bộ lọc</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Khách hàng</th>
                            <th>Địa chỉ</th>
                            <th>Ngày giao</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Không có đơn hàng nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo $order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['khach_hang']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($order['dia_chi'], 0, 30) . (strlen($order['dia_chi']) > 30 ? '...' : '')); ?></td>
                                    <td><?php echo formatDate($order['ngay_giao']); ?></td>
                                    <td>
                                        <?php 
                                        $statusText = '';
                                        $badgeClass = '';
                                        
                                        switch ($order['trang_thai']) {
                                            case 'dang_giao':
                                                $statusText = 'Đang giao';
                                                $badgeClass = 'bg-warning';
                                                break;
                                            case 'hoan_thanh':
                                                $statusText = 'Hoàn thành';
                                                $badgeClass = 'bg-success';
                                                break;
                                            case 'huy':
                                                $statusText = 'Hủy';
                                                $badgeClass = 'bg-danger';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo $statusText; ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="/delivery-management/employee/order-view.php?id=<?php echo $order['order_id']; ?>" class="btn btn-info">
                                                <i class="bi bi-eye"></i> Xem
                                            </a>
                                            <?php if ($order['trang_thai'] === 'dang_giao'): ?>
                                                <a href="/delivery-management/employee/order-update.php?id=<?php echo $order['order_id']; ?>" class="btn btn-primary">
                                                    <i class="bi bi-pencil"></i> Cập nhật
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
