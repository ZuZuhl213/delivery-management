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

        <div class="row">
            <div class="col-md-6 mb-4">
                <!-- Đơn hàng theo trạng thái -->
                <div class="card">
                    <!-- ...existing code... -->
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <!-- Đơn hàng gần đây -->
                <div class="card">
                    <!-- ...existing code... -->
                </div>
            </div>
            
            <!-- Góc quản lý: Nhân viên xuất sắc -->
            <div class="col-md-12 col-lg-4 mb-4">
                <div class="card shadow-sm border-info h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-star text-warning"></i> Góc quản lý</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="fw-bold mb-2">🏆 Nhân viên xuất sắc tuần qua</h6>
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://scontent.fhan17-1.fna.fbcdn.net/v/t39.30808-6/472313328_608672488382829_3681280222989907015_n.jpg?_nc_cat=106&ccb=1-7&_nc_sid=6ee11a&_nc_eui2=AeEU71vs_JgpT7zIeKlj6NqGF1w1yWBLhGcXXDXJYEuEZ8VRhYCOUlKdbwzzit5NwviaAG28ZmimLkdai30Y7ODb&_nc_ohc=L0Rx2mOfO2YQ7kNvwHISOKK&_nc_oc=AdnN6QBwO9exNoF8zbZ9DLoMjomzs0izW5dWNGxy8pqBQUCbvDCR-yuC4fCk0ZSHvww&_nc_zt=23&_nc_ht=scontent.fhan17-1.fna&_nc_gid=WWSsQdbASB0mJzc3Xv6_Tg&oh=00_AfKxxngH3953vEKfxTKTaBG12oJ9WFNu67minaOsAlI9nQ&oe=683C02AE" alt="Trịnh Ngọc Lâm" class="rounded-circle me-3 border border-2 border-warning" style="width:56px;height:56px;">
                            <div>
                                <div class="fw-bold fs-5">Trịnh Ngọc Lâm</div>
                                <div class="text-muted small"><i class="fas fa-calendar-alt me-1"></i> 28/05/2025</div>
                                <div class="text-muted small"><i class="fas fa-truck me-1"></i> Nhân viên giao hàng</div>
                            </div>
                        </div>
                        <ul class="mb-3 ps-3">
                            <li>Hoàn thành <b>100%</b> đơn hàng đúng giờ.</li>
                            <li>Hỗ trợ đồng nghiệp trong <b>2</b> đơn hàng gấp.</li>
                        </ul>
                        <div class="bg-light rounded p-2">
                            <span class="fst-italic text-secondary">
                                <i class="fas fa-quote-left me-1"></i>
                                "Mình luôn đặt uy tín và sự hài lòng của khách lên hàng đầu. Cảm ơn công ty đã ghi nhận nỗ lực của mình!"
                            </span>
                            <div class="text-end text-info mt-1 small">- Trịnh Ngọc Lâm</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12 col-lg-8 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-bullhorn"></i> Thông báo nội bộ</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3">
                                <h6 class="mb-1"><i class="fas fa-tools text-primary"></i> Lịch bảo trì hệ thống</h6>
                                <div class="small text-muted mb-1">⏰ 01/06/2025, 22:00 - 02:00</div>
                                <div class="small">Hệ thống sẽ bảo trì định kỳ, vui lòng lưu công việc trước thời gian này.</div>
                            </li>
                            <li class="mb-3">
                                <h6 class="mb-1"><i class="fas fa-file-signature text-success"></i> Chính sách thưởng mới</h6>
                                <div class="small text-muted mb-1">Bắt đầu từ 01/06/2025</div>
                                <div class="small">Nhân viên hoàn thành xuất sắc sẽ nhận thêm thưởng tháng, chi tiết xem tại mục thông báo công ty.</div>
                            </li>
                            <li>
                                <h6 class="mb-1"><i class="fas fa-trophy text-warning"></i> Khen thưởng nhân viên nổi bật</h6>
                                <div class="small text-muted mb-1">Tuần này: Trịnh Ngọc Lâm</div>
                                <div class="small">Chúc mừng anh Lâm đã hoàn thành xuất sắc nhiệm vụ!</div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
