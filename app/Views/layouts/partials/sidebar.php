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

$canHome = canAccessPage('home');
$canPatrolDaily = canAccessPage('patrol_daily');
$canPatrolDashboard = canAccessPage('patrol_dashboard');
$canItCenter = canAccessPage('it_center');
$canDashboardIt = canAccessPage('dashboard_it');
$canItAssets = canAccessPage('it_assets');
$canDeviceControl = canAccessPage('device_control');
$canEmployees = canAccessPage('employees');
$canComplianceDashboard = canAccessPage('compliance_dashboard');
$canComplianceProgress = canAccessPage('compliance_progress');
$canComplianceInventory = canAccessPage('compliance_inventory');
$canChecklistMaster = canAccessPage('checklist_master');
$canQrGallery = canAccessPage('qr_gallery');
$canHolidays = canAccessPage('holidays');
$canComplianceReport = canAccessPage('compliance_report');
$canThermalImaging = canAccessPage('thermal_imaging');
$canEmsReports = canAccessPage('ems_reports');
$canFdm = canAccessPage('fdm_data_collection');
$canQuestionnaires = canAccessPage('questionnaires');
$canEvidenceCenter = canAccessPage('evidence_center');
$canBoiler = canAccessPage('boiler_fuel');
$canIpal = canAccessPage('ipal');
$canPdamWater = canAccessPage('pdam_water');
$canPdamWaterBoiler = canAccessPage('pdam_water_boiler');
$canCompliancePrint = canAccessPage('compliance_print');
$canUsersManagement = canAccessPage('users_management');
$canAuditLogs = canAccessPage('audit_logs');
$canBackups = canAccessPage('backups');

$showHome = canShowMenuPage(['staff', 'compliance', 'admin', 'office'], 'home');
$showPatrolDaily = canShowMenuPage(['security', 'compliance', 'admin'], 'patrol_daily');
$showPatrolDashboard = canShowMenuPage(['admin', 'compliance'], 'patrol_dashboard');
$showItCenter = canShowMenuPage(['admin'], 'it_center');
$showDashboardIt = canShowMenuPage(['admin'], 'dashboard_it');
$showItAssets = canShowMenuPage(['admin'], 'it_assets');
$showDeviceControl = canShowMenuPage(['admin'], 'device_control');
$showEmployees = canShowMenuPage(['admin'], 'employees');
$showComplianceDashboard = canShowMenuPage(['admin', 'compliance', 'auditor'], 'compliance_dashboard');
$showComplianceProgress = canShowMenuPage(['admin', 'compliance'], 'compliance_progress');
$showComplianceInventory = canShowMenuPage(['admin', 'compliance', 'staff'], 'compliance_inventory');
$showChecklistMaster = canShowMenuPage(['admin', 'compliance'], 'checklist_master');
$showQrGallery = canShowMenuPage(['admin', 'compliance'], 'qr_gallery');
$showHolidays = canShowMenuPage(['admin', 'compliance'], 'holidays');
$showComplianceReport = canShowMenuPage(['admin', 'compliance', 'auditor'], 'compliance_report');
$showThermalImaging = canShowMenuPage(['admin', 'compliance', 'staff'], 'thermal_imaging');
$showEmsReports = canShowMenuPage(['admin', 'compliance', 'office'], 'ems_reports');
$showFdm = canShowMenuPage(['admin', 'compliance', 'office'], 'fdm_data_collection');
$showQuestionnaires = canShowMenuPage(['admin', 'compliance'], 'questionnaires');
$showEvidenceCenter = canShowMenuPage(['admin', 'compliance', 'auditor'], 'evidence_center');
$showBoiler = canShowMenuPage(['admin', 'compliance'], 'boiler_fuel');
$showIpal = canShowMenuPage(['admin', 'compliance'], 'ipal');
$showPdamWater = canShowMenuPage(['admin', 'compliance'], 'pdam_water');
$showPdamWaterBoiler = canShowMenuPage(['admin', 'compliance'], 'pdam_water_boiler');
$showCompliancePrint = canShowMenuPage(['admin', 'compliance', 'auditor'], 'compliance_print');
$showUsersManagement = canShowMenuPage(['admin', 'compliance'], 'users_management');
$showAuditLogs = canShowMenuPage(['admin', 'compliance'], 'audit_logs');
$showBackups = canShowMenuPage(['admin', 'compliance'], 'backups');

$isUtilityMenu = in_array($seg1, ['boiler', 'ipal', 'pdam-water', 'pdam-water-boiler']);
?>

