<?php
$isWritable = $isWritable ?? false;
$role       = $role ?? session()->get('role') ?? 'viewer';
$isReadOnlyAccess = function_exists('isReadOnlyAccess') ? isReadOnlyAccess() : false;

$segments = service('uri')->getSegments();

$seg1 = $segments[0] ?? '';
$seg2 = $segments[1] ?? '';

$pageTitle = trim((string)($title ?? ''));
if ($pageTitle === '') {
    $pageTitle = trim((string)($defaultTitle ?? 'Dashboard'));
}
if ($pageTitle === '') {
    $pageTitle = 'Dashboard';
}

$resolvedBackUrl = '';
if (!empty($backUrl)) {
    $backUrlText = trim((string) $backUrl);

    if (
        str_starts_with($backUrlText, 'http://') ||
        str_starts_with($backUrlText, 'https://') ||
        str_starts_with($backUrlText, '/')
    ) {
        $resolvedBackUrl = $backUrlText;
    } else {
        $resolvedBackUrl = base_url($backUrlText);
    }
}

?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= esc($pageTitle . ' | EAMS') ?></title>


    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        crossorigin="anonymous">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">


    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('adminlte4/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/mobile.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css?v=' . time()) ?>">
    <?= $this->renderSection('styles') ?>

</head>

<body class="layout-fixed sidebar-mini sidebar-expand-lg bg-body-secondary eams-v2<?= $isReadOnlyAccess ? ' is-read-only' : '' ?>" data-read-only="<?= $isReadOnlyAccess ? '1' : '0' ?>">

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
                        <?php if ($resolvedBackUrl !== ''): ?>
                            <div class="mb-3">
                                <a href="<?= esc($resolvedBackUrl) ?>" class="btn btn-outline-secondary btn-sm content-back-link">
                                    <i class="fa-solid fa-left-long me-1"></i> Kembali
                                </a>
                            </div>
                        <?php endif; ?>
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

<script src="<?= base_url('js/evidence.js') ?>"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-sparklines/2.1.2/jquery.sparkline.min.js"></script>



<script src="<?= base_url('js/app.js') ?>"></script>
<script src="<?= base_url('js/checklist-master.js?v=' . filemtime(FCPATH . 'js/checklist-master.js')) ?>"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

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

<?php if ($isReadOnlyAccess && session()->get('logged_in')): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const message = "Akses read only hanya bisa membaca data.";
            const mutatingMethods = ["post", "put", "patch", "delete"];

            document.querySelectorAll("form").forEach((form) => {
                const method = (form.getAttribute("method") || "get").toLowerCase();
                if (!mutatingMethods.includes(method) || form.dataset.readonlyAllow === "1") {
                    return;
                }

                form.classList.add("read-only-locked-form");
                form.querySelectorAll("input, textarea, select, button").forEach((control) => {
                    if (control.type === "hidden" || control.dataset.readonlyAllow === "1") {
                        return;
                    }

                    control.disabled = true;
                    control.setAttribute("aria-disabled", "true");
                });
            });

            document.addEventListener("submit", function(event) {
                const form = event.target;
                if (!(form instanceof HTMLFormElement) || form.dataset.readonlyAllow === "1") {
                    return;
                }

                const method = (form.getAttribute("method") || "get").toLowerCase();
                if (mutatingMethods.includes(method)) {
                    event.preventDefault();
                    window.safeToast?.(message, "warning");
                }
            }, true);

            const originalFetch = window.fetch;
            window.fetch = function(resource, options = {}) {
                const method = (options.method || "get").toLowerCase();
                if (mutatingMethods.includes(method) && options.readonlyAllow !== true) {
                    window.safeToast?.(message, "warning");
                    return Promise.reject(new Error(message));
                }

                return originalFetch.apply(this, arguments);
            };
        });
    </script>
<?php endif; ?>


<!-- Page Specific Script -->
<?= $this->renderSection('scripts') ?>


</html>
