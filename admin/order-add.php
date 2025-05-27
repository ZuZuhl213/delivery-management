<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';
$order_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $khach_hang = $_POST['khach_hang'] ?? '';
    $dia_chi = $_POST['dia_chi'] ?? '';
    $ngay_giao = $_POST['ngay_giao'] ?? '';
    $nhanvien_id = !empty($_POST['nhanvien_id']) ? $_POST['nhanvien_id'] : null;
    $trang_thai = $_POST['trang_thai'] ?? 'dang_giao';

    if (empty($khach_hang) || empty($dia_chi) || empty($ngay_giao)) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
    } else {
        try {
            $stmt = $conn->prepare("
                INSERT INTO DonHang (khach_hang, dia_chi, ngay_giao, nhanvien_id, trang_thai) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$khach_hang, $dia_chi, $ngay_giao, $nhanvien_id, $trang_thai]);
            $order_id = $conn->lastInsertId();
            $success = 'Tạo đơn hàng thành công. Bạn có thể thêm sản phẩm vào đơn hàng.';
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

<style>
.btn-loading {
    position: relative;
    pointer-events: none;
    opacity: 0.7;
}
.btn-loading .spinner-border {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 1rem;
    height: 1rem;
    margin-top: -0.5rem;
    margin-left: -0.5rem;
    border-width: 0.15em;
}
.page-title {
    margin-bottom: 1rem;
}
.btn-submit {
    min-width: 140px;
}
</style>

<div class="container-fluid">
    <h1 class="h2 page-title">Tạo đơn hàng mới</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($order_id): ?>
        <div class="alert alert-info">
            <p>Đơn hàng đã được tạo thành công. Bạn có thể:</p>
            <a href="/delivery-management/admin/order-view.php?id=<?php echo $order_id; ?>" class="btn btn-info btn-submit mb-2 me-2" data-bs-toggle="tooltip" title="Xem chi tiết đơn hàng">Xem chi tiết đơn hàng</a>
            <a href="/delivery-management/admin/order-detail-add.php?id=<?php echo $order_id; ?>" class="btn btn-primary btn-submit mb-2 me-2" data-bs-toggle="tooltip" title="Thêm sản phẩm vào đơn hàng">Thêm sản phẩm vào đơn hàng</a>
            <a href="/delivery-management/admin/orders.php" class="btn btn-secondary btn-submit mb-2" data-bs-toggle="tooltip" title="Quay lại danh sách đơn hàng">Quay lại danh sách đơn hàng</a>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="" novalidate>
                    <div class="mb-3">
                        <label for="khach_hang" class="form-label">Tên khách hàng <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="khach_hang" name="khach_hang" required
                               value="<?php echo isset($_POST['khach_hang']) ? htmlspecialchars($_POST['khach_hang']) : ''; ?>">
                        <div class="invalid-feedback">Vui lòng nhập tên khách hàng.</div>
                    </div>

                    <div class="mb-3">
                        <label for="dia_chi" class="form-label">Địa chỉ giao hàng <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="dia_chi" name="dia_chi" rows="3" required><?php echo isset($_POST['dia_chi']) ? htmlspecialchars($_POST['dia_chi']) : ''; ?></textarea>
                        <div class="invalid-feedback">Vui lòng nhập địa chỉ giao hàng.</div>
                    </div>

                    <div class="mb-3">
                        <label for="ngay_giao" class="form-label">Ngày giao <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="ngay_giao" name="ngay_giao" required
                               value="<?php echo isset($_POST['ngay_giao']) ? htmlspecialchars($_POST['ngay_giao']) : ''; ?>">
                        <div class="invalid-feedback">Vui lòng chọn ngày giao.</div>
                    </div>

                    <div class="mb-3">
                        <label for="nhanvien_id" class="form-label">Nhân viên giao hàng</label>
                        <select class="form-select" id="nhanvien_id" name="nhanvien_id">
                            <option value="">-- Chọn nhân viên --</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['nhanvien_id']; ?>"
                                    <?php echo (isset($_POST['nhanvien_id']) && $_POST['nhanvien_id'] == $employee['nhanvien_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($employee['ho_ten']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="trang_thai" class="form-label">Trạng thái</label>
                        <select class="form-select" id="trang_thai" name="trang_thai">
                            <option value="dang_giao" <?php echo (isset($_POST['trang_thai']) && $_POST['trang_thai'] === 'dang_giao') ? 'selected' : ''; ?>>Đang giao</option>
                            <option value="hoan_thanh" <?php echo (isset($_POST['trang_thai']) && $_POST['trang_thai'] === 'hoan_thanh') ? 'selected' : ''; ?>>Hoàn thành</option>
                            <option value="huy" <?php echo (isset($_POST['trang_thai']) && $_POST['trang_thai'] === 'huy') ? 'selected' : ''; ?>>Hủy</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-submit" data-bs-toggle="tooltip" title="Tạo đơn hàng mới">Tạo đơn hàng</button>
                        <a href="/delivery-management/admin/orders.php" class="btn btn-secondary btn-submit" data-bs-toggle="tooltip" title="Quay lại danh sách đơn hàng">Quay lại</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Bật tooltip bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Bootstrap form validation
    (function () {
        'use strict'
        var form = document.querySelector('form')
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
                alert('Vui lòng nhập đầy đủ và đúng thông tin trước khi gửi.')
            } else {
                // Hiện loader cho nút submit
                const btn = form.querySelector('button[type="submit"]')
                btn.disabled = true
                btn.classList.add('btn-loading')

                // Thêm spinner
                const spinner = document.createElement('span')
                spinner.className = 'spinner-border spinner-border-sm ms-2'
                spinner.setAttribute('role', 'status')
                spinner.setAttribute('aria-hidden', 'true')
                btn.appendChild(spinner)
            }
            form.classList.add('was-validated')
        }, false)
    })();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
