<?php
$monthMap = [
  1 => 'Januari',
  2 => 'Februari',
  3 => 'Maret',
  4 => 'April',
  5 => 'Mei',
  6 => 'Juni',
  7 => 'Juli',
  8 => 'Agustus',
  9 => 'September',
  10 => 'Oktober',
  11 => 'November',
  12 => 'Desember',
];

$bulanNama = $monthMap[(int) $month] ?? date('F', strtotime($year . '-' . $month . '-01'));

$questionMap = [];
foreach ($masters as $master) {
  $questionMap[$master['id']] = $master['question'];
}
?>

<div class="report-sheet">
  <div class="text-center mb-3">
    <h6 class="report-sheet-title mb-1">Checklist Pengecekan <?= strtoupper(esc($itemName)) ?></h6>
    <div class="text-muted small">Periode <?= esc($bulanNama) ?> <?= esc((string) $year) ?></div>
  </div>

  <div class="row g-2 mb-3 report-meta-grid">
    <div class="col-md-3">
      <div class="report-meta-item">
        <div class="report-meta-label">Lokasi</div>
        <div class="report-meta-value"><?= esc($specificArea ?? '-') ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="report-meta-item">
        <div class="report-meta-label">PIC</div>
        <div class="report-meta-value"><?= esc($pic ?? '-') ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="report-meta-item">
        <div class="report-meta-label">No Inventaris</div>
        <div class="report-meta-value"><?= esc($inventory['asset_code']) ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="report-meta-item">
        <div class="report-meta-label">Masa Berlaku</div>
        <div class="report-meta-value">
          <?php if (!empty($isFireExtinguisher) && $isFireExtinguisher): ?>
            <?= !empty($expired) ? esc(date('d M Y', strtotime($expired))) : '-' ?>
          <?php else: ?>
            -
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="table-responsive report-grid-wrap">
    <table class="table table-bordered text-center align-middle mb-0 report-grid-table">
      <thead class="table-light">
        <tr>
          <th class="text-start report-sticky-col">Item Pengecekan</th>
          <th>Minggu 1</th>
          <th>Minggu 2</th>
          <th>Minggu 3</th>
          <th>Minggu 4</th>
        </tr>
      </thead>

      <tbody>
        <?php foreach ($masters as $q): ?>
          <tr>
            <td class="text-start report-sticky-col"><?= esc($q['question']) ?></td>
            <?php for ($w = 1; $w <= 4; $w++): ?>
              <?php $status = $weeklyGrid[$q['id']][$w] ?? null; ?>
              <td>
                <?php
                if ($status === 'ok') {
                  echo '&#10003;';
                } elseif ($status === 'not_ok') {
                  echo '&#10007;';
                } elseif ($status === 'na') {
                  echo '-';
                } else {
                  echo '';
                }
                ?>
              </td>
            <?php endfor; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>

      <tfoot>
        <tr>
          <td class="fw-semibold report-sticky-col">Dicek Oleh</td>
          <?php for ($w = 1; $w <= 4; $w++): ?>
            <?php
            $data = $checkerByWeek[$w] ?? null;
            $displayName = '';
            $tooltip = '';

            if ($data) {
              $parts = explode(' ', trim($data['name']));
              $displayName = ucfirst(strtolower($parts[0] ?? ''));
              $tooltip = 'Dicek oleh: ' . $data['name'];
              if ($role !== 'auditor') {
                $tooltip .= ' | Tanggal: ' . date('d M Y', strtotime($data['date']));
              }
            }
            ?>
            <td class="report-checker-cell">
              <?php if ($displayName): ?>
                <span title="<?= esc($tooltip) ?>"><?= esc($displayName) ?></span>
              <?php endif; ?>
            </td>
          <?php endfor; ?>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<?php if (!empty($findings)): ?>
  <hr>
  <h6 class="mt-4 mb-3">Detail Temuan <?= esc($bulanNama) ?> <?= esc((string) $year) ?></h6>

  <div class="row g-3">
    <?php foreach ($findings as $log): ?>
      <div class="col-md-4">
        <article class="card border-0 shadow-sm h-100 report-finding-card">
          <?php if (!empty($log['photo'])): ?>
            <img
              src="<?= base_url('uploads/checklist/' . $log['photo']) ?>"
              class="card-img-top img-preview report-finding-img"
              data-src="<?= base_url('uploads/checklist/' . $log['photo']) ?>"
              alt="Foto temuan">
          <?php endif; ?>

          <div class="card-body">
            <small class="text-muted d-block mb-1"><?= esc($log['display_period']) ?></small>
            <div class="fw-semibold"><?= esc($questionMap[$log['checklist_template_id']] ?? '-') ?></div>
            <?php if (!empty($log['remark'])): ?>
              <p class="mb-0 mt-2"><?= esc($log['remark']) ?></p>
            <?php endif; ?>
          </div>
        </article>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
