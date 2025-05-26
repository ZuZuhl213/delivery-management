<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

// Lấy thống kê tổng quan
try {
    // Tổng số nhân viên
    $stmt = $conn->query("SELECT COUNT(*) as total FROM NhanVien");
    $totalEmployees = $stmt->fetch()['total'];
    
    // Tổng số phương tiện
    $stmt = $conn->query("SELECT COUNT(*) as total FROM PhuongTien");
    $totalVehicles = $stmt->fetch()['total'];
    
    // Tổng số đơn hàng
    $stmt = $conn->query("SELECT COUNT(*) as total FROM DonHang");
    $totalOrders = $stmt->fetch()['total'];
    
    // Đơn hàng theo trạng thái
    $stmt = $conn->query("SELECT trang_thai, COUNT(*) as count FROM DonHang GROUP BY trang_thai");
    $ordersByStatus = $stmt->fetchAll();
    
    // Đơn hàng gần đây
    $stmt = $conn->query("
        SELECT d.*, nv.ho_ten as nhanvien_name 
        FROM DonHang d
        LEFT JOIN NhanVien nv ON d.nhanvien_id = nv.nhanvien_id
        ORDER BY d.order_id DESC LIMIT 5
    ");
    $recentOrders = $stmt->fetchAll();
    
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
        <div class="col-md-4 mb-4">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Nhân viên</h5>
                            <h2 class="mb-0"><?php echo $totalEmployees; ?></h2>
                        </div>
                        <i class="bi bi-people fs-1"></i>
                    </div>
                    <a href="/delivery-management/admin/employees.php" class="text-white">Xem chi tiết <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Phương tiện</h5>
                            <h2 class="mb-0"><?php echo $totalVehicles; ?></h2>
                        </div>
                        <i class="bi bi-truck fs-1"></i>
                    </div>
                    <a href="/delivery-management/admin/vehicles.php" class="text-white">Xem chi tiết <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Đơn hàng</h5>
                            <h2 class="mb-0"><?php echo $totalOrders; ?></h2>
                        </div>
                        <i class="bi bi-box-seam fs-1"></i>
                    </div>
                    <a href="/delivery-management/admin/orders.php" class="text-white">Xem chi tiết <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Đơn hàng theo trạng thái</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Trạng thái</th>
                                    <th>Số lượng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ordersByStatus as $status): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $statusText = '';
                                            $badgeClass = '';
                                            
                                            switch ($status['trang_thai']) {
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
                                        <td><?php echo $status['count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Đơn hàng gần đây</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Khách hàng</th>
                                    <th>Nhân viên</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><?php echo $order['order_id']; ?></td>
                                        <td><?php echo $order['khach_hang']; ?></td>
                                        <td><?php echo $order['nhanvien_name'] ?? 'Chưa gán'; ?></td>
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
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
