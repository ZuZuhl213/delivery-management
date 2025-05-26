<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';

// Xử lý thêm thông tin lương
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nhanvien_id = $_POST['nhanvien_id'] ?? '';
    $thang = $_POST['thang'] ?? '';
    $luong_co_ban = $_POST['luong_co_ban'] ?? '';
    $luong_theo_order = $_POST['luong_theo_order'] ?? null;
    $ngay_tra = !empty($_POST['ngay_tra']) ? $_POST['ngay_tra'] : null;
    
    if (empty($nhanvien_id) || empty($thang) || empty($luong_co_ban)) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
    } elseif (!is_numeric($luong_co_ban) || $luong_co_ban < 0) {
        $error = 'Lương cơ bản phải là số dương';
    } elseif (!empty($luong_theo_order) && (!is_numeric($luong_theo_order) || $luong_theo_order < 0)) {
        $error = 'Lương theo đơn phải là số dương';
    } else {
        try {
            // Kiểm tra xem đã có thông tin lương của nhân viên trong tháng này chưa
            $stmt = $conn->prepare("SELECT COUNT(*) FROM Luong WHERE nhanvien_id = ? AND thang = ?");
            $stmt->execute([$nhanvien_id, $thang]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Đã tồn tại thông tin lương của nhân viên này trong tháng ' . $thang;
            } else {
                // Thêm thông tin lương mới
                $stmt = $conn->prepare("
                    INSERT INTO Luong (nhanvien_id, thang, luong_co_ban, luong_theo_order, ngay_tra) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nhanvien_id, $thang, $luong_co_ban, $luong_theo_order, $ngay_tra]);
                
                $success = 'Thêm thông tin lương thành công';
                
                // Xóa dữ liệu form sau khi thêm thành công
                $nhanvien_id = '';
                $thang = '';
                $luong_co_ban = '';
                $luong_theo_order = '';
                $ngay_tra = '';
            }
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

// Lấy danh sách nhân viên
try {
    $stmt = $conn->query("SELECT * FROM NhanVien ORDER BY ho_ten");
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Lỗi khi lấy danh sách nhân viên: ' . $e->getMessage();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Thêm thông tin lương</h1>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="nhanvien_id" class="form-label">Nhân viên <span class="text-danger">*</span></label>
                    <select class="form-select" id="nhanvien_id" name="nhanvien_id" required>
                        <option value="">-- Chọn nhân viên --</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo $employee['nhanvien_id']; ?>" <?php echo isset($nhanvien_id) && $nhanvien_id == $employee['nhanvien_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($employee['ho_ten']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="thang" class="form-label">Tháng (YYYY-MM) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="thang" name="thang" placeholder="VD: 2023-05" pattern="\d{4}-\d{2}" value="<?php echo isset($thang) ? htmlspecialchars($thang) : ''; ?>" required>
                    <div class="form-text">Định dạng: YYYY-MM (Năm-Tháng)</div>
                </div>
                
                <div class="mb-3">
                    <label for="luong_co_ban" class="form-label">Lương cơ bản (VNĐ) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="luong_co_ban" name="luong_co_ban" min="0" step="100000" value="<?php echo isset($luong_co_ban) ? htmlspecialchars($luong_co_ban) : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="luong_theo_order" class="form-label">Lương theo đơn (VNĐ)</label>
                    <input type="number" class="form-control" id="luong_theo_order" name="luong_theo_order" min="0" step="10000" value="<?php echo isset($luong_theo_order) ? htmlspecialchars($luong_theo_order) : ''; ?>">
                </div>
                
                <div class="mb-3">
                    <label for="ngay_tra" class="form-label">Ngày trả lương</label>
                    <input type="date" class="form-control" id="ngay_tra" name="ngay_tra" value="<?php echo isset($ngay_tra) ? htmlspecialchars($ngay_tra) : ''; ?>">
                    <div class="form-text">Để trống nếu chưa trả lương</div>
                </div>
                
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Thêm</button>
                    <a href="/delivery-management/admin/salaries.php" class="btn btn-secondary">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
