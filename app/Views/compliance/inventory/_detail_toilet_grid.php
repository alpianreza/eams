<div class="table-responsive">
  <table class="table table-bordered text-center">

    <thead>

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
        $isSunday  = date('w', strtotime($date)) == 0;
        $isHoliday = in_array($date, $holidayDates ?? []);
        $isOffDay  = $isSunday || $isHoliday;
        ?>

        <tr>

          <td><?= date('l', strtotime($date)) ?></td>
          <td><?= date('j', strtotime($date)) ?></td>

          <?php foreach ($questions as $q): ?>

            <?php foreach (['PG', 'SI', 'SO'] as $slot): ?>

              <td class="<?= $isOffDay ? 'bg-offday text-muted' : '' ?>">

                <?php if (!$isOffDay): ?>

                  <?php
                  $status = $dataGrid[$q['id']][$date][$slot] ?? null;

                  if ($status === 'ok') {
                    echo '✓';
                  } elseif ($status === 'not_ok') {
                    echo '✗';
                  } else {
                    echo '';
                  }
                  ?>

                <?php endif; ?>

              </td>

            <?php endforeach; ?>

          <?php endforeach; ?>

        </tr>

      <?php endforeach; ?>

    </tbody>

  </table>
</div>