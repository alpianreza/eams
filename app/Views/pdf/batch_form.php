<?php
$logoPath = FCPATH . 'assets/images/company/logo.png';
$logoSrc = is_file($logoPath) ? str_replace('\\', '/', $logoPath) : '';

$company = $layout['company'] ?? [];
$period = $layout['period'] ?? ['label' => 'HARI / TANGGAL', 'value' => ''];
$groupedColumns = $layout['groupedColumns'] ?? [];
$itemSlug = (string) ($layout['itemSlug'] ?? '');
$isFireAlarmTemplate = $itemSlug === 'fire_alarm';
$isIntrusionAlarmTemplate = $itemSlug === 'intrusion_alarm';
$isHydrantTemplate = $itemSlug === 'hydrant';
$isEmergencyLightTemplate = $itemSlug === 'emergency_light';
$isCctvTemplate = $itemSlug === 'cctv';
$isSmokeDetectorTemplate = $itemSlug === 'smoke_detector';
$isHeatDetectorTemplate = $itemSlug === 'heat_detector';
$headerTitle = (string) ($layout['headerTitle'] ?? 'CHECKLIST');
$headerSubtitle = (string) ($layout['headerSubtitle'] ?? '');
$signatures = $layout['signatures'] ?? ['Diperiksa oleh', 'Dimonitor oleh', 'Mengetahui'];

$totalGroupedColumns = 0;
foreach ($groupedColumns as $group) {
  $totalGroupedColumns += count($group['columns'] ?? []);
}

$totalColumns = 3 + $totalGroupedColumns;

$formatShortDate = static function (?string $date): string {
  if (empty($date)) {
    return '-';
  }

  $timestamp = strtotime($date);
  if ($timestamp === false) {
    return (string) $date;
  }

  $months = [
    1 => 'Jan',
    2 => 'Feb',
    3 => 'Mar',
    4 => 'Apr',
    5 => 'Mei',
    6 => 'Jun',
    7 => 'Jul',
    8 => 'Agu',
    9 => 'Sep',
    10 => 'Okt',
    11 => 'Nov',
    12 => 'Des',
  ];

  $monthNumber = (int) date('n', $timestamp);

  return date('d', $timestamp) . '-' . ($months[$monthNumber] ?? date('M', $timestamp)) . '-' . date('y', $timestamp);
};

