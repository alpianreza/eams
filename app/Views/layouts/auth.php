<?php
$assetUrl = static function (string $relativePath): string {
  $absolutePath = FCPATH . $relativePath;
  $version = is_file($absolutePath) ? (string) filemtime($absolutePath) : '1';

  return base_url($relativePath) . '?v=' . $version;
};

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
  <title>Login | EAMS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light dark">

  <!-- AdminLTE 4 -->
  <link rel="stylesheet" href="<?= base_url('adminlte4/css/adminlte.min.css') ?>">
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <!-- DataTables Bootstrap 5 -->
  <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

  <!-- Design token -->
  <link rel="stylesheet" href="<?= $assetUrl('assets/css/tokens.css') ?>">

</head>

<body class="login-page bg-body-secondary eams-v2"
  <?php if ($resolvedTheme !== ''): ?>data-bs-theme="<?= esc($resolvedTheme, 'attr') ?>"<?php endif; ?>>

  <?= $this->renderSection('content') ?>

</body>

</html>
