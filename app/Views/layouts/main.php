<?php
$isWritable = $isWritable ?? false;
$role       = $role ?? session()->get('role') ?? 'viewer';

$segments = service('uri')->getSegments();

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

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        crossorigin="anonymous">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">


    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('adminlte4/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/mobile.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/inventory-detail.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/inventory-mobile.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/login.css') ?>">
    <?= $this->renderSection('styles') ?>

</head>

<body class="layout-fixed sidebar-expand-lg bg-body-secondary">

    <?php if (session()->get('logged_in')): ?>

        <div class="app-wrapper">

            <!-- ================= HEADER ================= -->

            <?= $this->include('layouts/partials/header') ?>

            <!-- ================= SIDEBAR ================= -->

            <?= $this->include('layouts/partials/sidebar') ?>


            <!-- ================= MAIN ================= -->
            <main class="app-main">
                <div class="app-content">
                    <div class="container-fluid py-4">
                        <?= $this->renderSection('content') ?>
                    </div>
                </div>
            </main>

            <?= $this->include('layouts/partials/footer') ?>


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

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>





<script src="<?= base_url('js/app.js') ?>"></script>
<script src="<?= base_url('js/checklist-master.js') ?>"></script>

<script>
    window.FLASH_MESSAGE = {
        success: <?= session()->getFlashdata('success')
                        ? json_encode(session('success'))
                        : 'null' ?>,
        error: <?= session()->getFlashdata('error')
                    ? json_encode(session('error'))
                    : 'null' ?>,
        warning: <?= session()->getFlashdata('warning')
                        ? json_encode(session('warning'))
                        : 'null' ?>,
        info: <?= session()->getFlashdata('info')
                    ? json_encode(session('info'))
                    : 'null' ?>
    };
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (window.FLASH_MESSAGE.success) {
            safeToast(window.FLASH_MESSAGE.success, "success");
        }

        if (window.FLASH_MESSAGE.error) {
            safeToast(window.FLASH_MESSAGE.error, "error");
        }

        if (window.FLASH_MESSAGE.warning) {
            safeToast(window.FLASH_MESSAGE.warning, "warning");
        }

        if (window.FLASH_MESSAGE.info) {
            safeToast(window.FLASH_MESSAGE.info, "info");
        }
    });
</script>


<!-- Page Specific Script -->
<?= $this->renderSection('scripts') ?>


</html>