$formatFullDate = static function (?string $date): string {
  if (empty($date)) {
    return '-';
  }

  $timestamp = strtotime($date);
  if ($timestamp === false) {
    return (string) $date;
  }

  $months = [
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

  $monthNumber = (int) date('n', $timestamp);

  return date('d', $timestamp) . ' ' . ($months[$monthNumber] ?? date('F', $timestamp)) . ' ' . date('Y', $timestamp);
};

$displayField = static function (array $inventory, array $column) use ($formatShortDate): string {
  $key = (string) ($column['key'] ?? '');

  if ($key === 'expired_date') {
    return $formatShortDate($inventory['expired_date'] ?? null);
  }

  $value = trim((string) ($inventory[$key] ?? ''));

  return $value !== '' ? $value : '-';
};

$displayStatus = static function (?string $status): string {
  return match ($status) {
    'ok' => 'V',
    'not_ok' => 'X',
    'na' => '-',
    default => '',
  };
};
?>

<style>
  body {
    font-family: sans-serif;
    font-size: 7px;
    color: #111;
  }

  table {
    border-collapse: collapse;
    width: 100%;
  }

  .batch-header-table td,
  .batch-checklist-table th,
  .batch-checklist-table td,
  .finding-grid td,
  .finding-meta td,
  .finding-meta th {
    border: 1px solid #111;
  }

  .batch-header-table {
    margin-bottom: 5px;
    table-layout: fixed;
  }

  .batch-header-table td {
    vertical-align: top;
    padding: 3px;
  }

  .company-cell {
    width: 28%;
  }

  .title-cell {
    width: 30%;
    text-align: center;
  }

  .sign-cell {
    width: 14%;
    text-align: center;
  }

  .company-logo {
    width: 42px;
    text-align: center;
  }

  .company-name {
    font-size: 7.8px;
    font-weight: bold;
    color: #1f55b5;
    margin-bottom: 2px;
  }

  .company-line {
    font-size: 6px;
    line-height: 1.22;
  }

  .title-main {
    font-size: 8px;
    font-weight: bold;
    line-height: 1.2;
    margin-top: 2px;
  }

  .title-sub {
    font-size: 6.8px;
    color: #4968b6;
    margin-top: 2px;
  }

  .sign-label {
    font-size: 6.8px;
    margin-bottom: 22px;
  }

  .sign-line {
    border-top: 1px solid #111;
    height: 18px;
  }

  .period-row {
    font-size: 7px;
    font-weight: bold;
  }

  .period-value {
    font-weight: normal;
  }

  .batch-checklist-table {
    table-layout: fixed;
  }

  .batch-checklist-table th,
  .batch-checklist-table td {
    padding: 2px 2.5px;
    font-size: 6.4px;
    text-align: center;
    vertical-align: middle;
    word-wrap: break-word;
  }

  .batch-checklist-table th {
    font-weight: bold;
  }

  .batch-checklist-table .text-left {
    text-align: left;
  }

  .col-no {
    width: 3.6%;
  }

  .col-location {
    width: 11%;
  }

  .col-pic {
    width: 8%;
  }

  .col-static {
    width: 6.2%;
  }

  .col-question {
    width: 5.4%;
  }

  .answer-cell {
    font-weight: bold;
    font-size: 7px;
  }

  .batch-weekly-table {
    table-layout: fixed;
  }

  .batch-weekly-table th,
  .batch-weekly-table td {
    border: 1px solid #111;
    padding: 1.8px 2px;
    font-size: 5.9px;
    text-align: center;
    vertical-align: middle;
    word-wrap: break-word;
  }

  .batch-weekly-table .text-left {
    text-align: left;
  }

  .batch-weekly-table .month-band {
    font-size: 6.5px;
    font-weight: bold;
    letter-spacing: 0.2px;
  }

  .batch-weekly-table .question-head {
    font-size: 5.8px;
    font-weight: bold;
  }

  .batch-weekly-table .week-head {
    font-size: 5.6px;
    font-weight: bold;
  }

  .batch-weekly-table .week-answer {
    font-size: 6.6px;
    font-weight: bold;
  }

  .batch-weekly-table .col-no {
    width: 3.8%;
  }

  .batch-weekly-table .col-info {
    width: 20%;
  }

  .batch-weekly-table .col-week {
    width: 2.25%;
  }

  .batch-weekly-table .col-note {
    width: 7%;
  }

  .batch-emergency-table {
    table-layout: fixed;
    width: 100%;
  }

  .batch-emergency-table th,
  .batch-emergency-table td {
    border: 1px solid #111;
    padding: 1.9px 2.2px;
    font-size: 7.85px;
    line-height: 1.2;
    text-align: center;
    vertical-align: middle;
    word-wrap: break-word;
  }

  .batch-emergency-table .text-left {
    text-align: left;
  }

  .batch-emergency-table .col-no {
    width: 3.5%;
  }

  .batch-emergency-table .col-location {
    width: 25%;
  }

  .batch-emergency-table .col-type {
    width: 11%;
  }

  .batch-emergency-table .col-question {
    width: 8.25%;
  }

  .batch-emergency-table .emergency-band {
    font-size: 8.6px;
    font-weight: bold;
    line-height: 1.16;
  }

  .batch-emergency-table .emergency-head {
    font-size: 7.65px;
    font-weight: bold;
    line-height: 1.14;
  }

  .batch-smoke-table {
    table-layout: fixed;
    width: 100%;
  }

  .batch-smoke-table th,
  .batch-smoke-table td {
    border: 1px solid #111;
    padding: 1.9px 2.2px;
    font-size: 7.3px;
    line-height: 1.15;
    text-align: center;
    vertical-align: middle;
    word-wrap: break-word;
  }

  .batch-smoke-table .text-left {
    text-align: left;
  }

  .batch-smoke-table .col-no {
    width: 4.2%;
  }

  .batch-smoke-table .col-location {
    width: 28%;
  }

  .batch-smoke-table .col-note {
    width: 17%;
  }

  .batch-smoke-table .smoke-head {
    width: 6.8%;
    height: 92px;
    padding: 0;
  }

  .batch-smoke-table .vertical-head span {
    display: inline-block;
    writing-mode: vertical-rl;
    transform: rotate(180deg);
    line-height: 1.08;
    padding: 6px 0;
  }

  .na-cell {
    background: #ff2d20;
  }

  .batch-cctv-table {
    table-layout: fixed;
    width: 100%;
  }

  .batch-cctv-table th,
  .batch-cctv-table td {
    border: 1px solid #111;
    padding: 1.5px 1.8px;
    font-size: 6px;
    text-align: center;
    vertical-align: middle;
    word-wrap: break-word;
  }

  .batch-cctv-table .text-left {
    text-align: left;
  }

  .batch-cctv-table .col-no {
    width: 3.2%;
  }

  .batch-cctv-table .col-checker {
    width: 14%;
  }

  .batch-cctv-table .col-location {
    width: 14%;
  }

  .batch-cctv-table .col-day {
    width: 2.1%;
    padding: 1.2px 0;
  }

  .batch-cctv-table .col-paraf {
    width: 6.5%;
  }

  .batch-cctv-table .cctv-head {
    font-size: 6.5px;
    font-weight: bold;
    line-height: 1.1;
  }

  .batch-cctv-table .cctv-day-head {
    font-size: 5.8px;
    font-weight: bold;
  }

  .batch-cctv-table .cctv-answer {
    font-size: 7px;
    font-weight: bold;
    height: 14px;
  }

  .batch-cctv-table .cctv-offday,
  .batch-cctv-table .cctv-day-off {
    background: #4b4b4b;
    color: #4b4b4b;
  }

  .batch-cctv-table .cctv-not-ok {
    background: #ffd8d5;
    color: #9a1b16;
  }

  .batch-cctv-footer td {
    height: 18px;
    font-weight: bold;
  }

  .empty-state {
    border: 1px solid #111;
    padding: 10px;
    text-align: center;
    font-size: 7px;
  }

  .legend {
    margin-top: 4px;
    font-size: 6.4px;
  }

  .finding-title {
    margin-top: 8px;
    margin-bottom: 4px;
    font-size: 8px;
    font-weight: bold;
  }

  .finding-grid {
    table-layout: fixed;
  }

  .finding-grid td {
    width: 50%;
    vertical-align: top;
    padding: 0;
  }

  .finding-card {
    padding: 4px;
    page-break-inside: avoid;
  }

  .finding-head {
    font-size: 7px;
    font-weight: bold;
    margin-bottom: 3px;
    padding-bottom: 2px;
    border-bottom: 1px solid #111;
  }

  .finding-meta {
    margin-bottom: 4px;
  }

  .finding-meta td,
  .finding-meta th {
    padding: 2px 3px;
    font-size: 6.3px;
    vertical-align: top;
  }

  .finding-meta th {
    width: 26%;
    text-align: left;
    font-weight: bold;
  }

  .finding-photo-box {
    border: 1px solid #111;
    text-align: center;
    padding: 3px;
    min-height: 120px;
  }

  .finding-photo {
    width: 100%;
    max-height: 120px;
    object-fit: contain;
  }

  .finding-photo-empty {
    font-size: 6.5px;
    color: #666;
    margin-top: 50px;
  }
</style>

<table class="batch-header-table">
  <tr>
    <td class="company-cell">
      <table style="border:0;">
        <tr>
          <td style="border:0; width:48px; padding:0 3px 0 0;">
            <?php if ($logoSrc !== ''): ?>
              <img src="<?= esc($logoSrc) ?>" alt="Logo perusahaan" class="company-logo">
            <?php endif; ?>
          </td>
          <td style="border:0; padding:0;">
            <div class="company-name"><?= esc($company['name'] ?? 'PT. YOUNGHYUN STAR') ?></div>
            <?php foreach (($company['address'] ?? []) as $line): ?>
              <div class="company-line"><?= esc($line) ?></div>
            <?php endforeach; ?>
          </td>
        </tr>
      </table>
    </td>

    <td class="title-cell">
      <div class="title-main"><?= esc($headerTitle) ?></div>
      <?php if ($headerSubtitle !== ''): ?>
        <div class="title-sub"><?= esc($headerSubtitle) ?></div>
      <?php endif; ?>
    </td>

    <?php foreach ($signatures as $signatureLabel): ?>
      <td class="sign-cell">
        <div class="sign-label"><?= esc($signatureLabel) ?>,</div>
        <div class="sign-line"></div>
      </td>
    <?php endforeach; ?>
  </tr>
  <tr>
    <td colspan="<?= 2 + count($signatures) ?>" class="period-row">
      <?= esc($period['label'] ?? 'HARI / TANGGAL') ?> :
      <span class="period-value"><?= esc($period['value'] ?? '') ?></span>
    </td>
  </tr>
</table>

<?php if (empty($inventories)): ?>
  <div class="empty-state">Belum ada inventory untuk item ini.</div>
<?php else: ?>
  <?php if ($isCctvTemplate): ?>
    <?= view('pdf/batch_partials/cctv_table', [
      'inventories' => $inventories,
      'layout' => $layout,
      'dailyChecklistMatrix' => $dailyChecklistMatrix ?? [],
      'dailyPeriods' => $dailyPeriods ?? [],
      'displayStatus' => $displayStatus,
    ]) ?>
  <?php elseif ($isHydrantTemplate): ?>
    <?= view('pdf/batch_partials/hydrant_table', [
      'inventories' => $inventories,
      'masters' => $masters,
      'period' => $period,
      'weeklyChecklistMatrix' => $weeklyChecklistMatrix ?? [],
      'displayStatus' => $displayStatus,
    ]) ?>
  <?php elseif ($isFireAlarmTemplate || $isIntrusionAlarmTemplate): ?>
    <?= view('pdf/batch_partials/fire_alarm_table', [
      'inventories' => $inventories,
      'groupedColumns' => $groupedColumns,
      'period' => $period,
      'weeklyChecklistMatrix' => $weeklyChecklistMatrix ?? [],
      'displayStatus' => $displayStatus,
    ]) ?>
  <?php elseif ($isEmergencyLightTemplate): ?>
    <?= view('pdf/batch_partials/emergency_light_table', [
      'inventories' => $inventories,
      'groupedColumns' => $groupedColumns,
      'checklistMatrix' => $checklistMatrix,
      'displayStatus' => $displayStatus,
    ]) ?>
  <?php elseif ($isSmokeDetectorTemplate || $isHeatDetectorTemplate): ?>
    <?= view('pdf/batch_partials/smoke_detector_table', [
      'inventories' => $inventories,
      'masters' => $masters,
      'checklistMatrix' => $checklistMatrix,
      'displayStatus' => $displayStatus,
    ]) ?>
  <?php else: ?>
    <table class="batch-checklist-table">
      <thead>
        <tr>
          <th rowspan="2" class="col-no">NO</th>
          <th rowspan="2" class="col-location">LOKASI</th>
          <th rowspan="2" class="col-pic">PIC</th>
          <?php foreach ($groupedColumns as $group): ?>
            <th colspan="<?= count($group['columns'] ?? []) ?>">
              <?= esc(strtoupper((string) ($group['label'] ?? 'CHECKLIST'))) ?>
            </th>
          <?php endforeach; ?>
        </tr>
        <tr>
          <?php foreach ($groupedColumns as $group): ?>
            <?php foreach (($group['columns'] ?? []) as $column): ?>
              <th class="<?= esc($column['class'] ?? 'col-question') ?>">
                <?= esc($column['label'] ?? '') ?>
              </th>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($inventories as $index => $inventory): ?>
          <tr>
            <td><?= $index + 1 ?></td>
            <td class="text-left"><?= esc($inventory['specific_area'] ?? '-') ?></td>
            <td class="text-left"><?= esc($inventory['pic'] ?? '-') ?></td>
            <?php foreach ($groupedColumns as $group): ?>
              <?php foreach (($group['columns'] ?? []) as $column): ?>
                <?php if (($column['type'] ?? 'question') === 'field'): ?>
                  <td><?= esc($displayField($inventory, $column)) ?></td>
                <?php else: ?>
                  <?php
                  $templateId = (int) ($column['id'] ?? 0);
                  $status = $checklistMatrix[$inventory['id']][$templateId] ?? null;
                  ?>
                  <td class="answer-cell"><?= esc($displayStatus($status)) ?></td>
                <?php endif; ?>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <div class="legend">
    Keterangan:
    <strong>V</strong> = ceklis,
    <strong>X</strong> = Tidak sesuai,
    <?php if ($isEmergencyLightTemplate): ?>
      <span style="background:#ff2d20; color:#ff2d20; border:1px solid #111;">__</span> = tidak berlaku
    <?php elseif ($isCctvTemplate): ?>
      <span style="display:inline-block; width:10px; height:8px; background:#4b4b4b; border:1px solid #111; vertical-align:middle;"></span> = offday / libur
    <?php else: ?>
      <strong>-</strong> = tidak berlaku
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if (!empty($findings)): ?>
  <div class="finding-title">Finding / Foto Tidak Sesuai</div>

  <table class="finding-grid">
    <?php foreach (array_chunk($findings, 2) as $findingRow): ?>
      <tr>
        <?php foreach ($findingRow as $finding): ?>
          <?php
          $photoPath = trim((string) ($finding['photo_path'] ?? ''));
          $photoSrc = $photoPath !== '' && is_file($photoPath) ? str_replace('\\', '/', $photoPath) : '';
          ?>
          <td>
            <div class="finding-card">
              <div class="finding-head">
                <?= esc($finding['asset_code'] ?? '-') ?> - <?= esc($finding['specific_area'] ?? '-') ?>
              </div>

              <table class="finding-meta">
                <tr>
                  <th>Lokasi</th>
                  <td><?= esc($finding['specific_area'] ?? '-') ?></td>
                </tr>
                <tr>
                  <th>PIC</th>
                  <td><?= esc($finding['pic'] ?? '-') ?></td>
                </tr>
                <tr>
                  <th>Pertanyaan</th>
                  <td><?= esc($finding['question'] ?? '-') ?></td>
                </tr>
                <tr>
                  <th>Catatan</th>
                  <td><?= esc(trim((string) ($finding['remark'] ?? '')) !== '' ? $finding['remark'] : '-') ?></td>
                </tr>
                <tr>
                  <th>Diperiksa</th>
                  <td><?= esc($finding['checked_by'] ?? '-') ?></td>
                </tr>
                <tr>
                  <th>Tanggal</th>
                  <td><?= esc($formatFullDate($finding['check_date'] ?? null)) ?></td>
                </tr>
                <tr>
                  <th>Periode</th>
                  <td><?= esc($finding['display_period'] ?? '-') ?></td>
                </tr>
              </table>

              <div class="finding-photo-box">
                <?php if ($photoSrc !== ''): ?>
                  <img src="<?= esc($photoSrc) ?>" alt="Foto not ok" class="finding-photo">
                <?php else: ?>
                  <div class="finding-photo-empty">Foto not_ok tidak tersedia.</div>
                <?php endif; ?>
              </div>
            </div>
          </td>
        <?php endforeach; ?>

        <?php if (count($findingRow) < 2): ?>
          <td></td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
