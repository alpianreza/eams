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

/**
 * Cache busting untuk semua file CSS/JS lokal.
 * Aman kalau file belum ada (mis. hasil build belum dijalankan).
 */
$assetUrl = static function (string $relativePath): string {
    $absolutePath = FCPATH . $relativePath;
    $version = is_file($absolutePath) ? (string) filemtime($absolutePath) : '1';

    return base_url($relativePath) . '?v=' . $version;
};

/**
 * Preferensi tema: 'light' | 'dark' | 'system'.
 * Dibaca di sisi server supaya tidak ada kedipan warna saat halaman dibuka.
 * Untuk 'system', atribut sengaja dikosongkan lalu diisi oleh skrip blocking
 * di <head> berdasarkan prefers-color-scheme.
 */
$themePreference = $_COOKIE['theme'] ?? 'light';
if (!in_array($themePreference, ['light', 'dark', 'system'], true)) {
    $themePreference = 'light';
}
$resolvedTheme = $themePreference === 'system' ? '' : $themePreference;
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <!-- viewport-fit=cover wajib supaya env(safe-area-inset-*) terbaca di iPhone -->
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">

    <title><?= esc($pageTitle . ' | EAMS') ?></title>

    <!-- Terapkan tema sistem sebelum render pertama (anti kedip) -->
    <script>
        (function () {
            var pref = <?= json_encode($themePreference) ?>;
            if (pref !== 'system') return;
            var dark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-eams-pending-theme', dark ? 'dark' : 'light');
        })();
    </script>

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

    <!-- AdminLTE (vendor) -->
    <link rel="stylesheet" href="<?= base_url('adminlte4/css/adminlte.min.css') ?>">

    <!--
      URUTAN PEMUATAN CSS (jangan diubah sembarangan):
      1. vendor  : bootstrap, adminlte
      2. token   : tokens.css  <- sumber kebenaran warna & tema
      3. compat  : sisa kelas tw- dari view lama (sementara)
      4. app     : app.css
      5. halaman : renderSection('styles')
      6. mobile  : mobile.css <- SENGAJA PALING AKHIR

      mobile.css harus menang atas CSS per halaman, karena banyak file
      halaman memakai !important dan memperkecil font sampai 0.62rem.
      Kalau dipindah ke atas, perbaikan mobile akan tertimpa lagi.
    -->
    <link rel="stylesheet" href="<?= $assetUrl('assets/css/tokens.css') ?>">
    <link rel="stylesheet" href="<?= $assetUrl('assets/css/compat-tailwind.css') ?>">
    <link rel="stylesheet" href="<?= $assetUrl('assets/css/app.css') ?>">
    <?= $this->renderSection('styles') ?>
    <link rel="stylesheet" href="<?= $assetUrl('assets/css/mobile.css') ?>">

</head>

<body class="layout-fixed sidebar-mini sidebar-expand-lg bg-body-secondary eams-v2<?= $isReadOnlyAccess ? ' is-read-only' : '' ?>"
      data-read-only="<?= $isReadOnlyAccess ? '1' : '0' ?>"
      data-theme-preference="<?= esc($themePreference, 'attr') ?>"
      <?php if ($resolvedTheme !== ''): ?>data-bs-theme="<?= esc($resolvedTheme, 'attr') ?>"<?php endif; ?>>

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
<script src="<?= $assetUrl('js/checklist-master.js') ?>"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<script>
  /* =====================================================================
   * GANTI TEMA — 3 status: Terang -> Gelap -> Ikut Sistem
   * Preferensi disimpan di cookie dan dibaca ulang di sisi server,
   * sehingga tidak ada kedipan warna saat pindah halaman.
   * ===================================================================== */
  (function () {
    var body = document.body;
    var toggle = document.getElementById('theme-toggle');
    var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

    var ORDER = ['light', 'dark', 'system'];

    var META = {
      light:  { icon: 'bi bi-sun',         label: 'Tema terang. Klik untuk tema gelap.' },
      dark:   { icon: 'bi bi-moon-stars',  label: 'Tema gelap. Klik untuk ikut sistem.' },
      system: { icon: 'bi bi-circle-half', label: 'Ikut sistem. Klik untuk tema terang.' }
    };

    function effectiveTheme(pref) {
      if (pref !== 'system') return pref;
      return media && media.matches ? 'dark' : 'light';
    }

    function apply(pref, persist) {
      body.setAttribute('data-theme-preference', pref);
      body.setAttribute('data-bs-theme', effectiveTheme(pref));
      document.documentElement.removeAttribute('data-eams-pending-theme');

      if (toggle) {
        toggle.innerHTML = '<i class="' + META[pref].icon + '"></i>';
        toggle.setAttribute('aria-label', META[pref].label);
        toggle.setAttribute('title', META[pref].label);
      }

      if (persist) {
        document.cookie = 'theme=' + pref + ';path=/;max-age=31536000;SameSite=Lax';
      }

      window.dispatchEvent(new CustomEvent('eams:themechange', {
        detail: { preference: pref, theme: effectiveTheme(pref) }
      }));
    }

    var current = body.getAttribute('data-theme-preference') || 'light';
    apply(current, false);

    if (toggle) {
      toggle.addEventListener('click', function () {
        var next = ORDER[(ORDER.indexOf(current) + 1) % ORDER.length];
        current = next;
        apply(next, true);
      });
    }

    // Ikuti perubahan tema OS secara langsung saat mode "system".
    if (media && media.addEventListener) {
      media.addEventListener('change', function () {
        if (current === 'system') apply('system', false);
      });
    }
  })();
</script>

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
