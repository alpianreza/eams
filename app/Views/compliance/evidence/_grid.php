<div class="row">
  <?php foreach ($evidences as $ev): ?>
    <div class="col-md-3 mb-4">
      <div class="card evidence-card shadow-sm position-relative"
        style="cursor:pointer;"
        data-id="<?= $ev['id'] ?>">

        <?php
        $followStatus = $ev['follow_up_status'] ?? 'open';

        $statusColor = match ($followStatus) {
          'closed' => 'success',
          'monitoring' => 'warning',
          default => 'danger'
        };

        $agingText = '';
        $agingDays = 0;

        if ($followStatus !== 'closed' && !empty($ev['check_date'])) {
          $checkDate = new DateTime($ev['check_date']);
          $today     = new DateTime();
          $agingDays = $today->diff($checkDate)->days;

          $agingText = $agingDays . ' hari';
        }
        ?>


        <!-- Badge Follow Up -->
        <span class="badge bg-<?= $statusColor ?>"
          style="position:absolute; top:10px; right:10px; z-index:10;">
          <?= ucfirst($followStatus) ?>
        </span>

        <img src="<?= base_url('uploads/checklist/' . $ev['photo']) ?>"
          class="card-img-top"
          style="height:200px;object-fit:cover;">

        <div class="card-body p-2">

          <!-- Periode berdasarkan period_key -->
          <small class="text-muted d-block mb-1">
            <?= esc($ev['period_key']) ?>
          </small>

          <!-- Nama Item -->
          <h6 class="mb-1 text-danger">
            ✗ <?= esc($ev['item_name']) ?>
          </h6>

          <?php if ($followStatus !== 'closed' && $agingDays > 0): ?>
            <small class="d-block mt-1 <?= $agingDays > 30 ? 'text-danger fw-bold' : 'text-muted' ?>">
              • <?= $agingText ?> <?= $agingDays > 30 ? '⚠' : '' ?>
            </small>
          <?php endif; ?>


          <!-- Asset Code -->
          <small class="d-block">
            <strong>Kode:</strong>
            <?= esc($ev['asset_code']) ?>
          </small>

          <!-- Lokasi -->
          <small class="d-block mb-2">
            <strong>Lokasi:</strong>
            <?= esc($ev['specific_area']) ?>
          </small>

          <!-- Remark -->
          <?php if (!empty($ev['remark'])): ?>
            <div class="border-top pt-2">
              <small class="text-muted">
                <?= esc(strlen($ev['remark']) > 60
                  ? substr($ev['remark'], 0, 60) . '...'
                  : $ev['remark']) ?>
              </small>
            </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="mt-3">
  <?= $pager->links() ?>
</div>