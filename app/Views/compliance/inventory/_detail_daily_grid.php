<div class="table-responsive inventory-grid-table-wrap">
  <table class="table table-bordered text-center mb-0 inventory-grid-table">
    <thead class="table-light">
      <tr>
        <th class="text-start sticky-left">Item Pengecekan</th>
        <?php foreach ($dailyDays as $date): ?>
          <th><?= date('j', strtotime($date)) ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>

    <tbody>
      <?php foreach ($questions as $q): ?>
        <tr>
          <td class="text-start sticky-left"><?= esc($q['question']) ?></td>

          <?php foreach ($dailyDays as $date): ?>
            <?php
            $isOffDay = is_date_offday($date, $holidayDates ?? []);
            $status = $dataGrid[$q['id']][$date] ?? null;
            ?>

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
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
