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
    <title><?= esc($title ?? 'EAMS') ?></title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <!-- App Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">

    <link rel="stylesheet" href="<?= base_url('css/assets/checklist.css') ?>">

    <link rel="stylesheet" href="<?= base_url('css/assets/inventory-detail.css') ?>">

    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">


</head>

<body>

    <?php if (session()->get('logged_in')): ?>

        <div id="wrapper" class="d-flex bg-light">

            <!-- SIDEBAR -->
            <aside class="sidebar p-3" style="width:260px">
                <div class="text-center mb-4">
                    <a href="<?= base_url('/') ?>" class="text-white fw-bold text-decoration-none fs-5">
                        EAMS
                    </a>
                </div>

                <ul class="nav nav-pills flex-column gap-1">

                    <!-- DASHBOARD -->
                    <li class="nav-item">
                        <a class="nav-link <?= $seg1 === '' ? 'active' : '' ?>" href="<?= base_url('/') ?>">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>

                    <hr class="border-light opacity-25">

                    <!-- IT ASSET -->
                    <div class="sidebar-heading text-uppercase small opacity-75 mb-1">
                        IT Asset
                    </div>

                    <li class="nav-item">
                        <a class="nav-link <?= $seg1 === 'it-assets' ? 'active' : '' ?>" href="<?= base_url('it-assets') ?>">
                            <i class="bi bi-pc-display me-2"></i> Data Asset IT
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?= $seg1 === 'employees' ? 'active' : '' ?>"
                            href="<?= base_url('employees') ?>">
                            Pemegang IT
                        </a>
                    </li>

                    <hr class="border-light opacity-25">

                    <!-- COMPLIANCE -->
                    <div class="sidebar-heading text-uppercase small opacity-75 mb-1">
                        Compliance
                    </div>

                    <li class="nav-item">
                        <a class="nav-link <?= $seg1 === 'compliance' && $seg2 === 'dashboard' ? 'active' : '' ?>"
                            href="<?= base_url('compliance/dashboard') ?>">
                            Dashboard Compliance
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link d-flex justify-content-between align-items-center"
                            data-bs-toggle="collapse"
                            href="#menuCompliance"
                            role="button"
                            aria-expanded="<?= $seg1 === 'compliance' ? 'true' : 'false' ?>">
                            <span>Checklist</span>
                            <span class="small">▾</span>
                        </a>

                        <div class="collapse <?= $seg1 === 'compliance' ? 'show' : '' ?>"
                            id="menuCompliance">
                            <ul class="nav flex-column ms-3 mt-1">
                                <li class="nav-item">
                                    <a class="nav-link <?= $seg2 === 'inventory' ? 'active' : '' ?>"
                                        href="<?= base_url('compliance/inventory') ?>">
                                        Inventory / Asset
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link"
                                        href="<?= base_url('compliance/checklist/ctpat') ?>">
                                        CTPAT
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link"
                                        href="<?= base_url('compliance/checklist/fire-safety') ?>">
                                        Fire Safety
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <?php if ($role === 'admin'): ?>
                        <hr class="border-light opacity-25">

                        <div class="sidebar-heading text-uppercase small opacity-75 mb-1">
                            Admin
                        </div>

                        <li class="nav-item">
                            <a class="nav-link <?= $seg1 === 'users' ? 'active' : '' ?>"
                                href="<?= base_url('users') ?>">
                                Manajemen User
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link <?= $seg1 === 'audit-logs' ? 'active' : '' ?>"
                                href="<?= base_url('audit-logs') ?>">
                                Audit Log
                            </a>
                        </li>
                    <?php endif; ?>

                </ul>
            </aside>

            <!-- CONTENT -->
            <div class="flex-grow-1 d-flex flex-column">

                <!-- TOPBAR -->
                <nav class="navbar navbar-light bg-white shadow-sm border-bottom px-4">
                    <span class="navbar-brand fw-semibold">
                        <?= esc($title ?? 'Dashboard') ?>
                    </span>

                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
                                href="#"
                                role="button"
                                data-bs-toggle="dropdown">

                                <span class="small">
                                    <?= esc(session()->get('name')) ?>
                                </span>

                                <img class="rounded-circle"
                                    width="32"
                                    height="32"
                                    src="https://ui-avatars.com/api/?name=<?= urlencode(session()->get('name') ?? 'User') ?>&size=32">
                            </a>

                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li>
                                    <a class="dropdown-item" href="<?= base_url('settings') ?>">
                                        ⚙️ Settings
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?= base_url('logout') ?>">
                                        🚪 Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </nav>

                <!-- PAGE CONTENT -->
                <main class="container-fluid py-4">
                    <?= $this->renderSection('content') ?>
                </main>

            </div>
        </div>

    <?php else: ?>

        <div class="container mt-5">
            <?= $this->renderSection('content') ?>
        </div>

    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if (session()->getFlashdata('success')): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: '<?= esc(session('success')) ?>',
                timer: 2000,
                showConfirmButton: false
            });
        </script>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: '<?= esc(session('error')) ?>'
            });
        </script>
    <?php endif; ?>



    <?= $this->renderSection('scripts') ?>

    <!-- JQUERY -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- DATATABLES -->
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

    <!-- INIT (PALING BAWAH) -->
    <script>
        $(document).ready(function() {
            $('#inventoryTable').DataTable({
                paging: false,
                info: false
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            document.querySelectorAll('.btn-delete').forEach(function(btn) {
                btn.addEventListener('click', function() {

                    const form = this.closest('form');

                    Swal.fire({
                        title: 'Yakin hapus data?',
                        text: 'Data inventory yang dihapus tidak bisa dikembalikan.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });

                });
            });

        });
    </script>

</body>

</html>