<?php
$emptyImage = 'data:image/svg+xml;utf8,' . rawurlencode(
  '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="450" viewBox="0 0 800 450"><rect width="800" height="450" fill="#e2e8f0"/><text x="50%" y="50%" font-size="28" text-anchor="middle" fill="#64748b" font-family="Arial, sans-serif">Tidak ada foto</text></svg>'
);
?>

<?php if (empty($evidences)): ?>
  <div class="evidence-grid-state text-center p-4">
    <h6 class="mb-1">Belum ada evidence</h6>
    <p class="text-muted mb-0">Temuan dengan status <strong>Tidak sesuai</strong> akan muncul di sini.</p>
  </div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($evidences as $ev): ?>
      <?php
      $followStatusRaw = strtolower(trim((string) ($ev['follow_up_status'] ?? 'open')));
      $followStatus = in_array($followStatusRaw, ['open', 'monitoring', 'closed'], true) ? $followStatusRaw : 'open';

      $statusMap = [
        'open' => ['class' => 'danger', 'label' => 'Open'],
        'monitoring' => ['class' => 'warning', 'label' => 'Monitoring'],
        'closed' => ['class' => 'success', 'label' => 'Closed'],
      ];

      $statusColor = $statusMap[$followStatus]['class'];
      $statusLabel = $statusMap[$followStatus]['label'];

      $agingDays = 0;
      if ($followStatus !== 'closed' && !empty($ev['check_date'])) {
        try {
          $checkDate = new DateTime((string) $ev['check_date']);
          $today = new DateTime('today');
          $agingDays = (int) $today->diff($checkDate)->format('%a');
        } catch (Throwable $th) {
          $agingDays = 0;
        }
      }

      $remark = trim((string) ($ev['remark'] ?? ''));
      $shortRemark = $remark;
      $remarkLength = function_exists('mb_strlen') ? mb_strlen($remark) : strlen($remark);
      if ($remark !== '' && $remarkLength > 90) {
        $shortRemark = function_exists('mb_substr')
          ? mb_substr($remark, 0, 90) . '...'
          : substr($remark, 0, 90) . '...';
      }

      $photo = trim((string) ($ev['photo'] ?? ''));
      $photoUrl = $photo !== ''
        ? base_url('uploads/checklist/' . str_replace('%2F', '/', rawurlencode($photo)))
        : $emptyImage;

      $periodKey = trim((string) ($ev['period_key'] ?? ''));
      $periodLabel = $periodKey !== '' ? $periodKey : '-';
      ?>

      <div class="col-12 col-sm-6 col-xl-3">
        <button type="button"
          class="card evidence-card evidence-card-button w-100 text-start border-0 p-0"
          data-id="<?= (int) $ev['id'] ?>"
          aria-label="Lihat detail evidence <?= esc($ev['item_name'] ?? '-') ?>">

          <div class="evidence-media-wrap">
            <img src="<?= esc($photoUrl) ?>"
              alt="Evidence <?= esc($ev['item_name'] ?? '-') ?>"
              class="card-img-top evidence-thumb"
              loading="lazy"
              onerror="this.onerror=null;this.src='<?= esc($emptyImage) ?>';">
            <span class="badge text-bg-<?= esc($statusColor) ?> evidence-status-badge">
              <?= esc($statusLabel) ?>
            </span>
          </div>

          <div class="card-body">
            <small class="evidence-period d-block mb-1">Periode: <?= esc($periodLabel) ?></small>

            <h6 class="evidence-item-title mb-1">
              Tidak sesuai: <?= esc($ev['item_name'] ?? '-') ?>
            </h6>

            <?php if ($followStatus !== 'closed' && $agingDays > 0): ?>
              <p class="evidence-aging <?= $agingDays > 30 ? 'is-late' : '' ?>">
                Aging <?= $agingDays ?> hari
              </p>
            <?php endif; ?>

            <div class="evidence-meta-list">
              <div class="evidence-meta-item">
                <span class="evidence-meta-label">Kode</span>
                <span class="evidence-meta-value"><?= esc($ev['asset_code'] ?? '-') ?></span>
              </div>
              <div class="evidence-meta-item">
                <span class="evidence-meta-label">Lokasi</span>
                <span class="evidence-meta-value"><?= esc($ev['specific_area'] ?? '-') ?></span>
              </div>
            </div>

            <?php if ($shortRemark !== ''): ?>
              <p class="evidence-remark mb-0"><?= esc($shortRemark) ?></p>
            <?php endif; ?>
          </div>
        </button>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (!empty($evidences)): ?>
  <nav class="evidence-pagination-wrap mt-3" aria-label="Paginasi evidence">
    <ul class="pagination pagination-sm justify-content-center mb-0">
      <?= $pager->links('default', 'eams') ?>
    </ul>
  </nav>
<?php endif; ?>
