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
    $luong_theo_order = $_POST['luong_theo_order'] ?? 0;
    $ngay_tra = !empty($_POST['ngay_tra']) ? $_POST['ngay_tra'] : null;

    if (empty($nhanvien_id) || empty($thang) || $luong_co_ban === '') {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
    } elseif (!is_numeric($luong_co_ban) || $luong_co_ban < 0) {
        $error = 'Lương cơ bản phải là số dương';
    } elseif (!empty($luong_theo_order) && (!is_numeric($luong_theo_order) || $luong_theo_order < 0)) {
        $error = 'Lương theo đơn phải là số dương';
    } else {
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM Luong WHERE nhanvien_id = ? AND thang = ?");
            $stmt->execute([$nhanvien_id, $thang]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Đã tồn tại thông tin lương của nhân viên này trong tháng ' . htmlspecialchars($thang);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO Luong (nhanvien_id, thang, luong_co_ban, luong_theo_order, ngay_tra) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nhanvien_id, $thang, $luong_co_ban, $luong_theo_order, $ngay_tra]);
                $success = 'Thêm thông tin lương thành công';

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

<!-- CSS cho Select2 và Spinner -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    .page-title { margin-bottom: 1rem; }
    .form-label span.text-danger { margin-left: 2px; }
    .btn-submit { min-width: 120px; }
    .spinner-border-sm { width: 1rem; height: 1rem; border-width: 0.15em; }
</style>

<div class="container-fluid">
    <h1 class="h2 page-title">Thêm thông tin lương</h1>

    <!-- Toast -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
        <div id="toastNotification" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
            <div class="d-flex">
                <div class="toast-body" id="toastMessage">Thành công!</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form id="salaryForm" method="POST" action="" novalidate>
                <div class="mb-3">
                    <label for="nhanvien_id" class="form-label" title="Chọn nhân viên nhận lương">Nhân viên <span class="text-danger">*</span></label>
                    <select class="form-select select2" id="nhanvien_id" name="nhanvien_id" required>
                        <option value="">-- Chọn nhân viên --</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo $employee['nhanvien_id']; ?>" <?php echo (isset($nhanvien_id) && $nhanvien_id == $employee['nhanvien_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($employee['ho_ten']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Vui lòng chọn nhân viên.</div>
                </div>

                <div class="mb-3">
                    <label for="thang" class="form-label" title="Nhập tháng theo định dạng YYYY-MM">Tháng (YYYY-MM) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="thang" name="thang" placeholder="VD: 2023-05" pattern="\d{4}-\d{2}" value="<?php echo isset($thang) ? htmlspecialchars($thang) : ''; ?>" required>
                    <div class="form-text">Định dạng: YYYY-MM (Năm-Tháng)</div>
                    <div class="invalid-feedback">Vui lòng nhập tháng hợp lệ.</div>
                </div>

                <div class="mb-3">
                    <label for="luong_co_ban" class="form-label" title="Nhập số tiền lương cơ bản">Lương cơ bản (VNĐ) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="luong_co_ban" name="luong_co_ban" min="0" step="100000" value="<?php echo isset($luong_co_ban) ? htmlspecialchars($luong_co_ban) : ''; ?>" required>
                    <div class="invalid-feedback">Vui lòng nhập lương cơ bản hợp lệ.</div>
                </div>

                <div class="mb-3">
                    <label for="luong_theo_order" class="form-label" title="Nhập số tiền lương theo đơn">Lương theo đơn (VNĐ)</label>
                    <input type="number" class="form-control" id="luong_theo_order" name="luong_theo_order" min="0" step="10000" value="<?php echo isset($luong_theo_order) ? htmlspecialchars($luong_theo_order) : ''; ?>">
                </div>

                <div class="mb-3">
                    <label for="tong_luong" class="form-label">Tổng lương (VNĐ)</label>
                    <input type="text" class="form-control" id="tong_luong" readonly value="0">
                </div>

                <div class="mb-3">
                    <label for="ngay_tra" class="form-label" title="Chọn ngày trả lương">Ngày trả lương</label>
                    <input type="date" class="form-control" id="ngay_tra" name="ngay_tra" value="<?php echo isset($ngay_tra) ? htmlspecialchars($ngay_tra) : ''; ?>">
                    <div class="form-text">Để trống nếu chưa trả lương</div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-submit" id="submitBtn" title="Thêm thông tin lương">Thêm</button>
                    <a href="/delivery-management/admin/salaries.php" class="btn btn-secondary btn-submit" title="Quay lại trang danh sách lương">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JS thư viện Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Init Select2 cho dropdown nhân viên
    $('.select2').select2({
        placeholder: "-- Chọn nhân viên --",
        allowClear: true,
        width: '100%'
    });

    const form = document.getElementById('salaryForm');
    const submitBtn = document.getElementById('submitBtn');
    const tongLuongInput = document.getElementById('tong_luong');
    const luongCoBanInput = document.getElementById('luong_co_ban');
    const luongTheoOrderInput = document.getElementById('luong_theo_order');

    // Tính tổng lương tự động
    function updateTongLuong() {
        let coBan = parseFloat(luongCoBanInput.value) || 0;
        let theoOrder = parseFloat(luongTheoOrderInput.value) || 0;
        tongLuongInput.value = (coBan + theoOrder).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
    }
    luongCoBanInput.addEventListener('input', updateTongLuong);
    luongTheoOrderInput.addEventListener('input', updateTongLuong);
    updateTongLuong(); // cập nhật lần đầu

    // Validation HTML5 + hiển thị lỗi
    form.addEventListener('submit', function (e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
            form.classList.add('was-validated');
            return;
        }
        // disable nút, thêm spinner
        submitBtn.disabled = true;
        submitBtn.innerHTML = `Đang gửi <span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>`;
    });

    // Hiển thị toast lỗi/thành công nếu có
    <?php if ($error): ?>
        showToast("<?php echo addslashes($error); ?>", 'danger');
    <?php elseif ($success): ?>
        showToast("<?php echo addslashes($success); ?>", 'success');
    <?php endif; ?>

    function showToast(message, type = 'success') {
        const toastEl = document.getElementById('toastNotification');
        toastEl.className = 'toast align-items-center text-white bg-' + type + ' border-0';
        toastEl.querySelector('.toast-body').textContent = message;
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
