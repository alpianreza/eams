<?php if ($ev): ?>

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

  $remark = trim((string) ($ev['remark'] ?? ''));
  $followUpNote = trim((string) ($ev['follow_up_note'] ?? ''));

  $checkDateText = '-';
  if (!empty($ev['check_date'])) {
    try {
      $checkDateText = (new DateTime((string) $ev['check_date']))->format('d-m-Y');
    } catch (Throwable $th) {
      $checkDateText = (string) $ev['check_date'];
    }
  }

  $followUpDateText = '-';
  if (!empty($ev['follow_up_date'])) {
    try {
      $followUpDateText = (new DateTime((string) $ev['follow_up_date']))->format('d-m-Y');
    } catch (Throwable $th) {
      $followUpDateText = (string) $ev['follow_up_date'];
    }
  }

  $emptyImage = 'data:image/svg+xml;utf8,' . rawurlencode(
    '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="450" viewBox="0 0 800 450"><rect width="800" height="450" fill="#e2e8f0"/><text x="50%" y="50%" font-size="28" text-anchor="middle" fill="#64748b" font-family="Arial, sans-serif">Tidak ada foto</text></svg>'
  );

  $photo = trim((string) ($ev['photo'] ?? ''));
  $photoUrl = $photo !== ''
    ? base_url('uploads/checklist/' . str_replace('%2F', '/', rawurlencode($photo)))
    : $emptyImage;
  ?>

  <div class="evidence-detail">
    <div class="row g-3">
      <div class="col-12 col-lg-6">
        <div class="evidence-detail-media-wrap">
          <img src="<?= esc($photoUrl) ?>"
            alt="Evidence <?= esc($ev['item_name'] ?? '-') ?>"
            class="img-fluid evidence-detail-img"
            onerror="this.onerror=null;this.src='<?= esc($emptyImage) ?>';">
        </div>
      </div>

      <div class="col-12 col-lg-6">
        <div class="evidence-detail-head">
          <small class="text-muted d-block mb-1">Temuan Checklist</small>
          <h5 class="evidence-detail-title mb-2">
            Tidak sesuai: <?= esc($ev['item_name'] ?? '-') ?>
          </h5>

          <div class="d-flex flex-wrap gap-2">
            <span class="badge text-bg-danger">Status Checklist: Tidak sesuai</span>
            <span class="badge text-bg-<?= esc($statusColor) ?>">Tindak Lanjut: <?= esc($statusLabel) ?></span>
          </div>
        </div>

        <div class="evidence-detail-grid mt-3">
          <div class="evidence-detail-grid-item">
            <span>Kode Inventaris</span>
            <strong><?= esc($ev['asset_code'] ?? '-') ?></strong>
          </div>
          <div class="evidence-detail-grid-item">
            <span>Lokasi</span>
            <strong><?= esc($ev['specific_area'] ?? '-') ?></strong>
          </div>
          <div class="evidence-detail-grid-item">
            <span>Periode</span>
            <strong><?= esc($ev['period_key'] ?? '-') ?></strong>
          </div>
          <div class="evidence-detail-grid-item">
            <span>Tanggal Checklist</span>
            <strong><?= esc($checkDateText) ?></strong>
          </div>
        </div>

        <div class="mt-3">
          <label class="form-label evidence-detail-label">Catatan Temuan</label>
          <div class="evidence-detail-note">
            <?php if ($remark === ''): ?>
              <span class="text-muted">Tidak ada catatan.</span>
            <?php else: ?>
              <?= nl2br(esc($remark)) ?>
            <?php endif; ?>
          </div>
        </div>

        <?php if (hasRole(['admin', 'compliance'])): ?>
          <form id="followUpForm" class="evidence-followup-form mt-3">
            <input type="hidden" name="id" value="<?= (int) $ev['id'] ?>">

            <div class="mb-2">
              <label for="followUpStatus" class="form-label evidence-detail-label">Status Tindak Lanjut</label>
              <select id="followUpStatus" name="follow_up_status" class="form-select">
                <option value="open" <?= $followStatus === 'open' ? 'selected' : '' ?>>Open</option>
                <option value="monitoring" <?= $followStatus === 'monitoring' ? 'selected' : '' ?>>Monitoring</option>
                <option value="closed" <?= $followStatus === 'closed' ? 'selected' : '' ?>>Closed</option>
              </select>
            </div>

            <div class="mb-2">
              <label for="followUpNote" class="form-label evidence-detail-label">Catatan Tindak Lanjut</label>
              <textarea id="followUpNote"
                name="follow_up_note"
                class="form-control"
                rows="3"
                maxlength="1000"
                placeholder="Contoh: menunggu spare part, estimasi selesai 2 hari lagi"><?= esc($followUpNote) ?></textarea>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
              <small class="text-muted">Terakhir diperbarui: <?= esc($followUpDateText) ?></small>
              <button type="submit" class="btn btn-success btn-sm">
                Simpan Tindak Lanjut
              </button>
            </div>
          </form>
        <?php endif; ?>

        <?php if (!empty($ev['inventory_id'])): ?>
          <a href="<?= base_url('compliance/inventory/detail/' . $ev['inventory_id']) ?>"
            class="btn btn-outline-primary btn-sm mt-3">
            Lihat Detail Inventory
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

<?php else: ?>
  <div class="text-center text-muted p-4">
    Data evidence tidak ditemukan.
  </div>
<?php endif; ?>
