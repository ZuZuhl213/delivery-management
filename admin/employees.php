<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';

// Xử lý xóa nhân viên
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $conn->prepare("DELETE FROM NhanVien WHERE nhanvien_id = ?");
        $stmt->execute([$id]);
        $success = 'Xóa nhân viên thành công';
    } catch (PDOException $e) {
        $error = 'Lỗi khi xóa nhân viên: ' . $e->getMessage();
    }
}

// Lấy danh sách nhân viên và phân trang
try {
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    $limit = 10; // số bản ghi mỗi trang
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $limit;

    // Đếm tổng số nhân viên (có filter nếu có)
    if (!empty($search)) {
        $countStmt = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM NhanVien nv
            WHERE nv.ho_ten LIKE :search OR nv.email LIKE :search OR nv.username LIKE :search
        ");
        $countStmt->execute(['search' => "%$search%"]);
    } else {
        $countStmt = $conn->query("SELECT COUNT(*) as total FROM NhanVien");
    }
    $totalEmployees = $countStmt->fetch()['total'];

    // Ép kiểu int cho limit và offset để tránh lỗi SQL
    $limit = (int)$limit;
    $offset = (int)$offset;

    // Lấy danh sách nhân viên phân trang, chèn limit offset trực tiếp vào query
    if (!empty($search)) {
        $sql = "
            SELECT nv.*, pt.loai, pt.bien_so 
            FROM NhanVien nv
            LEFT JOIN PhuongTien pt ON nv.phuongtien_id = pt.phuongtien_id
            WHERE nv.ho_ten LIKE :search OR nv.email LIKE :search OR nv.username LIKE :search
            ORDER BY nv.nhanvien_id DESC
            LIMIT $limit OFFSET $offset
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['search' => "%$search%"]);
    } else {
        $sql = "
            SELECT nv.*, pt.loai, pt.bien_so 
            FROM NhanVien nv
            LEFT JOIN PhuongTien pt ON nv.phuongtien_id = pt.phuongtien_id
            ORDER BY nv.nhanvien_id DESC
            LIMIT $limit OFFSET $offset
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
    }

    $employees = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = 'Lỗi khi lấy danh sách nhân viên: ' . $e->getMessage();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<style>
    /* Sticky tìm kiếm */
    .search-bar-wrapper {
        position: sticky;
        top: 70px;
        background: white;
        z-index: 1050;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #dee2e6;
    }

    /* Avatar chữ cái */
    .avatar {
        width: 36px;
        height: 36px;
        background-color: #0d6efd;
        color: white;
        font-weight: 700;
        font-size: 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        margin-right: 10px;
        user-select: none;
        text-transform: uppercase;
    }

    /* Bảng padding cột */
    table.table th, table.table td {
        vertical-align: middle;
        padding: 12px 15px;
    }

    /* Nút thao tác nhỏ hơn */
    .btn-group-sm > .btn {
        padding: 0.25rem 0.4rem;
        font-size: 0.8rem;
        position: relative;
    }

    /* Tooltip cho nút */
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

    /* Card bảng bóng nhẹ và bo góc */
    .card {
        border-radius: 0.5rem;
        box-shadow: 0 2px 6px rgb(0 0 0 / 0.1);
    }

    /* Bảng hover màu nền nhẹ */
    table.table-hover tbody tr:hover {
        background-color: #e9f5ff;
    }

    /* Mô tả nhỏ dưới tiêu đề */
    .page-desc {
        color: #6c757d;
        margin-top: -10px;
        margin-bottom: 15px;
        font-size: 0.9rem;
    }

    /* Nút thêm nhân viên to hơn, hover */
    .btn-add-employee {
        font-size: 1rem;
        padding: 8px 16px;
        transition: background-color 0.3s ease;
    }
    .btn-add-employee:hover {
        background-color: #004bcc;
        color: #fff;
    }

    /* Footer card phân trang + thống kê */
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
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-1 border-bottom">
        <div>
            <h1 class="h2">Quản lý nhân viên</h1>
            <p class="page-desc">Quản lý thông tin nhân viên, vai trò và phương tiện đi lại.</p>
        </div>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="/delivery-management/auth/register.php" class="btn btn-primary btn-add-employee">
                <i class="bi bi-plus"></i> Thêm nhân viên
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
            <div class="col-md-6">
                <div class="input-group rounded border border-secondary">
                    <input type="text" class="form-control border-0" name="search" placeholder="Tìm kiếm theo tên, email, username..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" style="border-radius: 0.375rem 0 0 0.375rem;">
                    <button class="btn btn-outline-secondary" type="submit" style="border-radius: 0 0.375rem 0.375rem 0;">
                        <i class="bi bi-search"></i> Tìm kiếm
                    </button>
                </div>
            </div>
            <?php if (isset($_GET['search'])): ?>
                <div class="col-md-2">
                    <a href="/delivery-management/admin/employees.php" class="btn btn-outline-secondary w-100">Xóa bộ lọc</a>
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
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>SĐT</th>
                            <th>Username</th>
                            <th>Vai trò</th>
                            <th>Phương tiện</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">Không có nhân viên nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td><?php echo $employee['nhanvien_id']; ?></td>
                                    <td class="d-flex align-items-center">
                                        <div class="avatar" title="<?php echo htmlspecialchars($employee['ho_ten']); ?>">
                                            <?php 
                                            $words = explode(' ', trim($employee['ho_ten']));
                                            $initials = '';
                                            foreach ($words as $w) {
                                                $initials .= mb_substr($w, 0, 1);
                                            }
                                            echo mb_strtoupper($initials);
                                            ?>
                                        </div>
                                        <?php echo htmlspecialchars($employee['ho_ten']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['sdt'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($employee['username']); ?></td>
                                    <td>
                                        <?php if ($employee['role'] === 'admin'): ?>
                                            <span class="badge bg-danger">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Nhân viên</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($employee['phuongtien_id']): ?>
                                            <span class="badge bg-success" title="<?php echo htmlspecialchars($employee['loai'] . ' - ' . $employee['bien_so']); ?>">
                                                <?php echo htmlspecialchars($employee['loai'] . ' - ' . $employee['bien_so']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Chưa gán</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group" aria-label="Thao tác">
                                            <a href="/delivery-management/admin/employee-edit.php?id=<?php echo $employee['nhanvien_id']; ?>" class="btn btn-primary" title="Sửa">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="/delivery-management/admin/employee-view.php?id=<?php echo $employee['nhanvien_id']; ?>" class="btn btn-info" title="Xem">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="/delivery-management/admin/employees.php?delete=<?php echo $employee['nhanvien_id']; ?>" class="btn btn-danger" title="Xóa" onclick="return confirmDelete('Bạn có chắc chắn muốn xóa nhân viên này?')">
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

        <!-- Phần footer bảng: thống kê + phân trang -->
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div>
                <strong>Tổng số nhân viên: <?php echo $totalEmployees; ?></strong>
                <br>
                Hiển thị <?php echo min($offset + 1, $totalEmployees); ?> -
                <?php echo min($offset + count($employees), $totalEmployees); ?> trên tổng số <?php echo $totalEmployees; ?>
            </div>

            <nav aria-label="Page navigation">
                <ul class="pagination mb-0">
                    <?php
                    $totalPages = ceil($totalEmployees / $limit);
                    $baseUrl = strtok($_SERVER["REQUEST_URI"], '?'); // url không có query
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

    <!-- Ghi chú / cảnh báo -->
    <div class="mt-3">
        <div class="alert alert-warning" role="alert">
            <strong>Lưu ý:</strong> Hãy kiểm tra kỹ thông tin trước khi xóa nhân viên. Thao tác xóa sẽ không thể hoàn tác.
        </div>
    </div>

    <!-- Gợi ý thao tác nhanh -->
    <div class="mb-4 text-muted" style="font-size: 0.9rem;">
        <i class="bi bi-info-circle"></i> Nhấn <span class="badge bg-primary">Sửa</span> để chỉnh sửa, <span class="badge bg-danger">Xóa</span> để xoá nhân viên. Bạn có thể tìm kiếm và phân trang để quản lý dễ dàng hơn.
    </div>
</div>

<script>
    function confirmDelete(message) {
        return confirm(message || 'Bạn có chắc chắn muốn xóa?');
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
