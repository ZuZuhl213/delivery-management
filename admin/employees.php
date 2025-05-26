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

// Lấy danh sách nhân viên
try {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    if (!empty($search)) {
        $stmt = $conn->prepare("
            SELECT nv.*, pt.loai, pt.bien_so 
            FROM NhanVien nv
            LEFT JOIN PhuongTien pt ON nv.phuongtien_id = pt.phuongtien_id
            WHERE nv.ho_ten LIKE ? OR nv.email LIKE ? OR nv.username LIKE ?
            ORDER BY nv.nhanvien_id DESC
        ");
        $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    } else {
        $stmt = $conn->query("
            SELECT nv.*, pt.loai, pt.bien_so 
            FROM NhanVien nv
            LEFT JOIN PhuongTien pt ON nv.phuongtien_id = pt.phuongtien_id
            ORDER BY nv.nhanvien_id DESC
        ");
    }
    
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Lỗi khi lấy danh sách nhân viên: ' . $e->getMessage();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Quản lý nhân viên</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="/delivery-management/auth/register.php" class="btn btn-sm btn-primary">
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
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Tìm kiếm theo tên, email, username..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button class="btn btn-outline-secondary" type="submit">
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
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
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
                                <td colspan="8" class="text-center">Không có nhân viên nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td><?php echo $employee['nhanvien_id']; ?></td>
                                    <td><?php echo htmlspecialchars($employee['ho_ten']); ?></td>
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
                                            <span class="badge bg-success">
                                                <?php echo htmlspecialchars($employee['loai'] . ' - ' . $employee['bien_so']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Chưa gán</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="/delivery-management/admin/employee-edit.php?id=<?php echo $employee['nhanvien_id']; ?>" class="btn btn-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="/delivery-management/admin/employee-view.php?id=<?php echo $employee['nhanvien_id']; ?>" class="btn btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="/delivery-management/admin/employees.php?delete=<?php echo $employee['nhanvien_id']; ?>" class="btn btn-danger" onclick="return confirmDelete('Bạn có chắc chắn muốn xóa nhân viên này?')">
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
