<?php
$role = $role ?? session()->get('role') ?? 'viewer';
$userName = (string)(session()->get('name') ?? 'User');

$segments = service('uri')->getSegments();
$seg1 = $segments[0] ?? '';
$seg2 = $segments[1] ?? '';

$isCompliance = $seg1 === 'compliance';
$isChecklistMenu = $isCompliance && in_array($seg2, ['inventory', 'checklist']);
$isQuestionnaireMenu = $isCompliance && $seg2 === 'questionnaires';
$isEmsReportMenu = $seg1 === 'ems-reports';
$isFdmMenu = $seg1 === 'fdm-data-collection';
$isPatrolMenu = $seg1 === 'patrol';

$brandLogoPath = FCPATH . 'assets/images/company/logo.png';
$brandLogoUrl = base_url('assets/images/company/logo.png');
$hasBrandLogo = file_exists($brandLogoPath);
?>

<aside class="app-sidebar bg-dark shadow" data-bs-theme="dark">
  <div class="sidebar-brand eams-sidebar-brand">
    <a href="<?= base_url('/') ?>" class="brand-link d-flex align-items-center justify-content-start gap-2">
      <?php if ($hasBrandLogo): ?>
        <img src="<?= esc($brandLogoUrl) ?>" class="sidebar-brand-icon" alt="EAMS">
        <span class="brand-copy">
          <span class="brand-text fw-bold">EAMS</span>
        </span>
      <?php else: ?>
        <span class="brand-copy">
          <span class="brand-text fw-bold">EAMS</span>
        </span>
      <?php endif; ?>
    </a>
  </div>

  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <ul class="nav sidebar-menu flex-column" role="menu">

        <!-- HOME -->
        <?php if (hasRole(['staff', 'compliance', 'admin', 'office'])): ?>
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

        <!-- PATROL -->
        <?php if (hasRole(['security', 'compliance', 'admin'])): ?>
          <li class="nav-header sidebar-section-title">SECURITY</li>
          <li class="nav-item">
            <a href="/patrol"
              class="nav-link <?= $isPatrolMenu ? 'active' : '' ?>">
              <i class="nav-icon bi bi-compass"></i>
              <p>Patrol Harian</p>
            </a>
          </li>
          <?php if (hasRole(['admin', 'compliance'])): ?>
            <li class="nav-item">
              <a href="/patrol/dashboard"
                class="nav-link <?= $seg1 === 'patrol' && $seg2 === 'dashboard' ? 'active' : '' ?>">
                <i class="nav-icon bi bi-speedometer2"></i>
                <p>Patrol Dashboard</p>
              </a>
            </li>
          <?php endif; ?>
        <?php endif; ?>

        <!-- ================= IT ASSET ================= -->
        <?php if (hasRole(['admin'])): ?>
          <li class="nav-header sidebar-section-title">IT ASSET</li>

          <li class="nav-item">
            <a href="<?= base_url('it') ?>"
              class="nav-link <?= $seg1 === 'it' && $seg2 === '' ? 'active' : '' ?>">
              <i class="nav-icon bi bi-grid"></i>
              <p>IT Center</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="<?= base_url('dashboard-it') ?>"
              class="nav-link <?= $seg1 === 'dashboard-it' ? 'active' : '' ?>">
              <i class="nav-icon bi bi-pie-chart"></i>
              <p>Dashboard IT</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="<?= base_url('it-assets') ?>"
              class="nav-link <?= $seg1 === 'it-assets' ? 'active' : '' ?>">
              <i class="nav-icon bi bi-pc-display"></i>
              <p>Data Asset IT</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="<?= base_url('it/devices') ?>"
              class="nav-link <?= $seg1 === 'it' && $seg2 === 'devices' ? 'active' : '' ?>">
              <i class="nav-icon bi bi-cpu"></i>
              <p>Device Control</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="<?= base_url('employees') ?>"
              class="nav-link <?= $seg1 === 'employees' ? 'active' : '' ?>">
              <i class="nav-icon bi bi-people"></i>
              <p>Users IT</p>
            </a>
          </li>
        <?php endif; ?>

        <!-- ================= COMPLIANCE ================= -->
        <?php if (hasRole(['admin', 'compliance', 'staff', 'auditor'])): ?>
          <li class="nav-header sidebar-section-title">COMPLIANCE</li>
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
                <i class="bi bi-chevron-down float-end sidebar-chevron"></i>
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
              <p>Holiday</p>
            </a>
          </li>
        <?php endif; ?>

        <!-- REPORT -->
        <?php if (hasRole(['admin', 'compliance', 'auditor'])): ?>
          <li class="nav-item">
            <a href="<?= base_url('compliance/report') ?>"
              class="nav-link <?= $isCompliance && $seg2 === 'report' ? 'active' : '' ?>">
              <i class="nav-icon fas fa-file-alt"></i>
              <p>Report</p>
            </a>
          </li>
        <?php endif; ?>

        <?php if (hasRole(['admin', 'compliance', 'office'])): ?>
          <li class="nav-item">
            <a href="<?= base_url('ems-reports') ?>"
              class="nav-link <?= $isEmsReportMenu ? 'active' : '' ?>">
              <i class="nav-icon bi bi-droplet-half"></i>
              <p>EMS Report</p>
            </a>
          </li>
        <?php endif; ?>

        <?php if (hasRole(['admin', 'compliance', 'office'])): ?>
          <li class="nav-item">
            <a href="<?= base_url('fdm-data-collection') ?>"
              class="nav-link <?= $isFdmMenu ? 'active' : '' ?>">
              <i class="nav-icon bi bi-clipboard-data"></i>
              <p>FDM Data Collection</p>
            </a>
          </li>
        <?php endif; ?>

        <?php if (hasRole(['admin', 'compliance'])): ?>
          <li class="nav-item">
            <a href="<?= base_url('compliance/questionnaires') ?>"
              class="nav-link <?= $isQuestionnaireMenu ? 'active' : '' ?>">
              <i class="nav-icon bi bi-ui-checks-grid"></i>
              <p>Kuesioner</p>
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

        <?php
        $isUtilityMenu = in_array($seg1, ['boiler', 'ipal', 'pdam-water']);
        ?>

        <?php if (hasRole(['admin', 'compliance'])): ?>
          <li class="nav-item">

            <a class="nav-link <?= $isUtilityMenu ? 'active' : '' ?>"
              data-bs-toggle="collapse"
              href="#menuUtility"
              role="button"
              aria-expanded="<?= $isUtilityMenu ? 'true' : 'false' ?>">

              <i class="nav-icon fas fa-industry"></i>
              <p>
                Boiler & Utility
                <i class="bi bi-chevron-down float-end sidebar-chevron"></i>
              </p>
            </a>

            <ul class="collapse nav flex-column ms-4 <?= $isUtilityMenu ? 'show' : '' ?>"
              id="menuUtility">

              <li class="nav-item">
                <a href="<?= base_url('boiler') ?>"
                  class="nav-link <?= $seg1 === 'boiler' ? 'active' : '' ?>">
                  <i class="fas fa-fire"></i>
                  <p>Boiler Fuel</p>
                </a>
              </li>

              <li class="nav-item">
                <a href="<?= base_url('ipal') ?>"
                  class="nav-link <?= $seg1 === 'ipal' ? 'active' : '' ?>">
                  <i class="fas fa-water"></i>
                  <p>IPAL Limbah</p>
                </a>
              </li>

              <li class="nav-item">
                <a href="<?= base_url('pdam-water') ?>"
                  class="nav-link <?= $seg1 === 'pdam-water' ? 'active' : '' ?>">
                  <i class="fas fa-faucet"></i>
                  <p>Air PDAM</p>
                </a>
              </li>

            </ul>

          </li>
        <?php endif; ?>

        <?php if (hasRole(['admin', 'compliance', 'auditor'])): ?>
          <li class="nav-item">
            <a href="<?= base_url('compliance/print') ?>" class="nav-link <?= $isCompliance && $seg2 === 'print' ? 'active' : '' ?>">
              <i class="nav-icon fas fa-print"></i>
              <p>Print Center</p>
            </a>
          </li>
        <?php endif; ?>

        <!-- ================= ADMIN ================= -->
        <?php if (hasRole(['admin', 'compliance'])): ?>
          <li class="nav-header sidebar-section-title">ADMIN</li>

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

          <li class="nav-item">
            <a href="<?= base_url('backups') ?>"
              class="nav-link <?= $seg1 === 'backups' ? 'active' : '' ?>">
              <i class="nav-icon bi bi-hdd-stack"></i>
              <p>Backup</p>
            </a>
          </li>
        <?php endif; ?>

      </ul>
    </nav>

    <div class="sidebar-meta mt-auto">
      <div class="sidebar-meta-label">Login sebagai</div>
      <div class="sidebar-meta-value"><?= esc($userName) ?></div>
    </div>
  </div>
</aside>