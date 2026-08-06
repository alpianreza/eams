<?php
/*
 * Bilah navigasi bawah untuk ponsel.
 *
 * Hanya tampil di bawah 768px (diatur bottom-nav.css).
 *
 * Isinya dibangun dari helper hak akses yang sama dengan sidebar,
 * lalu diambil empat teratas yang boleh dilihat pengguna ini. Jadi
 * peran security melihat Patrol, peran office melihat FDM, tanpa
 * perlu daftar terpisah yang gampang basi.
 */

$segments = service('uri')->getSegments();
$seg1 = $segments[0] ?? '';
$seg2 = $segments[1] ?? '';

$candidates = [
  [
    'show'  => canShowMenuPage(['staff', 'compliance', 'admin', 'office'], 'home') && canAccessPage('home'),
    'url'   => base_url('home'),
    'icon'  => 'bi-house',
    'label' => 'Home',
    'aktif' => $seg1 === 'home',
  ],
  [
    'show'  => canShowMenuPage(['security', 'compliance', 'admin'], 'patrol_daily') && canAccessPage('patrol_daily'),
    'url'   => base_url('patrol'),
    'icon'  => 'bi-compass',
    'label' => 'Patrol',
    'aktif' => $seg1 === 'patrol',
  ],
  [
    'show'  => canShowMenuPage(['admin', 'compliance', 'staff'], 'compliance_inventory') && canAccessPage('compliance_inventory'),
    'url'   => base_url('compliance/inventory'),
    'icon'  => 'bi-box-seam',
    'label' => 'Checklist',
    'aktif' => $seg1 === 'compliance' && $seg2 === 'inventory',
  ],
  [
    'show'  => canShowMenuPage(['admin', 'compliance', 'auditor'], 'compliance_dashboard') && canAccessPage('compliance_dashboard'),
    'url'   => base_url('compliance/dashboard'),
    'icon'  => 'bi-clipboard-check',
    'label' => 'Dashboard',
    'aktif' => $seg1 === 'compliance' && $seg2 === 'dashboard',
  ],
  [
    'show'  => canShowMenuPage(['admin', 'compliance'], 'compliance_progress') && canAccessPage('compliance_progress'),
    'url'   => base_url('compliance/progress'),
    'icon'  => 'bi-graph-up',
    'label' => 'Progress',
    'aktif' => $seg1 === 'compliance' && $seg2 === 'progress',
  ],
  [
    'show'  => canShowMenuPage(['admin', 'compliance', 'office'], 'fdm_data_collection') && canAccessPage('fdm_data_collection'),
    'url'   => base_url('fdm-data-collection'),
    'icon'  => 'bi-clipboard-data',
    'label' => 'FDM',
    'aktif' => $seg1 === 'fdm-data-collection',
  ],
  [
    'show'  => canShowMenuPage(['admin', 'compliance', 'auditor'], 'evidence_center') && canAccessPage('evidence_center'),
    'url'   => base_url('compliance/evidence'),
    'icon'  => 'bi-camera',
    'label' => 'Evidence',
    'aktif' => $seg1 === 'compliance' && $seg2 === 'evidence',
  ],
];

$items = [];
foreach ($candidates as $item) {
  if ($item['show']) {
    $items[] = $item;
  }
  if (count($items) === 4) {
    break;
  }
}
?>

<?php if (!empty($items)): ?>
  <nav class="eams-bottom-nav" aria-label="Navigasi utama">
    <?php foreach ($items as $item): ?>
      <a href="<?= esc($item['url']) ?>"
        class="eams-bottom-nav-item <?= $item['aktif'] ? 'is-active' : '' ?>"
        <?= $item['aktif'] ? 'aria-current="page"' : '' ?>>
        <i class="bi <?= esc($item['icon']) ?>" aria-hidden="true"></i>
        <span><?= esc($item['label']) ?></span>
      </a>
    <?php endforeach; ?>

    <button type="button"
      class="eams-bottom-nav-item eams-bottom-nav-more"
      data-lte-toggle="sidebar"
      aria-label="Buka menu lengkap">
      <i class="bi bi-list" aria-hidden="true"></i>
      <span>Menu</span>
    </button>
  </nav>
<?php endif; ?>
