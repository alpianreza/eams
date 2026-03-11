<?php
$role = $role ?? session()->get('role') ?? 'viewer';

$segments = service('uri')->getSegments();
$seg1 = $segments[0] ?? '';
$seg2 = $segments[1] ?? '';

$isCompliance = $seg1 === 'compliance';
$isChecklistMenu = $isCompliance && in_array($seg2, ['inventory', 'checklist']);
?>

<aside class="app-sidebar bg-dark shadow" data-bs-theme="dark">
  <div class="sidebar-brand">
    <a href="<?= base_url('/') ?>" class="brand-link">
      <span class="brand-text fw-bold">EAMS</span>
    </a>
  </div>

  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <ul class="nav sidebar-menu flex-column" role="menu">

        <!-- DASHBOARD -->
        <?php if (hasRole(['admin'])): ?>
          <li class="nav-item">
            <a href="<?= base_url('/') ?>"
              class="nav-link <?= $seg1 === '' ? 'active' : '' ?>">
              <i class="nav-icon bi bi-speedometer2"></i>
              <p>Dashboard</p>
            </a>
          </li>
        <?php endif; ?>

        <!-- HOME -->
        <?php if (hasRole(['staff', 'compliance', 'admin'])): ?>
          <li class="nav-item">
            <a href="<?= base_url('home') ?>"
              class="nav-link <?= $seg1 === 'home' ? 'active' : '' ?>">
              <i class="nav-icon bi bi-house"></i>
              <p>Home</p>

              <?php if (!empty($notifCount) && $notifCount > 0): ?>
                <span class="badge bg-danger"><?= $notifCount ?></span>
              <?php endif; ?>
            </a>
          </li>
        <?php endif; ?>

        <!-- ================= IT ASSET ================= -->
        <?php if (hasRole(['admin', 'compliance'])): ?>
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
        <?php endif; ?>

        <!-- ================= COMPLIANCE ================= -->
        <?php if (hasRole(['admin', 'compliance', 'staff', 'auditor'])): ?>
          <li class="nav-header">COMPLIANCE</li>
        <?php endif; ?>

        <!-- DASHBOARD COMPLIANCE -->
        <?php if (hasRole(['admin', 'compliance', 'auditor'])): ?>
          <li class="nav-item">
            <a href="<?= base_url('compliance/dashboard') ?>"
              class="nav-link <?= $isCompliance && $seg2 === 'dashboard' ? 'active' : '' ?>">
              <i class="nav-icon bi bi-clipboard-check"></i>
              <p>Dashboard Compliance</p>
            </a>
          </li>
        <?php endif; ?>

        <!-- PROGRESS -->
        <?php if (hasRole(['admin', 'compliance'])): ?>
          <li class="nav-item">
            <a href="<?= base_url('compliance/progress') ?>"
              class="nav-link <?= $isCompliance && $seg2 === 'progress' ? 'active' : '' ?>">
              <i class="nav-icon bi bi-graph-up"></i>
              <p>Monitoring Progress</p>
            </a>
          </li>
        <?php endif; ?>

        <!-- CHECKLIST MENU -->
        <?php if (hasRole(['admin', 'compliance', 'staff'])): ?>
          <li class="nav-item">
            <a class="nav-link <?= $isChecklistMenu ? 'active' : '' ?>"
              data-bs-toggle="collapse"
              href="#menuComplianceChecklist"
              role="button"
              aria-expanded="<?= $isChecklistMenu ? 'true' : 'false' ?>">
              <i class="nav-icon bi bi-list-check"></i>
              <p>
                Checklist
                <i class="bi bi-chevron-down float-end"></i>
              </p>
            </a>

            <ul class="collapse nav flex-column ms-4 <?= $isChecklistMenu ? 'show' : '' ?>"
              id="menuComplianceChecklist">

              <li class="nav-item">
                <a href="<?= base_url('compliance/inventory') ?>"
                  class="nav-link <?= $seg2 === 'inventory' ? 'active' : '' ?>">
                  <i class="fa-solid fa-industry"></i>
                  <p>Inventory / Asset</p>
                </a>
              </li>

              <?php if (hasRole(['admin', 'compliance'])): ?>
                <li class="nav-item">
                  <a href="<?= site_url('compliance/checklist/master') ?>"
                    class="nav-link <?= url_is('compliance/checklist/master*') ? 'active' : '' ?>">
                    <i class="nav-icon fa-solid fa-list-check"></i>
                    <p>Checklist Master</p>
                  </a>
                </li>
              <?php endif; ?>

              <?php if (hasRole(['admin', 'compliance'])): ?>

                <li class="nav-item">
                  <a href="<?= base_url('compliance/inventory/qr-center') ?>"
                    class="nav-link <?= ($seg2 == 'qr-center') ? 'active' : '' ?>">

                    <i class="nav-icon fas fa-qrcode"></i>
                    <p>QR Gallery</p>
                  </a>
                </li>

              <?php endif; ?>

            </ul>
          </li>
        <?php endif; ?>

        <!-- HOLIDAYS -->
        <?php if (hasRole(['admin', 'compliance'])): ?>
          <li class="nav-item">
            <a href="<?= site_url('holidays') ?>"
              class="nav-link <?= $seg1 === 'holidays' ? 'active' : '' ?>">
              <i class="nav-icon bi bi-calendar-event"></i>
              <p>Hari Libur</p>
            </a>
          </li>
        <?php endif; ?>

        <!-- REPORT -->
        <?php if (hasRole(['admin', 'compliance', 'auditor'])): ?>
          <li class="nav-item">
            <a href="<?= base_url('compliance/report') ?>"
              class="nav-link <?= $isCompliance && $seg2 === 'report' ? 'active' : '' ?>">
              <i class="nav-icon fas fa-file-alt"></i>
              <p>Laporan</p>
            </a>
          </li>
        <?php endif; ?>

        <!-- EVIDENCE -->
        <?php if (hasRole(['admin', 'compliance', 'auditor'])): ?>
          <li class="nav-item">
            <a href="<?= base_url('compliance/evidence') ?>"
              class="nav-link <?= $isCompliance && $seg2 === 'evidence' ? 'active' : '' ?>">
              <i class="nav-icon fas fa-camera"></i>
              <p>Evidence Center</p>
            </a>
          </li>
        <?php endif; ?>

        <?php if (hasRole(['admin', 'compliance'])): ?>
          <li class="nav-item">
            <a href="<?= base_url('boiler') ?>"
              class="nav-link <?= uri_string() == 'boiler' ? 'active' : '' ?>">
              <i class="nav-icon fas fa-fire"></i>
              <p>Boiler Fuel Log</p>
            </a>
          </li>
        <?php endif; ?>

        <?php if (hasRole(['admin', 'compliance', 'auditor'])): ?>
          <li class="nav-item">
            <a href="<?= base_url('compliance/print') ?>" class="nav-link">
              <i class="nav-icon fas fa-print"></i>
              <p>Print Center</p>
            </a>
          </li>
        <?php endif; ?>

        <!-- ================= ADMIN ================= -->
        <?php if (hasRole(['admin'])): ?>
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