<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';

// Xử lý POST: gán hoặc trả phương tiện
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'remove') {
        $nhanvien_id = $_POST['nhanvien_id'] ?? '';
        if (empty($nhanvien_id)) {
            $error = 'Thông tin không hợp lệ.';
        } else {
            try {
                $stmt = $conn->prepare("UPDATE NhanVien SET phuongtien_id = NULL WHERE nhanvien_id = ?");
                $stmt->execute([$nhanvien_id]);
                $success = 'Trả phương tiện thành công.';
                header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/delivery-management/admin/vehicles.php'));
                exit();
            } catch (PDOException $e) {
                $error = 'Lỗi hệ thống: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'assign') {
        $nhanvien_id = $_POST['nhanvien_id'] ?? '';
        $phuongtien_id = $_POST['phuongtien_id'] ?? '';

        if (empty($nhanvien_id) || empty($phuongtien_id)) {
            $error = 'Vui lòng chọn nhân viên và phương tiện.';
        } else {
            try {
                // Kiểm tra phương tiện đã được gán chưa
                $stmt = $conn->prepare("SELECT COUNT(*) FROM NhanVien WHERE phuongtien_id = ? AND nhanvien_id != ?");
                $stmt->execute([$phuongtien_id, $nhanvien_id]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Phương tiện đã được gán cho nhân viên khác.';
                } else {
                    // Kiểm tra nhân viên đã có phương tiện chưa
                    $stmt = $conn->prepare("SELECT phuongtien_id FROM NhanVien WHERE nhanvien_id = ?");
                    $stmt->execute([$nhanvien_id]);
                    $current = $stmt->fetchColumn();
                    if ($current) {
                        $error = 'Nhân viên này đã có phương tiện. Vui lòng trả phương tiện trước khi gán mới.';
                    } else {
                        $stmt = $conn->prepare("UPDATE NhanVien SET phuongtien_id = ? WHERE nhanvien_id = ?");
                        $stmt->execute([$phuongtien_id, $nhanvien_id]);
                        $success = 'Gán phương tiện thành công.';
                        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/delivery-management/admin/vehicles.php'));
                        exit();
                    }
                }
            } catch (PDOException $e) {
                $error = 'Lỗi hệ thống: ' . $e->getMessage();
            }
        }
    }
}

// Lấy danh sách nhân viên chưa có phương tiện
try {
    $stmt = $conn->query("SELECT * FROM NhanVien WHERE phuongtien_id IS NULL ORDER BY ho_ten");
    $employees = $stmt->fetchAll();

    $stmt = $conn->query("SELECT * FROM PhuongTien WHERE phuongtien_id NOT IN (SELECT phuongtien_id FROM NhanVien WHERE phuongtien_id IS NOT NULL) ORDER BY loai, bien_so");
    $vehicles = $stmt->fetchAll();

    $employee = null;
    if (isset($_GET['nhanvien_id']) && is_numeric($_GET['nhanvien_id'])) {
        $stmt = $conn->prepare("SELECT * FROM NhanVien WHERE nhanvien_id = ?");
        $stmt->execute([$_GET['nhanvien_id']]);
        $employee = $stmt->fetch();
    }

    $vehicle = null;
    if (isset($_GET['phuongtien_id']) && is_numeric($_GET['phuongtien_id'])) {
        $stmt = $conn->prepare("SELECT * FROM PhuongTien WHERE phuongtien_id = ?");
        $stmt->execute([$_GET['phuongtien_id']]);
        $vehicle = $stmt->fetch();
    }
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Gán phương tiện</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="assign">

                <div class="mb-3">
                    <label for="nhanvien_id" class="form-label">Nhân viên <span class="text-danger">*</span></label>
                    <select class="form-select" id="nhanvien_id" name="nhanvien_id" required <?php echo $employee ? 'disabled' : ''; ?>>
                        <option value="">-- Chọn nhân viên --</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['nhanvien_id']; ?>" <?php echo $employee && $employee['nhanvien_id'] == $emp['nhanvien_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['ho_ten'] . ' (' . $emp['username'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($employee): ?>
                        <input type="hidden" name="nhanvien_id" value="<?php echo $employee['nhanvien_id']; ?>">
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="phuongtien_id" class="form-label">Phương tiện <span class="text-danger">*</span></label>
                    <select class="form-select" id="phuongtien_id" name="phuongtien_id" required <?php echo $vehicle ? 'disabled' : ''; ?>>
                        <option value="">-- Chọn phương tiện --</option>
                        <?php foreach ($vehicles as $veh): ?>
                            <option value="<?php echo $veh['phuongtien_id']; ?>" <?php echo $vehicle && $vehicle['phuongtien_id'] == $veh['phuongtien_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($veh['loai'] . ' - ' . $veh['bien_so']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($vehicle): ?>
                        <input type="hidden" name="phuongtien_id" value="<?php echo $vehicle['phuongtien_id']; ?>">
                    <?php endif; ?>
                </div>

                <div class="mb-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Gán phương tiện</button>
                    <a href="<?php echo htmlspecialchars($_SERVER['HTTP_REFERER'] ?? '/delivery-management/admin/vehicles.php'); ?>" class="btn btn-secondary">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
