<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Quản Lý Vận Chuyển</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #f8f9fa;
        }
        .content {
            padding: 20px;
        }
        .nav-link {
            color: #333;
        }
        .nav-link:hover {
            background-color: #e9ecef;
        }
        .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }
        /* Dark mode styles */
        body.dark-mode {
            background-color: #181a1b !important;
            color: #e9ecef !important;
        }
        body.dark-mode .card,
        body.dark-mode .card-footer,
        body.dark-mode .search-bar-wrapper {
            background-color: #23272b !important;
            color: #e9ecef !important;
            border-color: #343a40 !important;
        }
        body.dark-mode .table {
            color: #e9ecef;
        }
        body.dark-mode .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: #23272b;
        }
        body.dark-mode .table-hover tbody tr:hover {
            background-color: #2c3035 !important;
        }
        body.dark-mode .table-light,
        body.dark-mode .table-light th,
        body.dark-mode .table-light td {
            background-color: #23272b !important;
            color: #e9ecef !important;
        }
        body.dark-mode .form-control,
        body.dark-mode .input-group-text,
        body.dark-mode .btn,
        body.dark-mode .btn-outline-secondary {
            background-color: #23272b !important;
            color: #e9ecef !important;
            border-color: #343a40 !important;
        }
        body.dark-mode .btn-primary,
        body.dark-mode .btn-danger,
        body.dark-mode .btn-info,
        body.dark-mode .btn-warning,
        body.dark-mode .btn-success {
            color: #fff !important;
        }
        body.dark-mode .alert {
            background-color: #23272b !important;
            color: #ffc107 !important;
            border-color: #343a40 !important;
        }
        body.dark-mode .pagination .page-link {
            background-color: #23272b !important;
            color: #e9ecef !important;
            border-color: #343a40 !important;
        }
        body.dark-mode .pagination .page-item.active .page-link {
            background-color: #0d6efd !important;
            color: #fff !important;
        }
        body.dark-mode .badge.bg-secondary {
            background-color: #6c757d !important;
            color: #fff !important;
        }
        body.dark-mode .avatar {
            background-color: #0d6efd !important;
            color: #fff !important;
        }
        body.dark-mode .sidebar {
            background-color: #23272b !important;
        }
        body.dark-mode .nav-link {
            color: #e9ecef !important;
        }
        body.dark-mode .nav-link.active {
            background-color: #0d6efd !important;
            color: #fff !important;
        }
        body.dark-mode .nav-link:hover {
            background-color: #343a40 !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/delivery-management/">Quản Lý Vận Chuyển</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-white" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo isAdmin() ? '/delivery-management/admin/index.php' : '/delivery-management/employee/profile.php'; ?>">Hồ sơ</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/delivery-management/auth/logout.php">Đăng xuất</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="/delivery-management/auth/login.php">Đăng nhập</a>
                        </li>
                    <?php endif; ?>
                    <!-- Nút chuyển dark mode -->
                    <li class="nav-item ms-2">
                        <button id="toggle-darkmode" class="btn btn-outline-light" type="button" title="Chuyển Dark/Light mode">
                            <i class="bi bi-moon"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <script>
        // Dark mode toggle
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('toggle-darkmode');
            if (btn) {
                btn.onclick = function() {
                    document.body.classList.toggle('dark-mode');
                    if(document.body.classList.contains('dark-mode')) {
                        localStorage.setItem('darkMode', '1');
                    } else {
                        localStorage.removeItem('darkMode');
                    }
                };
            }
            if(localStorage.getItem('darkMode')) {
                document.body.classList.add('dark-mode');
            }
        });
    </script>