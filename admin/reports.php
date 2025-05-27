<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';

try {
    // Đơn hàng theo nhân viên
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

    // Doanh thu theo tháng, đổi alias thành month_year
    $stmt = $conn->query("
        SELECT 
            DATE_FORMAT(dh.ngay_giao, '%Y-%m') AS month_year,
            COUNT(DISTINCT dh.order_id) AS total_orders,
            SUM(CASE WHEN dh.trang_thai = 'hoan_thanh' THEN 1 ELSE 0 END) AS completed_orders,
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
            ) AS revenue
        FROM DonHang dh
        GROUP BY month_year
        ORDER BY month_year DESC
    ");
    $revenueByMonth = $stmt->fetchAll();

    // Trạng thái đơn hàng
    $stmt = $conn->query("
        SELECT 
            trang_thai,
            COUNT(*) as count
        FROM DonHang
        GROUP BY trang_thai
    ");
    $ordersByStatus = $stmt->fetchAll();

    // Phương tiện dùng nhiều nhất
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

    // Tổng giá trị đơn hàng theo nhân viên
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

<style>
.card.shadow-sm {
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
    border-radius: 0.5rem;
    background-color: #f8f9fa; /* Nền sáng nhẹ */
}
.card-header {
    background-color: #343a40;
    color: #fff;
    font-weight: 600;
    font-size: 1.1rem;
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
}
.table {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    overflow: hidden;
    background-color: #fff;
}
.table thead tr {
    background-color: #17a2b8;
    color: white;
    font-weight: 600;
}
.table tbody tr:hover {
    background-color: #d1ecf1;
    cursor: pointer;
}
.table td, .table th {
    padding: 0.75rem 1rem;
    vertical-align: middle;
}
.badge {
    font-weight: 600;
    font-size: 0.9rem;
    padding: 0.35em 0.65em;
    border-radius: 0.5rem;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}
.badge.bg-warning {
    background-color: #ffc107 !important;
    color: #856404;
}
.badge.bg-success {
    background-color: #28a745 !important;
    color: #155724;
}
.badge.bg-danger {
    background-color: #dc3545 !important;
    color: #721c24;
}
.badge .icon {
    font-size: 1.1rem;
}
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Báo cáo & Thống kê</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Đơn hàng theo nhân viên -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header"><h5>Đơn hàng theo nhân viên</h5></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped mb-0">
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
                            <?php if (!$ordersByEmployee): ?>
                                <tr><td colspan="5" class="text-center">Không có dữ liệu</td></tr>
                            <?php else: foreach ($ordersByEmployee as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['ho_ten']); ?></td>
                                    <td><?php echo $item['total_orders']; ?></td>
                                    <td><?php echo $item['completed_orders']; ?></td>
                                    <td><?php echo $item['pending_orders']; ?></td>
                                    <td><?php echo $item['canceled_orders']; ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Doanh thu theo tháng -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header"><h5>Doanh thu theo tháng</h5></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Tháng</th>
                                <th>Tổng đơn</th>
                                <th>Hoàn thành</th>
                                <th>Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$revenueByMonth): ?>
                                <tr><td colspan="4" class="text-center">Không có dữ liệu</td></tr>
                            <?php else: foreach ($revenueByMonth as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['month_year']); ?></td>
                                    <td><?php echo $item['total_orders']; ?></td>
                                    <td><?php echo $item['completed_orders']; ?></td>
                                    <td><?php echo formatCurrency($item['revenue'] ?? 0); ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Trạng thái đơn hàng -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header"><h5>Trạng thái đơn hàng</h5></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Trạng thái</th>
                                <th>Số lượng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$ordersByStatus): ?>
                                <tr><td colspan="2" class="text-center">Không có dữ liệu</td></tr>
                            <?php else: foreach ($ordersByStatus as $item):
                                $statusText = '';
                                $badgeClass = '';
                                $icon = '';
                                switch ($item['trang_thai']) {
                                    case 'dang_giao':
                                        $statusText = 'Đang giao';
                                        $badgeClass = 'bg-warning';
                                        $icon = '⏳';
                                        break;
                                    case 'hoan_thanh':
                                        $statusText = 'Hoàn thành';
                                        $badgeClass = 'bg-success';
                                        $icon = '✔️';
                                        break;
                                    case 'huy':
                                        $statusText = 'Hủy';
                                        $badgeClass = 'bg-danger';
                                        $icon = '❌';
                                        break;
                                }
                            ?>
                                <tr>
                                    <td><span class="badge <?php echo $badgeClass; ?>"><span class="icon"><?php echo $icon; ?></span> <?php echo $statusText; ?></span></td>
                                    <td><?php echo $item['count']; ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Phương tiện được dùng nhiều -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header"><h5>Phương tiện được sử dụng nhiều nhất</h5></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Phương tiện</th>
                                <th>Biển số</th>
                                <th>Số đơn hàng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$topVehicles): ?>
                                <tr><td colspan="3" class="text-center">Không có dữ liệu</td></tr>
                            <?php else: foreach ($topVehicles as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['loai']); ?></td>
                                    <td><?php echo htmlspecialchars($item['bien_so']); ?></td>
                                    <td><?php echo $item['order_count']; ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Tổng giá trị đơn hàng theo nhân viên -->
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header"><h5>Tổng giá trị đơn hàng theo nhân viên</h5></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Nhân viên</th>
                                <th>Tổng giá trị đơn hàng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$totalValueByEmployee): ?>
                                <tr><td colspan="2" class="text-center">Không có dữ liệu</td></tr>
                            <?php else: foreach ($totalValueByEmployee as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['ho_ten']); ?></td>
                                    <td><?php echo formatCurrency($item['total_value']); ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
