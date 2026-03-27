<?php
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

<div class="table-responsive inventory-grid-table-wrap">
  <table class="table table-bordered text-center mb-0 inventory-grid-table">
    <thead class="table-light">
      <tr>
        <th rowspan="2">Hari</th>
        <th rowspan="2">Tanggal</th>
        <?php foreach ($questions as $q): ?>
          <th colspan="3"><?= esc($q['question']) ?></th>
        <?php endforeach; ?>
      </tr>

      <tr>
        <?php foreach ($questions as $q): ?>
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

          <?php foreach ($questions as $q): ?>
            <?php foreach (['PG', 'SI', 'SO'] as $slot): ?>
              <?php $status = $dataGrid[$q['id']][$date][$slot] ?? null; ?>

              <td class="<?= $isOffDay ? 'bg-offday text-muted' : '' ?>">
                <?php if ($isOffDay): ?>
                  &nbsp;
                <?php elseif ($status === 'ok'): ?>
                  <i class="bi bi-check-circle-fill text-success" title="Sesuai"></i>
                <?php elseif ($status === 'not_ok'): ?>
                  <i class="bi bi-x-circle-fill text-danger" title="Tidak sesuai"></i>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
