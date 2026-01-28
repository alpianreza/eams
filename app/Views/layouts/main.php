<?php
$isWritable = $isWritable ?? false;
$role       = $role ?? session()->get('role') ?? 'viewer';

$path = trim(parse_url(current_url(), PHP_URL_PATH), '/');
$segments = $path === '' ? [] : explode('/', $path);

$seg1 = $segments[0] ?? '';
$seg2 = $segments[1] ?? '';

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= esc($title ?? 'EAMS') ?></title>


    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <!-- DataTables Bootstrap 5 -->
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('adminlte4/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/mobile.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/checklist.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/inventory-detail.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/inventory-mobile.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/login.css') ?>">

</head>

<body class="layout-fixed sidebar-expand-lg bg-body-secondary">

    <?php if (session()->get('logged_in')): ?>

        <div class="app-wrapper">

            <!-- ================= HEADER ================= -->
            <nav class="app-header navbar navbar-expand bg-white border-bottom shadow-sm">
                <div class="container-fluid">

                    <!-- Sidebar toggle -->
                    <button class="btn btn-link" data-lte-toggle="sidebar">
                        <i class="bi bi-list"></i>
                    </button>

                    <span class="navbar-brand fw-semibold ms-2">
                        <?= esc($title ?? 'Dashboard') ?>
                    </span>

                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
                                data-bs-toggle="dropdown" href="#">
                                <span class="small"><?= esc(session()->get('name')) ?></span>
                                <img class="rounded-circle"
                                    width="32"
                                    height="32"
                                    src="https://ui-avatars.com/api/?name=<?= urlencode(session()->get('name') ?? 'User') ?>&size=32">
                            </a>

                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li>
                                    <a class="dropdown-item" href="<?= base_url('settings') ?>">
                                        <i class="bi bi-gear me-2"></i> Settings
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?= base_url('logout') ?>">
                                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>

                </div>
            </nav>

            <!-- ================= SIDEBAR ================= -->
            <aside class="app-sidebar bg-dark shadow" data-bs-theme="dark">
                <div class="sidebar-brand">
                    <a href="<?= base_url('/') ?>" class="brand-link">
                        <span class="brand-text fw-bold">EAMS</span>
                    </a>
                </div>

                <div class="sidebar-wrapper">
                    <nav class="mt-2">
                        <ul class="nav sidebar-menu flex-column" role="menu">

                            <!-- Dashboard -->
                            <li class="nav-item">
                                <a href="<?= base_url('/') ?>"
                                    class="nav-link <?= $seg1 === '' ? 'active' : '' ?>">
                                    <i class="nav-icon bi bi-speedometer2"></i>
                                    <p>Dashboard</p>
                                </a>
                            </li>

                            <li class="nav-header">IT ASSET</li>

                            <li class="nav-item">
                                <a href="<?= base_url('it-assets') ?>"
                                    class="nav-link <?= $seg1 === 'it-assets' ? 'active' : '' ?>">
                                    <i class="nav-icon bi bi-pc-display"></i>
                                    <p>Data Asset IT</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="<?= base_url('employees') ?>"
                                    class="nav-link <?= $seg1 === 'employees' ? 'active' : '' ?>">
                                    <i class="nav-icon bi bi-people"></i>
                                    <p>Pemegang IT</p>
                                </a>
                            </li>

                            <li class="nav-header">COMPLIANCE</li>

                            <li class="nav-item">
                                <a href="<?= base_url('compliance/dashboard') ?>"
                                    class="nav-link <?= $seg1 === 'compliance' && $seg2 === 'dashboard' ? 'active' : '' ?>">
                                    <i class="nav-icon bi bi-clipboard-check"></i>
                                    <p>Dashboard Compliance</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?= $seg1 === 'compliance' ? 'active' : '' ?>"
                                    data-bs-toggle="collapse"
                                    href="#menuCompliance">
                                    <i class="nav-icon bi bi-list-check"></i>
                                    <p>
                                        Checklist
                                        <i class="bi bi-chevron-down float-end"></i>
                                    </p>
                                </a>

                                <ul class="collapse nav flex-column ms-4 <?= $seg1 === 'compliance' ? 'show' : '' ?>"
                                    id="menuCompliance">
                                    <li class="nav-item">
                                        <a href="<?= base_url('compliance/inventory') ?>"
                                            class="nav-link <?= $seg2 === 'inventory' ? 'active' : '' ?>">
                                            Inventory / Asset
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="<?= base_url('compliance/checklist/ctpat') ?>" class="nav-link">
                                            CTPAT
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="<?= base_url('compliance/checklist/fire-safety') ?>" class="nav-link">
                                            Fire Safety
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <?php if ($role === 'admin'): ?>
                                <li class="nav-header">ADMIN</li>

                                <li class="nav-item">
                                    <a href="<?= base_url('users') ?>"
                                        class="nav-link <?= $seg1 === 'users' ? 'active' : '' ?>">
                                        <i class="nav-icon bi bi-person-gear"></i>
                                        <p>Manajemen User</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="<?= base_url('audit-logs') ?>"
                                        class="nav-link <?= $seg1 === 'audit-logs' ? 'active' : '' ?>">
                                        <i class="nav-icon bi bi-shield-check"></i>
                                        <p>Audit Log</p>
                                    </a>
                                </li>
                            <?php endif; ?>

                        </ul>
                    </nav>
                </div>
            </aside>

            <!-- ================= MAIN ================= -->
            <main class="app-main">
                <div class="app-content-header"></div>

                <div class="app-content">
                    <div class="container-fluid py-4">
                        <?= $this->renderSection('content') ?>
                    </div>
                </div>
            </main>

        </div>

        <!-- TOAST -->
        <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
            <div
                id="appToast"
                class="toast fade"
                role="alert"
                aria-live="assertive"
                aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body" id="toastMessage">
                        Pesan
                    </div>
                    <button
                        type="button"
                        class="btn-close me-2 m-auto"
                        data-bs-dismiss="toast"
                        aria-label="Close"></button>
                </div>
            </div>
        </div>


    <?php else: ?>

        <?= $this->renderSection('content') ?>

    <?php endif; ?>

</body>


<script src="<?= base_url('adminlte4/js/adminlte.min.js') ?>"></script>

<!-- jQuery (WAJIB SEBELUM PLUGIN) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<!-- Global App JS -->
<script src="<?= base_url('js/app.js') ?>"></script>
<script src="<?= base_url('js/checklist.js') ?>"></script>


<!-- Page Specific Script -->
<?= $this->renderSection('scripts') ?>


</html>