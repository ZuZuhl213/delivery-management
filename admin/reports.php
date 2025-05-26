<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';

// Lấy thống kê đơn hàng theo nhân viên
try {
    $stmt = $conn->query("
        SELECT nv.nhanvien_id, nv.ho_ten,
            COUNT(dh.order_id) as total_orders,
            SUM(CASE WHEN dh.trang_thai = 'hoan_thanh' THEN 1 ELSE 0 END) as completed_orders,
            SUM(CASE WHEN dh.trang_thai = 'dang_giao' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN dh.trang_thai = 'huy' THEN 1 ELSE 0 END) as canceled_orders
        FROM NhanVien nv
        LEFT JOIN DonHang dh ON nv.nhanvien_id = dh.nhanvien_id
        GROUP BY nv.nhanvien_id, nv.ho_ten
        ORDER BY total_orders DESC
    ");
    $ordersByEmployee = $stmt->fetchAll();
    
    // Lấy thống kê doanh thu theo tháng
    // Lấy thống kê doanh thu theo tháng
    $stmt = $conn->query("
        SELECT 
            DATE_FORMAT(dh.ngay_giao, '%Y-%m') as month,
            COUNT(DISTINCT dh.order_id) as total_orders,
            SUM(CASE WHEN dh.trang_thai = 'hoan_thanh' THEN 1 ELSE 0 END) as completed_orders,
            COALESCE(
                SUM(
                    CASE 
                        WHEN dh.trang_thai = 'hoan_thanh' THEN (
                            SELECT COALESCE(SUM(ct2.so_luong * ct2.don_gia), 0)
                            FROM ChiTietDonHang ct2 
                            WHERE ct2.order_id = dh.order_id
                        )
                        ELSE 0 
                    END
                ), 0
            ) as revenue
        FROM DonHang dh
        GROUP BY DATE_FORMAT(dh.ngay_giao, '%Y-%m')
        ORDER BY month DESC
    ");
    $revenueByMonth = $stmt->fetchAll();
    
    // Lấy thống kê trạng thái đơn hàng
    $stmt = $conn->query("
        SELECT 
            trang_thai,
            COUNT(*) as count
        FROM DonHang
        GROUP BY trang_thai
    ");
    $ordersByStatus = $stmt->fetchAll();
    
    // Lấy thống kê phương tiện được sử dụng nhiều nhất
    $stmt = $conn->query("
        SELECT 
            pt.phuongtien_id,
            pt.loai,
            pt.bien_so,
            COUNT(dh.order_id) as order_count
        FROM PhuongTien pt
        JOIN NhanVien nv ON pt.phuongtien_id = nv.phuongtien_id
        JOIN DonHang dh ON nv.nhanvien_id = dh.nhanvien_id
        GROUP BY pt.phuongtien_id, pt.loai, pt.bien_so
        ORDER BY order_count DESC
        LIMIT 5
    ");
    $topVehicles = $stmt->fetchAll();
    
    // Lấy tổng tiền các đơn theo từng nhân viên
    $stmt = $conn->query("
        SELECT 
            nv.nhanvien_id,
            nv.ho_ten,
            SUM(ct.so_luong * ct.don_gia) as total_value
        FROM NhanVien nv
        JOIN DonHang dh ON nv.nhanvien_id = dh.nhanvien_id
        JOIN ChiTietDonHang ct ON dh.order_id = ct.order_id
        WHERE dh.trang_thai = 'hoan_thanh'
        GROUP BY nv.nhanvien_id, nv.ho_ten
        ORDER BY total_value DESC
    ");
    $totalValueByEmployee = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Lỗi khi lấy dữ liệu báo cáo: ' . $e->getMessage();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Báo cáo & Thống kê</h1>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Đơn hàng theo nhân viên</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nhân viên</th>
                                    <th>Tổng đơn</th>
                                    <th>Hoàn thành</th>
                                    <th>Đang giao</th>
                                    <th>Hủy</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ordersByEmployee)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Không có dữ liệu</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($ordersByEmployee as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['ho_ten']); ?></td>
                                            <td><?php echo $item['total_orders']; ?></td>
                                            <td><?php echo $item['completed_orders']; ?></td>
                                            <td><?php echo $item['pending_orders']; ?></td>
                                            <td><?php echo $item['canceled_orders']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Doanh thu theo tháng</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Tháng</th>
                                    <th>Tổng đơn</th>
                                    <th>Hoàn thành</th>
                                    <th>Doanh thu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($revenueByMonth)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Không có dữ liệu</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($revenueByMonth as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['month']); ?></td>
                                            <td><?php echo $item['total_orders']; ?></td>
                                            <td><?php echo $item['completed_orders']; ?></td>
                                            <td><?php echo formatCurrency($item['revenue'] ?? 0); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Trạng thái đơn hàng</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Trạng thái</th>
                                    <th>Số lượng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ordersByStatus)): ?>
                                    <tr>
                                        <td colspan="2" class="text-center">Không có dữ liệu</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($ordersByStatus as $item): ?>
                                        <tr>
                                            <td>
                                                <?php 
                                                $statusText = '';
                                                $badgeClass = '';
                                                
                                                switch ($item['trang_thai']) {
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
                                            <td><?php echo $item['count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Phương tiện được sử dụng nhiều nhất</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Phương tiện</th>
                                    <th>Biển số</th>
                                    <th>Số đơn hàng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topVehicles)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Không có dữ liệu</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($topVehicles as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['loai']); ?></td>
                                            <td><?php echo htmlspecialchars($item['bien_so']); ?></td>
                                            <td><?php echo $item['order_count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tổng giá trị đơn hàng theo nhân viên</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nhân viên</th>
                                    <th>Tổng giá trị đơn hàng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($totalValueByEmployee)): ?>
                                    <tr>
                                        <td colspan="2" class="text-center">Không có dữ liệu</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($totalValueByEmployee as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['ho_ten']); ?></td>
                                            <td><?php echo formatCurrency($item['total_value']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
