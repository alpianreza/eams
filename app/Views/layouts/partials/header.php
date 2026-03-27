<?php
$resolvedTitle = trim((string)($title ?? ''));
if ($resolvedTitle === '') {
  $resolvedTitle = trim((string)($defaultTitle ?? 'Dashboard'));
}
if ($resolvedTitle === '') {
  $resolvedTitle = 'Dashboard';
}
?>

<nav class="app-header navbar navbar-expand bg-white border-bottom shadow-sm">
  <div class="container-fluid d-flex align-items-center app-header-inner">

    <!-- LEFT: Sidebar Toggle + Title -->
    <div class="d-flex align-items-center gap-2 header-left-controls">
      <button class="btn btn-link header-sidebar-toggle header-icon-btn" data-lte-toggle="sidebar" aria-label="Toggle Sidebar">
        <i class="bi bi-list"></i>
      </button>
      <div class="app-header-title">
        <span class="fw-semibold fs-5 header-title-text">
          <?= esc($resolvedTitle) ?>
        </span>
      </div>
    </div>

    <!-- RIGHT: Notification + Profile -->
    <?php
    $userPhoto = session()->get('photo');
    $photoUrl = (!empty($userPhoto) && file_exists(FCPATH . 'uploads/users/' . $userPhoto))
      ? base_url('uploads/users/' . $userPhoto)
      : 'https://ui-avatars.com/api/?name=' . urlencode(session()->get('name'));

    $notifCount = $notifCount ?? 0;
    $notifications = $notifications ?? [];
    ?>

    <ul class="navbar-nav ms-auto align-items-center flex-row header-right-controls">

      <li class="nav-item dropdown header-notif-item me-1">
        <a class="nav-link position-relative header-notif-toggle"
          data-bs-toggle="dropdown"
          href="#"
          aria-label="Notifications">
          <i class="bi bi-bell fs-5"></i>

          <?php if ($notifCount > 0): ?>
            <span class="position-absolute badge bg-danger rounded-pill header-notif-badge">
              <?= $notifCount ?>
            </span>
          <?php endif; ?>
        </a>

        <ul class="dropdown-menu dropdown-menu-end shadow-sm header-notif-menu">
          <li class="dropdown-header">
            <?= $notifCount ?> Notifikasi
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>

          <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $notif): ?>
              <li>
                <a href="<?= esc($notif['url'] ?? base_url('home')) ?>" class="dropdown-item small d-flex align-items-start gap-2">
                  <i class="<?= esc($notif['icon'] ?? 'bi bi-info-circle text-primary') ?>"></i>
                  <span><?= esc($notif['text'] ?? '-') ?></span>
                </a>
              </li>
            <?php endforeach; ?>
          <?php elseif ($notifCount > 0): ?>
            <li>
              <a href="<?= base_url('home') ?>" class="dropdown-item small d-flex align-items-start gap-2">
                <i class="bi bi-clock text-warning"></i>
                <span><?= $notifCount ?> periode perlu perhatian</span>
              </a>
            </li>
          <?php else: ?>
            <li>
              <span class="dropdown-item text-muted small">
                Tidak ada notifikasi
              </span>
            </li>
          <?php endif; ?>
        </ul>
      </li>

      <li class="nav-item dropdown header-profile-item ms-1">
        <a class="nav-link dropdown-toggle d-flex align-items-center justify-content-center header-profile-toggle"
          data-bs-toggle="dropdown"
          href="#"
          aria-label="Profile Menu">
          <img class="rounded-circle header-profile-avatar"
            width="32"
            height="32"
            src="<?= $photoUrl ?>"
            alt="Profile">
        </a>

        <ul class="dropdown-menu dropdown-menu-end shadow header-profile-menu">
          <li class="dropdown-header small text-muted">
            <?= esc(session()->get('name') ?? 'User') ?>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>
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
