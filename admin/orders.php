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

// Lấy danh sách đơn hàng với phân trang
try {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';

    $limit = 10;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $limit;

    $countQuery = "SELECT COUNT(*) as total FROM DonHang dh WHERE 1=1";
    $countParams = [];

    if (!empty($search)) {
        $countQuery .= " AND (dh.khach_hang LIKE ? OR dh.dia_chi LIKE ?)";
        $countParams[] = "%$search%";
        $countParams[] = "%$search%";
    }
    if (!empty($status)) {
        $countQuery .= " AND dh.trang_thai = ?";
        $countParams[] = $status;
    }

    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute($countParams);
    $totalOrders = $countStmt->fetch()['total'];

    $dataQuery = "
        SELECT dh.*, nv.ho_ten as nhanvien_name 
        FROM DonHang dh
        LEFT JOIN NhanVien nv ON dh.nhanvien_id = nv.nhanvien_id
        WHERE 1=1
    ";
    $dataParams = [];

    if (!empty($search)) {
        $dataQuery .= " AND (dh.khach_hang LIKE ? OR dh.dia_chi LIKE ?)";
        $dataParams[] = "%$search%";
        $dataParams[] = "%$search%";
    }
    if (!empty($status)) {
        $dataQuery .= " AND dh.trang_thai = ?";
        $dataParams[] = $status;
    }
    $dataQuery .= " ORDER BY dh.order_id DESC LIMIT $limit OFFSET $offset";

    $stmt = $conn->prepare($dataQuery);
    $stmt->execute($dataParams);
    $orders = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = 'Lỗi khi lấy danh sách đơn hàng: ' . $e->getMessage();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<style>
    .search-bar-wrapper {
        position: sticky;
        top: 70px;
        background: white;
        z-index: 1050;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #dee2e6;
    }
    .btn-add-order {
        font-size: 1rem;
        padding: 8px 16px;
        transition: background-color 0.3s ease;
    }
    .btn-add-order:hover {
        background-color: #004bcc;
        color: #fff;
    }
    table.table th, table.table td {
        vertical-align: middle;
        padding: 12px 15px;
    }
    table.table-hover tbody tr:hover {
        background-color: #e9f5ff;
    }
    .card {
        border-radius: 0.5rem;
        box-shadow: 0 2px 6px rgb(0 0 0 / 0.1);
    }
    .card-footer {
        background-color: #f8f9fa;
        border-top: 1px solid #dee2e6;
        padding: 12px 20px;
    }
    .page-link {
        cursor: pointer;
    }
    .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: white;
    }
    .page-desc {
        color: #6c757d;
        margin-top: -10px;
        margin-bottom: 15px;
        font-size: 0.9rem;
    }
    .btn-group-sm > .btn {
        padding: 0.25rem 0.4rem;
        font-size: 0.8rem;
        position: relative;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-1 border-bottom">
        <div>
            <h1 class="h2">Quản lý đơn hàng</h1>
            <p class="page-desc">Quản lý đơn hàng và trạng thái giao hàng.</p>
        </div>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="/delivery-management/admin/order-add.php" class="btn btn-primary btn-add-order">
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

    <div class="search-bar-wrapper mb-3">
        <form method="GET" action="" class="row g-3 align-items-center">
            <div class="col-md-4">
                <div class="input-group rounded border border-secondary">
                    <input type="text" class="form-control border-0" name="search" placeholder="Tìm kiếm theo khách hàng, địa chỉ..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" style="border-radius: 0.375rem 0 0 0.375rem;">
                    <button class="btn btn-outline-secondary" type="submit" style="border-radius: 0 0.375rem 0.375rem 0;">
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

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
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
                                <td colspan="7" class="text-center py-4">Không có đơn hàng nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo $order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['khach_hang']); ?></td>
                                    <td><?php echo htmlspecialchars(mb_strimwidth($order['dia_chi'], 0, 30, '...')); ?></td>
                                    <td><?php echo formatDate($order['ngay_giao']); ?></td>
                                    <td>
                                        <?php if ($order['nhanvien_id']): ?>
                                            <a href="/delivery-management/admin/employee-view.php?id=<?php echo $order['nhanvien_id']; ?>" title="Xem nhân viên">
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
                                        <div class="btn-group btn-group-sm" role="group" aria-label="Thao tác">
                                            <a href="/delivery-management/admin/order-view.php?id=<?php echo $order['order_id']; ?>" class="btn btn-info" title="Xem chi tiết">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="/delivery-management/admin/order-edit.php?id=<?php echo $order['order_id']; ?>" class="btn btn-primary" title="Chỉnh sửa">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="/delivery-management/admin/orders.php?delete=<?php echo $order['order_id']; ?>" class="btn btn-danger" title="Xóa đơn hàng" onclick="return confirmDelete('Bạn có chắc chắn muốn xóa đơn hàng này?')">
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

        <div class="card-footer d-flex justify-content-between align-items-center">
            <div>
                <strong>Tổng số đơn hàng: <?php echo $totalOrders ?? count($orders); ?></strong>
                <br>
                Hiển thị <?php echo min($offset + 1, $totalOrders ?? count($orders)); ?> -
                <?php echo min($offset + count($orders), $totalOrders ?? count($orders)); ?> trên tổng số <?php echo $totalOrders ?? count($orders); ?>
            </div>
            <?php if (isset($totalOrders) && $totalOrders > $limit): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination mb-0">
                    <?php
                    $totalPages = ceil($totalOrders / $limit);
                    $baseUrl = strtok($_SERVER["REQUEST_URI"], '?');
                    $queryParams = $_GET;
                    for ($i = 1; $i <= $totalPages; $i++) {
                        $queryParams['page'] = $i;
                        $link = $baseUrl . '?' . http_build_query($queryParams);
                        $activeClass = ($i == $page) ? 'active' : '';
                        echo '<li class="page-item ' . $activeClass . '"><a class="page-link" href="' . htmlspecialchars($link) . '">' . $i . '</a></li>';
                    }
                    ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ghi chú / cảnh báo -->
    <div class="mt-3">
        <div class="alert alert-warning" role="alert">
            <strong>Lưu ý:</strong> Thao tác xóa là vĩnh viễn. Vui lòng kiểm tra kỹ trước khi xóa đơn hàng.
        </div>
    </div>

    <!-- Gợi ý thao tác nhanh -->
    <div class="mb-4 text-muted" style="font-size: 0.9rem;">
        <i class="bi bi-info-circle"></i> 
        Nhấn <span class="badge bg-primary">Xem</span> để xem chi tiết, 
        <span class="badge bg-primary">Sửa</span> để cập nhật, 
        <span class="badge bg-danger">Xóa</span> để xoá đơn hàng.
    </div>
</div>

<script>
function confirmDelete(message) {
    return confirm(message || 'Bạn có chắc chắn muốn xóa?');
}

// Bật tooltip bootstrap cho title
document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'))
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
