<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="/delivery-management/admin/index.php">
                                <i class="bi bi-speedometer2 me-2"></i> Tổng quan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'employees.php' ? 'active' : ''; ?>" href="/delivery-management/admin/employees.php">
                                <i class="bi bi-people me-2"></i> Quản lý nhân viên
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'vehicles.php' ? 'active' : ''; ?>" href="/delivery-management/admin/vehicles.php">
                                <i class="bi bi-truck me-2"></i> Quản lý phương tiện
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" href="/delivery-management/admin/orders.php">
                                <i class="bi bi-box-seam me-2"></i> Quản lý đơn hàng
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'salaries.php' ? 'active' : ''; ?>" href="/delivery-management/admin/salaries.php">
                                <i class="bi bi-cash-stack me-2"></i> Quản lý lương
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="/delivery-management/admin/reports.php">
                                <i class="bi bi-bar-chart me-2"></i> Báo cáo & Thống kê
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="/delivery-management/employee/index.php">
                                <i class="bi bi-speedometer2 me-2"></i> Tổng quan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="/delivery-management/employee/profile.php">
                                <i class="bi bi-person me-2"></i> Hồ sơ cá nhân
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'vehicles.php' ? 'active' : ''; ?>" href="/delivery-management/employee/vehicles.php">
                                <i class="bi bi-truck me-2"></i> Phương tiện
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" href="/delivery-management/employee/orders.php">
                                <i class="bi bi-box-seam me-2"></i> Đơn hàng
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content">
