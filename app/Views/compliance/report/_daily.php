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

$dayMap = [
  'Sunday' => 'Minggu',
  'Monday' => 'Senin',
  'Tuesday' => 'Selasa',
  'Wednesday' => 'Rabu',
  'Thursday' => 'Kamis',
  'Friday' => 'Jumat',
  'Saturday' => 'Sabtu',
];

$bulanNama = $monthMap[(int) $month] ?? date('F', strtotime($year . '-' . $month . '-01'));

$questionMap = [];
foreach ($masters as $master) {
  $questionMap[$master['id']] = $master['question'];
}

$statusSymbol = static function ($status): string {
  return match ($status) {
    'ok' => '&#10003;',
    'not_ok' => '&#10007;',
    'na' => '-',
    default => '',
  };
};
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

  <?php if (!empty($isToiletChecklist)): ?>
    <div class="table-responsive report-grid-wrap">
      <table class="table table-bordered text-center align-middle mb-0 report-grid-table">
        <thead class="table-light">
          <tr>
            <th rowspan="2" class="report-sticky-col">Hari</th>
            <th rowspan="2">Tanggal</th>
            <?php foreach ($masters as $q): ?>
              <th colspan="3"><?= esc($q['question']) ?></th>
            <?php endforeach; ?>
          </tr>
          <tr>
            <?php foreach ($masters as $q): ?>
              <th>PG</th>
              <th>SI</th>
              <th>SO</th>
            <?php endforeach; ?>
          </tr>
        </thead>

        <tbody>
          <?php foreach ($dailyDays as $date): ?>
            <?php
            $isSunday = date('w', strtotime($date)) == 0;
            $isHoliday = in_array($date, $holidayDates ?? [], true);
            $isOffDay = $isSunday || $isHoliday;

            $dayEn = date('l', strtotime($date));
            $dayLabel = $dayMap[$dayEn] ?? $dayEn;
            ?>

            <tr>
              <td class="report-sticky-col"><?= esc($dayLabel) ?></td>
              <td><?= esc(date('j', strtotime($date))) ?></td>

              <?php foreach ($masters as $q): ?>
                <?php foreach (['PG', 'SI', 'SO'] as $slot): ?>
                  <?php $status = $dailyGrid[$q['id']][$date][$slot] ?? null; ?>
                  <td class="<?= $isOffDay ? 'bg-offday text-muted' : '' ?>">
                    <?= $isOffDay ? '' : $statusSymbol($status) ?>
                  </td>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="table-responsive report-grid-wrap">
      <table class="table table-bordered text-center align-middle mb-0 report-grid-table">
        <thead class="table-light">
          <tr>
            <th class="text-start report-sticky-col">Item Pengecekan</th>
            <?php foreach ($dailyDays as $date): ?>
              <th><?= date('j', strtotime($date)) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>

        <tbody>
          <?php foreach ($masters as $q): ?>
            <tr>
              <td class="text-start report-sticky-col"><?= esc($q['question']) ?></td>
              <?php foreach ($dailyDays as $date): ?>
                <?php
                $isSunday = date('w', strtotime($date)) == 0;
                $isHoliday = in_array($date, $holidayDates ?? [], true);
                $isOffDay = $isSunday || $isHoliday;
                $status = $dailyGrid[$q['id']][$date] ?? null;
                ?>
                <td class="<?= $isOffDay ? 'bg-offday text-muted' : '' ?>">
                  <?= $isOffDay ? '' : $statusSymbol($status) ?>
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>

        <tfoot>
          <tr>
            <td class="fw-semibold report-sticky-col">Dicek Oleh</td>
            <?php foreach ($dailyDays as $date): ?>
              <?php
              $data = $checkerByDate[$date] ?? null;
              $initial = '';
              $tooltip = '';

              if ($data) {
                $parts = explode(' ', trim($data['name']));
                $first = $parts[0] ?? '';
                $initial = strtoupper(substr($first, 0, 2));
                $tooltip = 'Dicek oleh: ' . $data['name'];
                if ($role !== 'auditor') {
                  $tooltip .= ' | Tanggal: ' . date('d M Y', strtotime($data['date']));
                }
              }
              ?>
              <td class="report-checker-cell">
                <?php if ($initial): ?>
                  <span title="<?= esc($tooltip) ?>"><?= esc($initial) ?></span>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
          </tr>
        </tfoot>
      </table>
    </div>
  <?php endif; ?>
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
