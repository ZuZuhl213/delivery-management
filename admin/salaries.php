<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';

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

// Lấy danh sách lương
try {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $month = isset($_GET['month']) ? $_GET['month'] : '';
    
    $query = "
        SELECT l.*, nv.ho_ten 
        FROM Luong l
        JOIN NhanVien nv ON l.nhanvien_id = nv.nhanvien_id
        WHERE 1=1
    ";
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND nv.ho_ten LIKE ?";
        $params[] = "%$search%";
    }
    
    if (!empty($month)) {
        $query .= " AND l.thang = ?";
        $params[] = $month;
    }
    
    $query .= " ORDER BY l.thang DESC, nv.ho_ten";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $salaries = $stmt->fetchAll();
    
    // Lấy danh sách nhân viên
    $stmt = $conn->query("SELECT * FROM NhanVien ORDER BY ho_ten");
    $employees = $stmt->fetchAll();
    
    // Lấy danh sách tháng
    $stmt = $conn->query("SELECT DISTINCT thang FROM Luong ORDER BY thang DESC");
    $months = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $error = 'Lỗi khi lấy danh sách lương: ' . $e->getMessage();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Quản lý lương</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="/delivery-management/admin/salary-add.php" class="btn btn-sm btn-primary">
                <i class="bi bi-plus"></i> Thêm thông tin lương
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
                        <input type="text" class="form-control" name="search" placeholder="Tìm kiếm theo tên nhân viên..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i> Tìm kiếm
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="month" onchange="this.form.submit()">
                        <option value="">Tất cả các tháng</option>
                        <?php foreach ($months as $m): ?>
                            <option value="<?php echo $m; ?>" <?php echo isset($_GET['month']) && $_GET['month'] === $m ? 'selected' : ''; ?>>
                                <?php echo $m; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (isset($_GET['search']) || isset($_GET['month'])): ?>
                    <div class="col-md-2">
                        <a href="/delivery-management/admin/salaries.php" class="btn btn-outline-secondary w-100">Xóa bộ lọc</a>
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
                                <td colspan="8" class="text-center">Không có thông tin lương nào</td>
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
                                        <div class="btn-group btn-group-sm">
                                            <a href="/delivery-management/admin/salary-edit.php?id=<?php echo $salary['luong_id']; ?>" class="btn btn-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="/delivery-management/admin/salaries.php?delete=<?php echo $salary['luong_id']; ?>" class="btn btn-danger" onclick="return confirmDelete('Bạn có chắc chắn muốn xóa thông tin lương này?')">
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
