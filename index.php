<!-- <?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header("Location: " . (isAdmin() ? "/delivery-management/admin/index.php" : "/delivery-management/employee/index.php"));
} else {
    header("Location: /delivery-management/auth/login.php");
}
exit();
?> -->

<?php
// Chuyển hướng đến trang home.php
header("Location: /delivery-management/home.php");
exit();
?>
