<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

// Lấy thông tin nhân viên
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /delivery-management/admin/employees.php");
    exit();
}

$id = $_GET['id'];

try {
    // Lấy thông tin nhân viên
    $stmt = $conn->prepare("
        SELECT nv.*, pt.loai, pt.bien_so 
        FROM NhanVien nv
        LEFT JOIN PhuongTien pt ON nv.phuongtien_id = pt.phuongtien_id
        WHERE nv.nhanvien_id = ?
    ");
    $stmt->execute([$id]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        header("Location: /delivery-management/admin/employees.php");
        exit();
    }
    
    // Lấy danh sách đơn hàng của nhân viên
    $stmt = $conn->prepare("
        SELECT * FROM DonHang 
        WHERE nhanvien_id = ?
        ORDER BY ngay_giao DESC
    ");
    $stmt->execute([$id]);
    $orders = $stmt->fetchAll();
    
    // Lấy thông tin lương
    $stmt = $conn->prepare("
        SELECT * FROM Luong 
        WHERE nhanvien_id = ?
        ORDER BY thang DESC
    ");
    $stmt->execute([$id]);
    $salaries = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Thông tin nhân viên</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="/delivery-management/admin/employee-edit.php?id=<?php echo $id; ?>" class="btn btn-sm btn-primary me-2">
                <i class="bi bi-pencil"></i> Chỉnh sửa
            </a>
            <a href="/delivery-management/admin/employees.php" class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left"></i> Quay lại
            </a>
        </div>
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
                    <table class="table">
                        <tr>
                            <th style="width: 30%">ID:</th>
                            <td><?php echo $employee['nhanvien_id']; ?></td>
                        </tr>
                        <tr>
                            <th>Họ tên:</th>
                            <td><?php echo htmlspecialchars($employee['ho_ten']); ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo htmlspecialchars($employee['email']); ?></td>
                        </tr>
                        <tr>
                            <th>Số điện thoại:</th>
                            <td><?php echo htmlspecialchars($employee['sdt'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Tên đăng nhập:</th>
                            <td><?php echo htmlspecialchars($employee['username']); ?></td>
                        </tr>
                        <tr>
                            <th>Vai trò:</th>
                            <td>
                                <?php if ($employee['role'] === 'admin'): ?>
                                    <span class="badge bg-danger">Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-info">Nhân viên</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Thông tin phương tiện</h5>
                </div>
                <div class="card-body">
                    <?php if ($employee['phuongtien_id']): ?>
                        <table class="table">
                            <tr>
                                <th style="width: 30%">ID:</th>
                                <td><?php echo $employee['phuongtien_id']; ?></td>
                            </tr>
                            <tr>
                                <th>Loại:</th>
                                <td><?php echo htmlspecialchars($employee['loai']); ?></td>
                            </tr>
                            <tr>
                                <th>Biển số:</th>
                                <td><?php echo htmlspecialchars($employee['bien_so']); ?></td>
                            </tr>
                        </table>
                        <form method="POST" action="/delivery-management/admin/vehicle-assign.php">
                            <input type="hidden" name="nhanvien_id" value="<?php echo $id; ?>">
                            <input type="hidden" name="action" value="remove">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-x-circle"></i> Trả phương tiện
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Nhân viên chưa được gán phương tiện.
                        </div>
                        <a href="/delivery-management/admin/vehicle-assign.php?nhanvien_id=<?php echo $id; ?>" class="btn btn-primary">
                            <i class="bi bi-truck"></i> Gán phương tiện
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Đơn hàng đã giao</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($orders)): ?>
                        <div class="alert alert-info">
                            Nhân viên chưa có đơn hàng nào.
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
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><?php echo $order['order_id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['khach_hang']); ?></td>
                                            <td><?php echo htmlspecialchars($order['dia_chi']); ?></td>
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
                                                <a href="/delivery-management/admin/order-view.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye"></i> Xem
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
                    <h5 class="card-title mb-0">Lịch sử lương</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($salaries)): ?>
                        <div class="alert alert-info">
                            Chưa có thông tin lương.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tháng</th>
                                        <th>Lương cơ bản</th>
                                        <th>Lương theo đơn</th>
                                        <th>Tổng lương</th>
                                        <th>Ngày trả</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($salaries as $salary): ?>
                                        <tr>
                                            <td><?php echo $salary['luong_id']; ?></td>
                                            <td><?php echo htmlspecialchars($salary['thang']); ?></td>
                                            <td><?php echo formatCurrency($salary['luong_co_ban']); ?></td>
                                            <td><?php echo formatCurrency($salary['luong_theo_order'] ?? 0); ?></td>
                                            <td><?php echo formatCurrency($salary['tong_luong']); ?></td>
                                            <td><?php echo $salary['ngay_tra'] ? formatDate($salary['ngay_tra']) : 'Chưa trả'; ?></td>
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
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
