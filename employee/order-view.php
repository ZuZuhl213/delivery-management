<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$error = '';

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
    
    // Lấy chi tiết đơn hàng
    $stmt = $conn->prepare("SELECT * FROM ChiTietDonHang WHERE order_id = ?");
    $stmt->execute([$id]);
    $orderDetails = $stmt->fetchAll();
    
    // Tính tổng giá trị đơn hàng
    $total = 0;
    foreach ($orderDetails as $detail) {
        $total += $detail['so_luong'] * $detail['don_gia'];
    }
    
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Chi tiết đơn hàng #<?php echo $id; ?></h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <?php if ($order['trang_thai'] === 'dang_giao'): ?>
                <a href="/delivery-management/employee/order-update.php?id=<?php echo $id; ?>" class="btn btn-sm btn-primary me-2">
                    <i class="bi bi-pencil"></i> Cập nhật trạng thái
                </a>
            <?php endif; ?>
            <a href="/delivery-management/employee/orders.php" class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
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
                            <th>Trạng thái:</th>
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
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Thông tin giao hàng</h5>
                </div>
                <div class="card-body">
                    <?php
                    $employee = getEmployeeById($conn, $user_id);
                    $vehicle = null;
                    if ($employee['phuongtien_id']) {
                        $vehicle = getVehicleById($conn, $employee['phuongtien_id']);
                    }
                    ?>
                    
                    <table class="table">
                        <tr>
                            <th style="width: 30%">Nhân viên giao hàng:</th>
                            <td><?php echo htmlspecialchars($employee['ho_ten']); ?></td>
                        </tr>
                        <tr>
                            <th>Số điện thoại:</th>
                            <td><?php echo htmlspecialchars($employee['sdt'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Phương tiện:</th>
                            <td>
                                <?php if ($vehicle): ?>
                                    <?php echo htmlspecialchars($vehicle['loai'] . ' - ' . $vehicle['bien_so']); ?>
                                <?php else: ?>
                                    <span class="text-muted">Chưa có phương tiện</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Chi tiết sản phẩm</h5>
        </div>
        <div class="card-body">
            <?php if (empty($orderDetails)): ?>
                <div class="alert alert-info">
                    Đơn hàng chưa có sản phẩm nào.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên sản phẩm</th>
                                <th>Số lượng</th>
                                <th>Đơn giá</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderDetails as $detail): ?>
                                <tr>
                                    <td><?php echo $detail['ct_id']; ?></td>
                                    <td><?php echo htmlspecialchars($detail['ten_san_pham']); ?></td>
                                    <td><?php echo $detail['so_luong']; ?></td>
                                    <td><?php echo formatCurrency($detail['don_gia']); ?></td>
                                    <td><?php echo formatCurrency($detail['so_luong'] * $detail['don_gia']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Tổng cộng:</th>
                                <th><?php echo formatCurrency($total); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
