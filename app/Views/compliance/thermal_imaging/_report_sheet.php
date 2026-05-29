<?php
$monthMap = [
  '01' => 'Januari',
  '02' => 'Februari',
  '03' => 'Maret',
  '04' => 'April',
  '05' => 'Mei',
  '06' => 'Juni',
  '07' => 'Juli',
  '08' => 'Agustus',
  '09' => 'September',
  '10' => 'Oktober',
  '11' => 'November',
  '12' => 'Desember',
];

$formatDate = static function (string $date) use ($monthMap): string {
  $timestamp = strtotime($date);
  if (! $timestamp) {
    return '-';
  }

  return date('d', $timestamp) . ' ' . ($monthMap[date('m', $timestamp)] ?? date('F', $timestamp)) . ' ' . date('Y', $timestamp);
};

$formatTemp = static function ($value): string {
  $number = number_format((float) $value, 2, '.', '');
  return rtrim(rtrim($number, '0'), '.') . '°C';
};

$imageSrc = static function (?string $path) use ($isPdf): string {
  $path = trim((string) $path);
  if ($path === '') {
    return '';
  }

  if (! $isPdf) {
    return base_url($path);
  }

  $fullPath = FCPATH . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
  if (! is_file($fullPath)) {
    return '';
  }

  $mime = mime_content_type($fullPath) ?: 'image/jpeg';
  return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($fullPath));
};
?>

<div class="thermal-report-sheet">
  <div class="thermal-top-line"></div>

  <h1>Thermal Imaging Inspection Report</h1>
  <h2>General Information</h2>

  <table class="thermal-info-table">
    <tr>
      <td class="thermal-info-label">Inspection Date</td>
      <td class="thermal-info-separator">:</td>
      <td><?= esc($formatDate((string) $report['inspection_date'])) ?></td>
    </tr>
    <tr>
      <td class="thermal-info-label">Inspector Name</td>
      <td class="thermal-info-separator">:</td>
      <td><?= esc($report['inspector_name']) ?></td>
    </tr>
    <tr>
      <td class="thermal-info-label">Facility</td>
      <td class="thermal-info-separator">:</td>
      <td><?= esc($report['facility']) ?></td>
    </tr>
  </table>

  <div class="thermal-section-title">Inspection Report</div>
  <div class="thermal-area-box"><?= esc($report['area_name']) ?></div>

  <table class="thermal-output-table">
    <thead>
      <tr>
        <th class="col-no">No.</th>
        <th class="col-image">Thermal Image</th>
        <th class="col-location">Location</th>
        <th class="col-findings">Findings</th>
        <th class="col-recommendation">Recommendation</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $index => $item): ?>
        <?php $src = $imageSrc($item['thermal_image'] ?? null); ?>
        <tr>
          <td class="thermal-no"><?= $index + 1 ?></td>
          <td class="thermal-image-cell">
            <?php if ($src !== ''): ?>
              <img src="<?= esc($src) ?>" alt="Thermal Image <?= $index + 1 ?>">
            <?php endif; ?>
          </td>
          <td class="thermal-location-cell">
            <?= esc($item['location_name']) ?><br>
            (<?= esc($formatTemp($item['celsius'])) ?>)
          </td>
          <td><?= nl2br(esc($item['findings'] ?? '')) ?></td>
          <td><?= nl2br(esc($item['recommendation'] ?? '')) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
