<style>
  body {
    font-family: sans-serif;
    font-size: 8px;
  }

  table {
    border-collapse: collapse;
    width: 100%;
    table-layout: fixed;
  }

  th,
  td {
    border: 1px solid #000;
    padding: 2px;
    font-size: 7px;
    text-align: center;
  }

  thead th {
    background: #f0f0f0;
    font-weight: bold;
  }

  .day-col {
    width: 42px;
  }

  .date-col {
    width: 28px;
  }

  .offday {
    background: #f4c2c2;
  }

  .question-col {
    white-space: nowrap;
    overflow: hidden;
  }
</style>

<?php
$logoPath = FCPATH . 'assets/images/company/logo.png';
$bulanNama = date('F', strtotime($year . '-' . $month . '-01'));

$dayMap = [
  'Sunday' => 'Minggu',
  'Monday' => 'Senin',
  'Tuesday' => 'Selasa',
  'Wednesday' => 'Rabu',
  'Thursday' => 'Kamis',
  'Friday' => 'Jumat',
  'Saturday' => 'Sabtu',
];
?>

<table style="width:100%; border:none; margin-bottom:6px;">
  <tr>
    <td style="width:90px; border:none; padding-left:300px;">
      <?php if (file_exists($logoPath)): ?>
        <img src="<?= $logoPath ?>" style="width:60px;">
      <?php endif; ?>
    </td>
    <td style="border:none; text-align:center; padding-right:330px;">
      <div style="font-size:14px; font-weight:bold;">PT. YOUNGHYUN STAR</div>
      <div style="font-size:13px; font-weight:bold;">CHECKLIST PENGECEKAN <?= strtoupper((string) $itemName) ?></div>
    </td>
  </tr>
</table>

<div style="width:100%; font-size:9px; margin-bottom:10px;">
  <div style="width:40%; float:left;">
    <strong>Bulan:</strong> <?= $bulanNama ?> <?= $year ?>
  </div>
  <div style="width:25%; float:left;">
    <strong>Area:</strong> <?= esc($specificArea ?? '-') ?>
  </div>
  <div style="width:20%; float:right;">
    <strong>No Inventaris:</strong> <?= esc($inventory['asset_code']) ?>
  </div>
  <div style="clear:both;"></div>
</div>

<table>
  <thead>
    <tr>
      <th rowspan="2" class="day-col">Hari</th>
      <th rowspan="2" class="date-col">Tanggal</th>
      <?php foreach ($masters as $q): ?>
        <th colspan="3" class="question-col"><?= esc($q['question']) ?></th>
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
        <td><?= esc($dayLabel) ?></td>
        <td><?= date('j', strtotime($date)) ?></td>

        <?php foreach ($masters as $q): ?>
          <?php foreach (['PG', 'SI', 'SO'] as $slot): ?>
            <?php
            $status = $dailyGrid[$q['id']][$date][$slot] ?? null;
            $symbol = match ($status) {
              'ok' => '&#10003;',
              'not_ok' => '&#10007;',
              'na' => '-',
              default => '',
            };
            ?>
            <td class="<?= $isOffDay ? 'offday' : '' ?>">
              <?= $isOffDay ? '' : $symbol ?>
            </td>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<p style="margin-top:5px; font-size:8px;">
  Keterangan: &#10003; = sesuai, &#10007; = tidak sesuai, - = tidak berlaku
</p>

<?php if (!empty($findings)): ?>
  <div style="page-break-before: always;"></div>

  <h4 style="margin-bottom:10px;font-size:12px;">
    DETAIL TEMUAN BULAN <?= strtoupper($bulanNama) ?> <?= $year ?>
  </h4>

  <?php $rows = array_chunk($findings, 2); ?>
  <table style="width:100%; border-collapse:collapse;">
    <?php foreach ($rows as $index => $pair): ?>
      <tr>
        <?php foreach ($pair as $i => $log): ?>
          <?php
          $questionName = '';
          foreach ($masters as $q) {
            if ($q['id'] == $log['checklist_template_id']) {
              $questionName = $q['question'];
              break;
            }
          }
          ?>

          <td style="width:50%; vertical-align:top; padding:4px 8px;">
            <div style="font-size:9px;border-bottom:1px solid #ddd;padding-bottom:4px;margin-bottom:6px;">
              <strong><?= ($index * 2) + $i + 1 ?>.</strong>
              <span style="font-style:italic;"><?= esc($log['display_period']) ?></span><br>
              <span style="margin-left:10px;"><?= esc($questionName) ?></span><br>
              <?php if (!empty($log['remark'])): ?>
                <span style="margin-left:10px;color:#555;"><?= esc($log['remark']) ?></span>
              <?php endif; ?>
            </div>
          </td>
        <?php endforeach; ?>

        <?php if (count($pair) == 1): ?>
          <td style="width:50%"></td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
