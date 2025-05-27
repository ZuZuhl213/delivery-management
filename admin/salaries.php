<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';

$limit = 10; // số bản ghi trên mỗi trang
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Xử lý xóa lương
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $conn->prepare("DELETE FROM Luong WHERE luong_id = ?");
        $stmt->execute([$id]);
        $success = 'Xóa thông tin lương thành công';
    } catch (PDOException $e) {
        $error = 'Lỗi khi xóa thông tin lương: ' . $e->getMessage();
    }
}

// Lấy danh sách lương có phân trang
try {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $month = isset($_GET['month']) ? $_GET['month'] : '';

    // Đếm tổng số bản ghi thỏa điều kiện
    $countQuery = "SELECT COUNT(*) FROM Luong l JOIN NhanVien nv ON l.nhanvien_id = nv.nhanvien_id WHERE 1=1";
    $countParams = [];
    if (!empty($search)) {
        $countQuery .= " AND nv.ho_ten LIKE ?";
        $countParams[] = "%$search%";
    }
    if (!empty($month)) {
        $countQuery .= " AND l.thang = ?";
        $countParams[] = $month;
    }
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute($countParams);
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);

    // Lấy dữ liệu thực tế theo phân trang
    $dataQuery = "
        SELECT l.*, nv.ho_ten 
        FROM Luong l
        JOIN NhanVien nv ON l.nhanvien_id = nv.nhanvien_id
        WHERE 1=1
    ";
    $dataParams = [];
    if (!empty($search)) {
        $dataQuery .= " AND nv.ho_ten LIKE ?";
        $dataParams[] = "%$search%";
    }
    if (!empty($month)) {
        $dataQuery .= " AND l.thang = ?";
        $dataParams[] = $month;
    }
    $dataQuery .= " ORDER BY l.thang DESC, nv.ho_ten LIMIT $limit OFFSET $offset";

    $stmt = $conn->prepare($dataQuery);
    $stmt->execute($dataParams);
    $salaries = $stmt->fetchAll();

    // Lấy danh sách tháng
    $stmt = $conn->query("SELECT DISTINCT thang FROM Luong ORDER BY thang DESC");
    $months = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $error = 'Lỗi khi lấy danh sách lương: ' . $e->getMessage();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<style>
    .search-bar-wrapper {
        position: sticky;
        top: 70px;
        background: #fff;
        z-index: 1050;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #dee2e6;
        margin-bottom: 1rem;
    }
    table.table th, table.table td {
        vertical-align: middle;
        padding: 12px 15px;
    }
    table.table-hover tbody tr:hover {
        background-color: #e9f5ff;
    }
    .btn-group-sm > .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.85rem;
        position: relative;
    }
    .btn-group-sm > .btn[title]:hover::after {
        content: attr(title);
        position: absolute;
        background: rgba(0,0,0,0.75);
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
    .pagination {
        margin-bottom: 0;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Quản lý lương</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="/delivery-management/admin/salary-add.php" class="btn btn-sm btn-primary">
                <i class="bi bi-plus"></i> Thêm thông tin lương
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="search-bar-wrapper">
        <form method="GET" action="" class="row g-3 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Tìm kiếm theo tên nhân viên..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i> Tìm kiếm
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <select class="form-select" name="month" onchange="this.form.submit()">
                    <option value="">Tất cả các tháng</option>
                    <?php foreach ($months as $m): ?>
                        <option value="<?php echo $m; ?>" <?php echo ($month === $m) ? 'selected' : ''; ?>><?php echo $m; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($search || $month): ?>
                <div class="col-md-2">
                    <a href="/delivery-management/admin/salaries.php" class="btn btn-outline-secondary w-100">Xóa bộ lọc</a>
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
                            <th>Nhân viên</th>
                            <th>Tháng</th>
                            <th>Lương cơ bản</th>
                            <th>Lương theo đơn</th>
                            <th>Tổng lương</th>
                            <th>Ngày trả</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($salaries)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">Không có thông tin lương nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($salaries as $salary): ?>
                                <tr>
                                    <td><?php echo $salary['luong_id']; ?></td>
                                    <td><?php echo htmlspecialchars($salary['ho_ten']); ?></td>
                                    <td><?php echo htmlspecialchars($salary['thang']); ?></td>
                                    <td><?php echo formatCurrency($salary['luong_co_ban']); ?></td>
                                    <td><?php echo formatCurrency($salary['luong_theo_order'] ?? 0); ?></td>
                                    <td><?php echo formatCurrency($salary['tong_luong']); ?></td>
                                    <td><?php echo $salary['ngay_tra'] ? formatDate($salary['ngay_tra']) : '<span class="text-danger">Chưa trả</span>'; ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group" aria-label="Thao tác">
                                            <a href="/delivery-management/admin/salary-edit.php?id=<?php echo $salary['luong_id']; ?>" class="btn btn-primary" title="Sửa thông tin lương">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="/delivery-management/admin/salaries.php?delete=<?php echo $salary['luong_id']; ?>" class="btn btn-danger" title="Xóa thông tin lương" onclick="return confirmDelete('Bạn có chắc chắn muốn xóa thông tin lương này?')">
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

        <?php if ($totalPages > 1): ?>
        <div class="card-footer d-flex justify-content-center">
            <nav aria-label="Page navigation">
                <ul class="pagination mb-0">
                    <?php
                    $baseUrl = strtok($_SERVER["REQUEST_URI"], '?');
                    $queryParams = $_GET;
                    for ($i = 1; $i <= $totalPages; $i++):
                        $queryParams['page'] = $i;
                        $link = $baseUrl . '?' . http_build_query($queryParams);
                        $activeClass = ($i == $page) ? 'active' : '';
                    ?>
                        <li class="page-item <?php echo $activeClass; ?>">
                            <a class="page-link" href="<?php echo htmlspecialchars($link); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>

    <!-- Ghi chú / cảnh báo -->
    <div class="mt-3">
        <div class="alert alert-warning" role="alert">
            <strong>Lưu ý:</strong> Thao tác xóa là vĩnh viễn. Vui lòng kiểm tra kỹ trước khi xóa thông tin lương.
        </div>
    </div>

    <!-- Gợi ý thao tác nhanh -->
    <div class="mb-4 text-muted" style="font-size: 0.9rem;">
        <i class="bi bi-info-circle"></i> 
        Nhấn <span class="badge bg-primary">Sửa</span> để chỉnh sửa, 
        <span class="badge bg-danger">Xóa</span> để xóa thông tin lương.
    </div>
</div>

<script>
function confirmDelete(message) {
    return confirm(message || 'Bạn có chắc chắn muốn xóa?');
}

document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.forEach(function (el) {
        if(typeof bootstrap !== 'undefined') {
            new bootstrap.Tooltip(el);
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
