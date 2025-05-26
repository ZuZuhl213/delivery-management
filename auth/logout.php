<?php
require_once __DIR__ . '/../includes/functions.php';


// Hủy phiên đăng nhập
session_start();
session_destroy();

// Chuyển hướng về trang đăng nhập
header("Location: /delivery-management/auth/login.php");
exit();
?>
