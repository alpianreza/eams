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
$pageTitle = trim((string) ($title ?? 'Login')) ?: 'Login';
?>
<!DOCTYPE html>
<html lang="id"<?php if ($resolvedTheme !== ''): ?> data-bs-theme="<?= esc($resolvedTheme, 'attr') ?>"<?php endif; ?>>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="color-scheme" content="light dark">
  <title><?= esc($pageTitle . ' | EAMS') ?></title>

  <script>
    (function () {
      var pref = <?= json_encode($themePreference) ?>;
      var media = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)');
      var theme = pref === 'system' ? (media && media.matches ? 'dark' : 'light') : pref;
      document.documentElement.setAttribute('data-bs-theme', theme);
    })();
  </script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('adminlte4/css/adminlte.min.css') ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= $assetUrl('assets/css/tokens.css') ?>">
  <?= $this->renderSection('styles') ?>
</head>
<body class="login-page eams-v2" data-theme-preference="<?= esc($themePreference, 'attr') ?>">
  <?= $this->renderSection('content') ?>
  <?= $this->renderSection('scripts') ?>
</body>
</html>
