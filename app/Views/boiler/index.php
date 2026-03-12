<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card">

  <div class="card-header d-flex justify-content-between align-items-center flex-wrap">

    <h3 class="card-title mb-2 mb-md-0">
      Laporan Pemakaian Bahan Bakar Boiler
    </h3>

    <div class="d-flex align-items-center gap-2">


      <form method="get">
        <input type="month"
          name="monthpicker"
          value="<?= $year . '-' . $month ?>"
          class="form-control"
          onchange="this.form.submit()">
      </form>


      <a href="<?= base_url('boiler/export?year=' . $year . '&month=' . $month) ?>"
        class="btn btn-success btn-sm">
        Export Excel
      </a>

    </div>

  </div>

  <div class="card-body">

    <?php
    $totalPoly = 0;
    $totalKg   = 0;
    $daysInMonth = date('t', strtotime("$year-$month-01"));
    ?>

    <!-- ===================== -->
    <!-- DESKTOP TABLE -->
    <!-- ===================== -->
    <div class="table-responsive d-none d-md-block">

      <table class="table table-bordered table-sm mb-0">
        <thead class="text-center bg-light">
          <tr>
            <th width="50">No</th>
            <th width="120">Hari</th>
            <th width="140">Tanggal</th>
            <th width="120">Polybag</th>
            <th width="150">KG</th>
          </tr>
        </thead>
        <tbody>

          <?php
          $dayMap = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu'
          ];

          for ($d = 1; $d <= $daysInMonth; $d++):

            $date = "$year-$month-" . sprintf('%02d', $d);
            $dayName = $dayMap[date('l', strtotime($date))];
            $isSunday = date('w', strtotime($date)) == 0;
            $isHoliday = in_array($date, $holidayDates);
            $isOff = ($isSunday || $isHoliday);

            $row = $logs[$date] ?? null;
            $poly = $row['total_polybag'] ?? '';
            $kg   = $row['total_kg'] ?? '';

            if ($row) {
              $totalPoly += $row['total_polybag'];
              $totalKg   += $row['total_kg'];
            }
          ?>

            <tr>
              <td class="text-center <?= $isOff ? 'offday-cell' : '' ?>">
                <?= $d ?>
              </td>
              <td class="<?= $isOff ? 'offday-cell' : '' ?>">
                <?= $dayName ?>
              </td>
              <td>
                <a href="<?= base_url('boiler/detail/' . $date) ?>" class="text-reset">
                  <?= date('d-M-y', strtotime($date)) ?>
                </a>
              </td>
              <td class="text-center"><?= $poly ?></td>
              <td class="text-center"><?= $kg ? number_format($kg, 2) : '' ?></td>
            </tr>

          <?php endfor; ?>

          <tr class="font-weight-bold bg-light">
            <td colspan="3" class="text-center">TOTAL BULAN</td>
            <td class="text-center"><?= $totalPoly ?></td>
            <td class="text-center"><?= number_format($totalKg, 2) ?></td>
          </tr>

        </tbody>
      </table>

    </div>


    <!-- ===================== -->
    <!-- MOBILE VERSION -->
    <!-- ===================== -->
    <div class="d-block d-md-none">

      <?php
      $totalPoly = 0;
      $totalKg   = 0;

      for ($d = 1; $d <= $daysInMonth; $d++):

        $date = "$year-$month-" . sprintf('%02d', $d);
        $dayName = $dayMap[date('l', strtotime($date))];
        $isSunday = date('w', strtotime($date)) == 0;
        $isHoliday = in_array($date, $holidayDates);
        $isOff = ($isSunday || $isHoliday);

        $row = $logs[$date] ?? null;
        $poly = $row['total_polybag'] ?? 0;
        $kg   = $row['total_kg'] ?? 0;

        $totalPoly += $poly;
        $totalKg   += $kg;
      ?>

        <div class="card mb-2 <?= $isOff ? 'offday-mobile' : '' ?>">
          <div class="card-body p-2">

            <div class="d-flex justify-content-between">
              <strong class="<?= $isOff ? 'text-danger' : '' ?>">
                <?= $d ?> - <?= $dayName ?>
              </strong>

              <?php if ($isOff): ?>
                <span class="badge badge-danger">LIBUR</span>
              <?php endif; ?>
              <a href="<?= base_url('boiler/detail/' . $date) ?>"
                class="btn btn-sm btn-outline-primary">
                Detail
              </a>
            </div>

            <div class="text-muted small mb-2">
              <?= date('d M Y', strtotime($date)) ?>
            </div>

            <div class="d-flex justify-content-between">
              <span>Polybag</span>
              <strong><?= $poly ?></strong>
            </div>

            <div class="d-flex justify-content-between">
              <span>KG</span>
              <strong><?= number_format($kg, 2) ?></strong>
            </div>

          </div>
        </div>

      <?php endfor; ?>

      <div class="card bg-light mt-3">
        <div class="card-body p-2">
          <strong>Total Bulan</strong>
          <div class="d-flex justify-content-between">
            <span>Polybag</span>
            <strong><?= $totalPoly ?></strong>
          </div>
          <div class="d-flex justify-content-between">
            <span>KG</span>
            <strong><?= number_format($totalKg, 2) ?></strong>
          </div>
        </div>
      </div>

    </div>

  </div>
</div>

<style>
  .offday-cell {
    background-color: #dc3545 !important;
    color: #fff !important;
    font-weight: 600;
  }

  .table td,
  .table th {
    vertical-align: middle;
  }

  .offday-mobile {
    background-color: #fdeaea !important;
    border: 1px solid #dc3545 !important;
  }
</style>

<?= $this->endSection() ?>