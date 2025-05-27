<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';

// Xử lý thêm phương tiện mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $loai = $_POST['loai'] ?? '';
    $bien_so = $_POST['bien_so'] ?? '';
    
    if (empty($loai) || empty($bien_so)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } else {
        try {
            // Kiểm tra biển số đã tồn tại chưa
            $stmt = $conn->prepare("SELECT COUNT(*) FROM PhuongTien WHERE bien_so = ?");
            $stmt->execute([$bien_so]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Biển số đã tồn tại';
            } else {
                // Thêm phương tiện mới
                $stmt = $conn->prepare("INSERT INTO PhuongTien (loai, bien_so) VALUES (?, ?)");
                $stmt->execute([$loai, $bien_so]);
                $success = 'Thêm phương tiện thành công';
            }
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

// Xử lý xóa phương tiện
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM NhanVien WHERE phuongtien_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Không thể xóa phương tiện đang được sử dụng';
        } else {
            $stmt = $conn->prepare("DELETE FROM PhuongTien WHERE phuongtien_id = ?");
            $stmt->execute([$id]);
            $success = 'Xóa phương tiện thành công';
        }
    } catch (PDOException $e) {
        $error = 'Lỗi khi xóa phương tiện: ' . $e->getMessage();
    }
}

// Lấy danh sách phương tiện với phân trang
try {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $filter = isset($_GET['filter']) ? $_GET['filter'] : '';
    
    $limit = 10;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $limit;
    
    // Đếm tổng số phương tiện
    $countQuery = "SELECT COUNT(*) as total FROM PhuongTien pt LEFT JOIN NhanVien nv ON pt.phuongtien_id = nv.phuongtien_id WHERE 1=1";
    $countParams = [];
    
    if (!empty($search)) {
        $countQuery .= " AND (pt.loai LIKE ? OR pt.bien_so LIKE ?)";
        $countParams[] = "%$search%";
        $countParams[] = "%$search%";
    }
    if ($filter === 'available') {
        $countQuery .= " AND nv.nhanvien_id IS NULL";
    } elseif ($filter === 'assigned') {
        $countQuery .= " AND nv.nhanvien_id IS NOT NULL";
    }
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute($countParams);
    $totalVehicles = $countStmt->fetch()['total'];
    
    // Lấy dữ liệu phân trang
    $dataQuery = "
        SELECT pt.*, nv.nhanvien_id, nv.ho_ten 
        FROM PhuongTien pt
        LEFT JOIN NhanVien nv ON pt.phuongtien_id = nv.phuongtien_id
        WHERE 1=1
    ";
    $dataParams = [];
    if (!empty($search)) {
        $dataQuery .= " AND (pt.loai LIKE ? OR pt.bien_so LIKE ?)";
        $dataParams[] = "%$search%";
        $dataParams[] = "%$search%";
    }
    if ($filter === 'available') {
        $dataQuery .= " AND nv.nhanvien_id IS NULL";
    } elseif ($filter === 'assigned') {
        $dataQuery .= " AND nv.nhanvien_id IS NOT NULL";
    }
    $dataQuery .= " ORDER BY pt.phuongtien_id DESC LIMIT $limit OFFSET $offset";
    
    $stmt = $conn->prepare($dataQuery);
    $stmt->execute($dataParams);
    $vehicles = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Lỗi khi lấy danh sách phương tiện: ' . $e->getMessage();
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
    .btn-add-vehicle {
        font-size: 1rem;
        padding: 8px 16px;
        transition: background-color 0.3s ease;
    }
    .btn-add-vehicle:hover {
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
    .btn-group-sm > .btn[title]:hover::after {
        content: attr(title);
        position: absolute;
        background: #000000cc;
        color: white;
        padding: 3px 6px;
        font-size: 0.75rem;
        border-radius: 4px;
        top: -30px;
        white-space: nowrap;
        left: 50%;
        transform: translateX(-50%);
        pointer-events: none;
        z-index: 1000;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-1 border-bottom">
        <div>
            <h1 class="h2">Quản lý phương tiện</h1>
            <p class="page-desc">Quản lý các phương tiện và trạng thái sử dụng.</p>
        </div>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-primary btn-add-vehicle" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                <i class="bi bi-plus"></i> Thêm phương tiện
            </button>
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
                    <input type="text" class="form-control border-0" name="search" placeholder="Tìm kiếm theo loại, biển số..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" style="border-radius: 0.375rem 0 0 0.375rem;">
                    <button class="btn btn-outline-secondary" type="submit" style="border-radius: 0 0.375rem 0.375rem 0;">
                        <i class="bi bi-search"></i> Tìm kiếm
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <select class="form-select" name="filter" onchange="this.form.submit()">
                    <option value="" <?php echo !isset($_GET['filter']) || $_GET['filter'] === '' ? 'selected' : ''; ?>>Tất cả phương tiện</option>
                    <option value="available" <?php echo isset($_GET['filter']) && $_GET['filter'] === 'available' ? 'selected' : ''; ?>>Chưa được gán</option>
                    <option value="assigned" <?php echo isset($_GET['filter']) && $_GET['filter'] === 'assigned' ? 'selected' : ''; ?>>Đã được gán</option>
                </select>
            </div>
            <?php if (isset($_GET['search']) || isset($_GET['filter'])): ?>
                <div class="col-md-2">
                    <a href="/delivery-management/admin/vehicles.php" class="btn btn-outline-secondary w-100">Xóa bộ lọc</a>
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
                            <th>Loại</th>
                            <th>Biển số</th>
                            <th>Trạng thái</th>
                            <th>Nhân viên sử dụng</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vehicles)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">Không có phương tiện nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <tr>
                                    <td><?php echo $vehicle['phuongtien_id']; ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['loai']); ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['bien_so']); ?></td>
                                    <td>
                                        <?php if ($vehicle['nhanvien_id']): ?>
                                            <span class="badge bg-warning">Đang sử dụng</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Sẵn sàng</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($vehicle['nhanvien_id']): ?>
                                            <a href="/delivery-management/admin/employee-view.php?id=<?php echo $vehicle['nhanvien_id']; ?>">
                                                <?php echo htmlspecialchars($vehicle['ho_ten']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Chưa gán</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group" aria-label="Thao tác">
                                            <a href="/delivery-management/admin/vehicle-edit.php?id=<?php echo $vehicle['phuongtien_id']; ?>" class="btn btn-primary" title="Sửa">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if (!$vehicle['nhanvien_id']): ?>
                                                <a href="/delivery-management/admin/vehicles.php?delete=<?php echo $vehicle['phuongtien_id']; ?>" class="btn btn-danger" title="Xóa" onclick="return confirmDelete('Bạn có chắc chắn muốn xóa phương tiện này?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                                <a href="/delivery-management/admin/vehicle-assign.php?phuongtien_id=<?php echo $vehicle['phuongtien_id']; ?>" class="btn btn-success" title="Gán nhân viên">
                                                    <i class="bi bi-person-plus"></i>
                                                </a>
                                            <?php else: ?>
                                                <form method="POST" action="/delivery-management/admin/vehicle-assign.php" style="display: inline;">
                                                    <input type="hidden" name="nhanvien_id" value="<?php echo $vehicle['nhanvien_id']; ?>">
                                                    <input type="hidden" name="action" value="remove">
                                                    <button type="submit" class="btn btn-warning" title="Hủy gán nhân viên">
                                                        <i class="bi bi-person-dash"></i>
                                                    </button>
                                                </form>
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

        <!-- Footer bảng: thống kê + phân trang -->
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div>
                <strong>Tổng số phương tiện: <?php echo $totalVehicles; ?></strong>
                <br>
                Hiển thị <?php echo min($offset + 1, $totalVehicles); ?> -
                <?php echo min($offset + count($vehicles), $totalVehicles); ?> trên tổng số <?php echo $totalVehicles; ?>
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination mb-0">
                    <?php
                    $totalPages = ceil($totalVehicles / $limit);
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
        </div>
    </div>

    <!-- Ghi chú -->
    <div class="mt-3">
        <div class="alert alert-warning" role="alert">
            <strong>Lưu ý:</strong> Không thể xóa phương tiện đang được sử dụng. Kiểm tra kỹ trước khi xóa.
        </div>
    </div>

    <!-- Gợi ý thao tác nhanh -->
    <div class="mb-4 text-muted" style="font-size: 0.9rem;">
        <i class="bi bi-info-circle"></i> Nhấn <span class="badge bg-primary">Sửa</span> để chỉnh sửa, <span class="badge bg-danger">Xóa</span> để xoá (nếu không đang sử dụng), <span class="badge bg-success">Gán</span> hoặc <span class="badge bg-warning">Hủy gán</span> nhân viên.
    </div>
</div>

<!-- Modal thêm phương tiện -->
<div class="modal fade" id="addVehicleModal" tabindex="-1" aria-labelledby="addVehicleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title" id="addVehicleModalLabel">Thêm phương tiện mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="loai" class="form-label">Loại phương tiện <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="loai" name="loai" required>
                    </div>
                    <div class="mb-3">
                        <label for="bien_so" class="form-label">Biển số <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="bien_so" name="bien_so" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Thêm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function confirmDelete(message) {
        return confirm(message || 'Bạn có chắc chắn muốn xóa?');
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
