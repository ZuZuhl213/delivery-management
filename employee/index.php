<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

// Lấy thông tin nhân viên
$user_id = $_SESSION['user_id'];
$employee = getEmployeeById($conn, $user_id);

// Lấy thông tin đơn hàng của nhân viên
try {
    // Đơn hàng đang giao
    $stmt = $conn->prepare("
        SELECT * FROM DonHang 
        WHERE nhanvien_id = ? AND trang_thai = 'dang_giao'
        ORDER BY ngay_giao
    ");
    $stmt->execute([$user_id]);
    $pendingOrders = $stmt->fetchAll();
    
    // Đơn hàng gần đây
    $stmt = $conn->prepare("
        SELECT * FROM DonHang 
        WHERE nhanvien_id = ? AND trang_thai != 'dang_giao'
        ORDER BY ngay_giao DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recentOrders = $stmt->fetchAll();
    
    // Thông tin phương tiện
    $vehicle = null;
    if ($employee['phuongtien_id']) {
        $vehicle = getVehicleById($conn, $employee['phuongtien_id']);
    }
    
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Tổng quan</h1>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Thông tin cá nhân</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <i class="bi bi-person-circle" style="font-size: 3rem;"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1"><?php echo htmlspecialchars($employee['ho_ten']); ?></h5>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($employee['email']); ?></p>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <a href="/delivery-management/employee/profile.php" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Chỉnh sửa hồ sơ
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Phương tiện</h5>
                </div>
                <div class="card-body">
                    <?php if ($vehicle): ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <i class="bi bi-truck" style="font-size: 3rem;"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-1"><?php echo htmlspecialchars($vehicle['loai']); ?></h5>
                                <p class="text-muted mb-0">Biển số: <?php echo htmlspecialchars($vehicle['bien_so']); ?></p>
                            </div>
                        </div>
                        
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
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Đơn hàng đang giao</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($pendingOrders)): ?>
                        <div class="alert alert-info">
                            Không có đơn hàng nào đang giao.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Khách hàng</th>
                                        <th>Địa chỉ</th>
                                        <th>Ngày giao</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingOrders as $order): ?>
                                        <tr>
                                            <td><?php echo $order['order_id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['khach_hang']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($order['dia_chi'], 0, 30) . (strlen($order['dia_chi']) > 30 ? '...' : '')); ?></td>
                                            <td><?php echo formatDate($order['ngay_giao']); ?></td>
                                            <td>
                                                <a href="/delivery-management/employee/order-view.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye"></i> Xem
                                                </a>
                                                <a href="/delivery-management/employee/order-update.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-pencil"></i> Cập nhật
                                                </a>
                                            </td>
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
    
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Đơn hàng gần đây</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recentOrders)): ?>
                        <div class="alert alert-info">
                            Không có đơn hàng nào gần đây.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
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
                                    <?php foreach ($recentOrders as $order): ?>
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
                                                <a href="/delivery-management/employee/order-view.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye"></i> Xem
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            <a href="/delivery-management/employee/orders.php" class="btn btn-primary">
                                <i class="bi bi-list"></i> Xem tất cả đơn hàng
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
