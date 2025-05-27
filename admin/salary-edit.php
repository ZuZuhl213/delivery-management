<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /delivery-management/admin/salaries.php");
    exit();
}

$id = $_GET['id'];

try {
    $stmt = $conn->prepare("
        SELECT l.*, nv.ho_ten 
        FROM Luong l
        JOIN NhanVien nv ON l.nhanvien_id = nv.nhanvien_id
        WHERE l.luong_id = ?
    ");
    $stmt->execute([$id]);
    $salary = $stmt->fetch();

    if (!$salary) {
        header("Location: /delivery-management/admin/salaries.php");
        exit();
    }
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}

// Xử lý cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $luong_co_ban = $_POST['luong_co_ban'] ?? '';
    $luong_theo_order = $_POST['luong_theo_order'] ?? null;
    $ngay_tra = !empty($_POST['ngay_tra']) ? $_POST['ngay_tra'] : null;

    if (empty($luong_co_ban)) {
        $error = 'Vui lòng nhập lương cơ bản';
    } elseif (!is_numeric($luong_co_ban) || $luong_co_ban < 0) {
        $error = 'Lương cơ bản phải là số dương';
    } elseif (!empty($luong_theo_order) && (!is_numeric($luong_theo_order) || $luong_theo_order < 0)) {
        $error = 'Lương theo đơn phải là số dương';
    } else {
        try {
            $stmt = $conn->prepare("
                UPDATE Luong 
                SET luong_co_ban = ?, luong_theo_order = ?, ngay_tra = ?
                WHERE luong_id = ?
            ");
            $stmt->execute([$luong_co_ban, $luong_theo_order, $ngay_tra, $id]);
            $success = 'Cập nhật thông tin lương thành công';

            // Cập nhật lại dữ liệu để hiển thị
            $stmt = $conn->prepare("
                SELECT l.*, nv.ho_ten 
                FROM Luong l
                JOIN NhanVien nv ON l.nhanvien_id = nv.nhanvien_id
                WHERE l.luong_id = ?
            ");
            $stmt->execute([$id]);
            $salary = $stmt->fetch();

        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

// Đặt giá trị input theo dữ liệu mới (giữ dữ liệu sau submit)
$luong_co_ban_val = $_POST['luong_co_ban'] ?? $salary['luong_co_ban'];
$luong_theo_order_val = $_POST['luong_theo_order'] ?? $salary['luong_theo_order'];
$ngay_tra_val = $_POST['ngay_tra'] ?? $salary['ngay_tra'];
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Chỉnh sửa thông tin lương</h1>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Nhân viên</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($salary['ho_ten']); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tháng</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($salary['thang']); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label for="luong_co_ban" class="form-label">Lương cơ bản (VNĐ) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="luong_co_ban" name="luong_co_ban" min="0" step="100000" value="<?php echo htmlspecialchars($luong_co_ban_val); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="luong_theo_order" class="form-label">Lương theo đơn (VNĐ)</label>
                    <input type="number" class="form-control" id="luong_theo_order" name="luong_theo_order" min="0" step="10000" value="<?php echo htmlspecialchars($luong_theo_order_val); ?>">
                </div>

                <div class="mb-3">
                    <label for="ngay_tra" class="form-label">Ngày trả lương</label>
                    <input type="date" class="form-control" id="ngay_tra" name="ngay_tra" value="<?php echo htmlspecialchars($ngay_tra_val); ?>">
                    <div class="form-text">Để trống nếu chưa trả lương</div>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                    <a href="/delivery-management/admin/salaries.php" class="btn btn-secondary">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
