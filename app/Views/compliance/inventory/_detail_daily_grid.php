<div class="table-responsive">
  <table class="table table-bordered text-center">
    <thead>
      <tr>
        <th>Item Pengecekan</th>
        <?php foreach ($dailyDays as $date): ?>
          <th><?= date('j', strtotime($date)) ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>

      <?php foreach ($questions as $q): ?>
        <tr>
          <td class="text-start"><?= esc($q['question']) ?></td>
          <?php foreach ($dailyDays as $date): ?>

            <?php
            $isSunday  = date('w', strtotime($date)) == 0;
            $isHoliday = in_array($date, $holidayDates ?? []);

            $isOffDay = $isSunday || $isHoliday;
            ?>

            <td class="<?= $isOffDay ? 'bg-offday text-muted' : '' ?>">

              <?php if ($isOffDay): ?>

              <?php else: ?>
                <?php
                $status = $dataGrid[$q['id']][$date] ?? null;

                if ($status === 'ok') echo '✓';
                elseif ($status === 'not_ok') echo '✗';
                else echo '';
                ?>
              <?php endif; ?>

            </td>

          <?php endforeach; ?>

        </tr>
      <?php endforeach; ?>

    </tbody>
  </table>
</div>