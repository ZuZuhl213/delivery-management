<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';

// Xử lý xóa đơn hàng
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $conn->prepare("DELETE FROM DonHang WHERE order_id = ?");
        $stmt->execute([$id]);
        $success = 'Xóa đơn hàng thành công';
    } catch (PDOException $e) {
        $error = 'Lỗi khi xóa đơn hàng: ' . $e->getMessage();
    }
}

// Lấy danh sách đơn hàng
try {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    
    $query = "
        SELECT dh.*, nv.ho_ten as nhanvien_name 
        FROM DonHang dh
        LEFT JOIN NhanVien nv ON dh.nhanvien_id = nv.nhanvien_id
        WHERE 1=1
    ";
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (dh.khach_hang LIKE ? OR dh.dia_chi LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($status)) {
        $query .= " AND dh.trang_thai = ?";
        $params[] = $status;
    }
    
    $query .= " ORDER BY dh.order_id DESC";
    
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
        <h1 class="h2">Quản lý đơn hàng</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="/delivery-management/admin/order-add.php" class="btn btn-sm btn-primary">
                <i class="bi bi-plus"></i> Tạo đơn hàng
            </a>
        </div>
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
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Tìm kiếm theo khách hàng, địa chỉ..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i> Tìm kiếm
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="status" onchange="this.form.submit()">
                        <option value="" <?php echo !isset($_GET['status']) || $_GET['status'] === '' ? 'selected' : ''; ?>>Tất cả trạng thái</option>
                        <option value="dang_giao" <?php echo isset($_GET['status']) && $_GET['status'] === 'dang_giao' ? 'selected' : ''; ?>>Đang giao</option>
                        <option value="hoan_thanh" <?php echo isset($_GET['status']) && $_GET['status'] === 'hoan_thanh' ? 'selected' : ''; ?>>Hoàn thành</option>
                        <option value="huy" <?php echo isset($_GET['status']) && $_GET['status'] === 'huy' ? 'selected' : ''; ?>>Hủy</option>
                    </select>
                </div>
                <?php if (isset($_GET['search']) || isset($_GET['status'])): ?>
                    <div class="col-md-2">
                        <a href="/delivery-management/admin/orders.php" class="btn btn-outline-secondary w-100">Xóa bộ lọc</a>
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
                            <th>Nhân viên</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Không có đơn hàng nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo $order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['khach_hang']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($order['dia_chi'], 0, 30) . (strlen($order['dia_chi']) > 30 ? '...' : '')); ?></td>
                                    <td><?php echo formatDate($order['ngay_giao']); ?></td>
                                    <td>
                                        <?php if ($order['nhanvien_id']): ?>
                                            <a href="/delivery-management/admin/employee-view.php?id=<?php echo $order['nhanvien_id']; ?>">
                                                <?php echo htmlspecialchars($order['nhanvien_name']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Chưa gán</span>
                                        <?php endif; ?>
                                    </td>
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
                                            <a href="/delivery-management/admin/order-view.php?id=<?php echo $order['order_id']; ?>" class="btn btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="/delivery-management/admin/order-edit.php?id=<?php echo $order['order_id']; ?>" class="btn btn-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="/delivery-management/admin/orders.php?delete=<?php echo $order['order_id']; ?>" class="btn btn-danger" onclick="return confirmDelete('Bạn có chắc chắn muốn xóa đơn hàng này?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
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