<aside class="app-sidebar" data-bs-theme="dark">
  <div class="sidebar-brand eams-sidebar-brand">
    <a href="<?= base_url('/') ?>" class="brand-link">
      <?php if ($hasBrandLogo): ?>
        <img src="<?= esc($brandLogoUrl) ?>" class="sidebar-brand-icon" alt="EAMS">
      <?php endif; ?>
      <span class="brand-copy">
        <span class="brand-text">EAMS</span>
      </span>
    </a>
  </div>

  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <ul class="nav sidebar-menu flex-column" role="menu">

        <!-- HOME -->
        <?php if ($showHome && $canHome): ?>
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

        <!-- SECURITY -->
        <?php if (($showPatrolDaily || $showPatrolDashboard) && ($canPatrolDaily || $canPatrolDashboard)): ?>
          <li class="nav-header sidebar-section-title">SECURITY</li>

          <?php if ($showPatrolDaily && $canPatrolDaily): ?>
            <li class="nav-item">
              <a href="<?= base_url('patrol') ?>"
                class="nav-link <?= $isPatrolMenu ? 'active' : '' ?>">
                <i class="nav-icon bi bi-compass"></i>
                <p>Patrol Harian</p>
              </a>
            </li>
          <?php endif; ?>

          <?php if ($showPatrolDashboard && $canPatrolDashboard): ?>
            <li class="nav-item">
              <a href="<?= base_url('patrol/dashboard') ?>"
                class="nav-link <?= $seg1 === 'patrol' && $seg2 === 'dashboard' ? 'active' : '' ?>">
                <i class="nav-icon bi bi-speedometer2"></i>
                <p>Patrol Dashboard</p>
              </a>
            </li>
          <?php endif; ?>
        <?php endif; ?>

        <!-- IT ASSET -->
        <?php if ($showItCenter || $showDashboardIt || $showItAssets || $showDeviceControl || $showEmployees): ?>
          <li class="nav-header sidebar-section-title">IT ASSET</li>

          <?php if ($showItCenter && $canItCenter): ?>
            <li class="nav-item">
              <a href="<?= base_url('it') ?>"
                class="nav-link <?= $seg1 === 'it' && $seg2 === '' ? 'active' : '' ?>">
                <i class="nav-icon bi bi-grid"></i>
                <p>IT Center</p>
              </a>
            </li>
          <?php endif; ?>

          <?php if ($showDashboardIt && $canDashboardIt): ?>
            <li class="nav-item">
              <a href="<?= base_url('dashboard-it') ?>"
                class="nav-link <?= $seg1 === 'dashboard-it' ? 'active' : '' ?>">
                <i class="nav-icon bi bi-pie-chart"></i>
                <p>Dashboard IT</p>
              </a>
            </li>
          <?php endif; ?>

          <?php if ($showItAssets && $canItAssets): ?>
            <li class="nav-item">
              <a href="<?= base_url('it-assets') ?>"
                class="nav-link <?= $seg1 === 'it-assets' ? 'active' : '' ?>">
                <i class="nav-icon bi bi-pc-display"></i>
                <p>Data Asset IT</p>
              </a>
            </li>
          <?php endif; ?>

          <?php if ($showDeviceControl && $canDeviceControl): ?>
            <li class="nav-item">
              <a href="<?= base_url('it/devices') ?>"
                class="nav-link <?= $seg1 === 'it' && $seg2 === 'devices' ? 'active' : '' ?>">
                <i class="nav-icon bi bi-cpu"></i>
                <p>Device Control</p>
              </a>
            </li>
          <?php endif; ?>

          <?php if ($showEmployees && $canEmployees): ?>
            <li class="nav-item">
              <a href="<?= base_url('employees') ?>"
                class="nav-link <?= $seg1 === 'employees' ? 'active' : '' ?>">
                <i class="nav-icon bi bi-people"></i>
                <p>Users IT</p>
              </a>
            </li>
          <?php endif; ?>
        <?php endif; ?>

        <!-- COMPLIANCE -->
        <?php if ($showComplianceDashboard || $showComplianceProgress || $showComplianceInventory || $showChecklistMaster || $showQrGallery || $showHolidays || $showComplianceReport || $showThermalImaging || $showEmsReports || $showFdm || $showQuestionnaires || $showEvidenceCenter || $showBoiler || $showIpal || $showPdamWater || $showPdamWaterBoiler || $showCompliancePrint): ?>
          <li class="nav-header sidebar-section-title">COMPLIANCE</li>
        <?php endif; ?>

        <?php if ($showComplianceDashboard && $canComplianceDashboard): ?>
          <li class="nav-item">
            <a href="<?= base_url('compliance/dashboard') ?>"
              class="nav-link <?= $isCompliance && $seg2 === 'dashboard' ? 'active' : '' ?>">
              <i class="nav-icon bi bi-clipboard-check"></i>
              <p>Dashboard Compliance</p>
            </a>
          </li>
        <?php endif; ?>

        <?php if ($showComplianceProgress && $canComplianceProgress): ?>
          <li class="nav-item">
            <a href="<?= base_url('compliance/progress') ?>"
              class="nav-link <?= $isCompliance && $seg2 === 'progress' ? 'active' : '' ?>">
              <i class="nav-icon bi bi-graph-up"></i>
              <p>Monitoring Progress</p>
            </a>
          </li>
        <?php endif; ?>

        <!-- CHECKLIST -->
        <?php if ($showComplianceInventory || $showChecklistMaster || $showQrGallery): ?>
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

              <?php if ($showComplianceInventory && $canComplianceInventory): ?>
                <li class="nav-item">
                  <a href="<?= base_url('compliance/inventory') ?>"
                    class="nav-link <?= $seg2 === 'inventory' ? 'active' : '' ?>">
                    <i class="nav-icon bi bi-box-seam"></i>
                    <p>Inventory / Asset</p>
                  </a>
                </li>
              <?php endif; ?>

              <?php if ($showChecklistMaster && $canChecklistMaster): ?>
                <li class="nav-item">
                  <a href="<?= site_url('compliance/checklist/master') ?>"
                    class="nav-link <?= url_is('compliance/checklist/master*') ? 'active' : '' ?>">
                    <i class="nav-icon bi bi-card-checklist"></i>
                    <p>Checklist Master</p>
                  </a>
                </li>
              <?php endif; ?>

              <?php if ($showQrGallery && $canQrGallery): ?>
                <li class="nav-item">
                  <a href="<?= base_url('compliance/inventory/qr-center') ?>"
                    class="nav-link <?= $seg2 === 'qr-center' ? 'active' : '' ?>">
                    <i class="nav-icon bi bi-qr-code"></i>
                    <p>QR Gallery</p>
                  </a>
                </li>
              <?php endif; ?>

            </ul>
          </li>
        <?php endif; ?>

        <?php if ($showHolidays && $canHolidays): ?>
          <li class="nav-item">
            <a href="<?= site_url('holidays') ?>"
              class="nav-link <?= $seg1 === 'holidays' ? 'active' : '' ?>">
              <i class="nav-icon bi bi-calendar-event"></i>
              <p>Holiday</p>
            </a>
          </li>
        <?php endif; ?>

        <?php if ($showComplianceReport && $canComplianceReport): ?>
          <li class="nav-item">
            <a href="<?= base_url('compliance/report') ?>"
              class="nav-link <?= $isCompliance && $seg2 === 'report' ? 'active' : '' ?>">
              <i class="nav-icon bi bi-file-earmark-text"></i>
              <p>Report</p>
            </a>
          </li>
        <?php endif; ?>

        <?php if ($showThermalImaging && $canThermalImaging): ?>
          <li class="nav-item">
            <a href="<?= base_url('compliance/thermal-imaging') ?>"
              class="nav-link <?= $isCompliance && $seg2 === 'thermal-imaging' ? 'active' : '' ?>">
              <i class="nav-icon bi bi-thermometer-half"></i>
              <p>Thermal Imaging</p>
            </a>
          </li>
        <?php endif; ?>

        <?php if ($showEmsReports && $canEmsReports): ?>
          <li class="nav-item">
            <a href="<?= base_url('ems-reports') ?>"
              class="nav-link <?= $isEmsReportMenu ? 'active' : '' ?>">
              <i class="nav-icon bi bi-droplet-half"></i>
              <p>EMS Report</p>
            </a>
          </li>
        <?php endif; ?>

        <?php if ($showFdm && $canFdm): ?>
          <li class="nav-item">
            <a href="<?= base_url('fdm-data-collection') ?>"
              class="nav-link <?= $isFdmMenu ? 'active' : '' ?>">
              <i class="nav-icon bi bi-clipboard-data"></i>
              <p>FDM Data Collection</p>
            </a>
          </li>
        <?php endif; ?>

        <?php if ($showQuestionnaires && $canQuestionnaires): ?>
          <li class="nav-item">
            <a href="<?= base_url('compliance/questionnaires') ?>"
              class="nav-link <?= $isQuestionnaireMenu ? 'active' : '' ?>">
              <i class="nav-icon bi bi-ui-checks-grid"></i>
              <p>Kuesioner</p>
            </a>
          </li>
        <?php endif; ?>

        <?php if ($showEvidenceCenter && $canEvidenceCenter): ?>
          <li class="nav-item">
            <a href="<?= base_url('compliance/evidence') ?>"
              class="nav-link <?= $isCompliance && $seg2 === 'evidence' ? 'active' : '' ?>">
              <i class="nav-icon bi bi-camera"></i>
              <p>Evidence Center</p>
            </a>
          </li>
        <?php endif; ?>

        <!-- BOILER & UTILITY -->
        <?php if ($showBoiler || $showIpal || $showPdamWater || $showPdamWaterBoiler): ?>
          <li class="nav-item">
            <a class="nav-link <?= $isUtilityMenu ? 'active' : '' ?>"
              data-bs-toggle="collapse"
              href="#menuUtility"
              role="button"
              aria-expanded="<?= $isUtilityMenu ? 'true' : 'false' ?>">
              <i class="nav-icon bi bi-buildings"></i>
              <p>
                Boiler &amp; Utility
                <i class="bi bi-chevron-down float-end sidebar-chevron"></i>
              </p>
            </a>

            <ul class="collapse nav flex-column ms-4 <?= $isUtilityMenu ? 'show' : '' ?>"
              id="menuUtility">

              <?php if ($showBoiler && $canBoiler): ?>
                <li class="nav-item">
                  <a href="<?= base_url('boiler') ?>"
                    class="nav-link <?= $seg1 === 'boiler' ? 'active' : '' ?>">
                    <i class="nav-icon bi bi-fire"></i>
                    <p>Boiler Fuel</p>
                  </a>
                </li>
              <?php endif; ?>

              <?php if ($showIpal && $canIpal): ?>
                <li class="nav-item">
                  <a href="<?= base_url('ipal') ?>"
                    class="nav-link <?= $seg1 === 'ipal' ? 'active' : '' ?>">
                    <i class="nav-icon bi bi-water"></i>
                    <p>IPAL Limbah</p>
                  </a>
                </li>
              <?php endif; ?>

              <?php if ($showPdamWater && $canPdamWater): ?>
                <li class="nav-item">
                  <a href="<?= base_url('pdam-water') ?>"
                    class="nav-link <?= $seg1 === 'pdam-water' ? 'active' : '' ?>">
                    <i class="nav-icon bi bi-moisture"></i>
                    <p>Air PDAM</p>
                  </a>
                </li>
              <?php endif; ?>

              <?php if ($showPdamWaterBoiler && $canPdamWaterBoiler): ?>
                <li class="nav-item">
                  <a href="<?= base_url('pdam-water-boiler') ?>"
                    class="nav-link <?= $seg1 === 'pdam-water-boiler' ? 'active' : '' ?>">
                    <i class="nav-icon bi bi-droplet"></i>
                    <p>Air PDAM Boiler</p>
                  </a>
                </li>
              <?php endif; ?>

            </ul>
          </li>
        <?php endif; ?>

        <?php if ($showCompliancePrint && $canCompliancePrint): ?>
          <li class="nav-item">
            <a href="<?= base_url('compliance/print') ?>"
              class="nav-link <?= $isCompliance && $seg2 === 'print' ? 'active' : '' ?>">
              <i class="nav-icon bi bi-printer"></i>
              <p>Print Center</p>
            </a>
          </li>
        <?php endif; ?>

        <!-- ADMIN -->
        <?php if ($showUsersManagement || $showAuditLogs || $showBackups): ?>
          <li class="nav-header sidebar-section-title">ADMIN</li>

          <?php if ($showUsersManagement && $canUsersManagement): ?>
            <li class="nav-item">
              <a href="<?= base_url('users') ?>"
                class="nav-link <?= $seg1 === 'users' ? 'active' : '' ?>">
                <i class="nav-icon bi bi-person-gear"></i>
                <p>Manajemen User</p>
              </a>
            </li>
          <?php endif; ?>

          <?php if ($showAuditLogs && $canAuditLogs): ?>
            <li class="nav-item">
              <a href="<?= base_url('audit-logs') ?>"
                class="nav-link <?= $seg1 === 'audit-logs' ? 'active' : '' ?>">
                <i class="nav-icon bi bi-shield-check"></i>
                <p>Audit Log</p>
              </a>
            </li>
          <?php endif; ?>

          <?php if ($showBackups && $canBackups): ?>
            <li class="nav-item">
              <a href="<?= base_url('backups') ?>"
                class="nav-link <?= $seg1 === 'backups' ? 'active' : '' ?>">
                <i class="nav-icon bi bi-hdd-stack"></i>
                <p>Backup</p>
              </a>
            </li>
          <?php endif; ?>
        <?php endif; ?>

      </ul>
    </nav>

    <div class="sidebar-meta mt-auto">
      <div class="sidebar-meta-label">Login sebagai</div>
      <div class="sidebar-meta-value"><?= esc($userName) ?></div>
    </div>
  </div>
</aside>
