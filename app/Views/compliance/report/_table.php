<div class="d-flex justify-content-between align-items-center mb-3">

  <div>
    <?php if ($prev): ?>
      <button class="btn btn-light border navInventory"
        style="width:36px;height:36px;border-radius:50%;"
        data-id="<?= $prev ?>">
        <i class="fa-solid fa-chevron-left"></i>
      </button>
    <?php endif; ?>
  </div>

  <strong class="fs-5">
    <?= esc($inventory['asset_code']) ?>
  </strong>

  <div>
    <?php if ($next): ?>
      <button class="btn btn-outline-secondary btn-sm navInventory"
        data-id="<?= $next ?>"
        title="Next">
        <i class="fa-solid fa-chevron-right"></i>
      </button>
    <?php endif; ?>
  </div>

</div>

<hr>
<?php if ($frequency === 'monthly'): ?>

  <?= view('compliance/report/_monthly', [
    'inventory'   => $inventory,
    'masters'     => $masters,
    'monthlyGrid' => $monthlyGrid,
    'findingsByMonth' => $findingsByMonth,
    'checkerByMonth' => $checkerByMonth,
    'year'        => $year,
    'month'       => $month,
    'role'        => $role
  ]) ?>

<?php elseif ($frequency === 'daily'): ?>

  <?= view('compliance/report/_daily', [
    'inventory'   => $inventory,
    'masters'     => $masters,
    'dailyGrid'   => $dailyGrid,
    'dailyDays'   => $dailyDays,
    'checkerByDate' => $checkerByDate,
    'findings'    => $findings,
    'year'        => $year,
    'month'       => $month,
    'holidayDates' => $holidayDates ?? [],
    'role'        => $role
  ]) ?>

<?php elseif ($frequency === 'weekly'): ?>

  <?= view('compliance/report/_weekly', [
    'inventory'   => $inventory,
    'masters'     => $masters,
    'weeklyGrid'  => $weeklyGrid,
    'checkerByWeek' => $checkerByWeek,
    'findings'    => $findings,
    'year'        => $year,
    'month'       => $month,
    'role'        => $role
  ]) ?>

<?php endif; ?>