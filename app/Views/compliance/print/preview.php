<!DOCTYPE html>
<html>

<head>

  <title>Print Preview</title>

  <style>
    body {
      font-family: Arial;
    }

    .page {
      page-break-after: always;
    }
  </style>

</head>

<body>

  <?php foreach ($inventories as $inv): ?>

    <div class="page">

      <h3><?= $inv['asset_code'] ?> — <?= $inv['specific_area'] ?></h3>

      <?php
      // ambil frequency
      $freq = $inv['checklist_frequency'] ?? 'monthly';
      ?>

      <?php if ($freq === 'daily'): ?>

        <?= view('compliance/print/templates/recap_daily', [
          'inventory' => $inv,
          'months' => $months,
          'years' => $years
        ]) ?>

      <?php elseif ($freq === 'weekly'): ?>

        <?= view('compliance/print/templates/recap_weekly', [
          'inventory' => $inv,
          'months' => $months,
          'years' => $years
        ]) ?>

      <?php else: ?>

        <?php foreach ($years as $year): ?>

          <div class="page">

            <?= view('compliance/print/templates/recap_item_yearly', [
              'inventory' => $inv,
              'year' => $year
            ]) ?>

          </div>

        <?php endforeach; ?>
      <?php endif; ?>

    </div>

  <?php endforeach; ?>

</body>

</html>