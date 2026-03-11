<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card">

  <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">

    <h3 class="card-title mb-2 mb-md-0">
      Laporan Limbah Domestik (IPAL)
    </h3>

    <div class="d-flex gap-2">

      <form method="get">
        <input type="month"
          name="monthpicker"
          value="<?= $year . '-' . $month ?>"
          class="form-control"
          onchange="this.form.submit()">
      </form>

      <a href="<?= base_url('ipal/export?year=' . $year . '&month=' . $month) ?>"
        class="btn btn-success btn-sm">
        Export Excel
      </a>

    </div>

  </div>


  <div class="card-body p-0">

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

    $days = date('t', strtotime("$year-$month-01"));
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
            <th width="120">START</th>
            <th width="120">STOP</th>
            <th width="150">PEMAKAIAN (M³)</th>
            <th>KET</th>
          </tr>
        </thead>

        <tbody>

          <?php for ($d = 1; $d <= $days; $d++):

            $date = "$year-$month-" . sprintf('%02d', $d);
            $dayName = $dayMap[date('l', strtotime($date))];

            $isSunday = date('w', strtotime($date)) == 0;
            $isHoliday = in_array($date, $holidayDates);
            $isOff = ($isSunday || $isHoliday);

            $row = $logs[$date] ?? null;
          ?>

            <tr class="<?= $isOff ? 'offday-row' : '' ?>" data-date="<?= $date ?>">

              <td class="text-center <?= $isOff ? 'offday-cell' : '' ?>">
                <?= $d ?>
              </td>

              <td class="<?= $isOff ? 'offday-cell' : '' ?>">
                <?= $dayName ?>
              </td>

              <td><?= date('d-M-y', strtotime($date)) ?></td>

              <td>
                <input class="form-control start"
                  <?= $isOff ? 'disabled' : '' ?>
                  value="<?= $row['start_meter'] ?? '' ?>">
              </td>

              <td>
                <input class="form-control stop"
                  <?= $isOff ? 'disabled' : '' ?>
                  value="<?= $row['stop_meter'] ?? '' ?>">
              </td>

              <td>
                <input class="form-control pemakaian"
                  <?= $isOff ? 'disabled' : '' ?>
                  value="<?= $row['pemakaian'] ?? '' ?>">
              </td>

              <td>
                <input class="form-control ket"
                  <?= $isOff ? 'disabled' : '' ?>
                  value="<?= $row['ket'] ?? '' ?>">
              </td>

            </tr>

          <?php endfor ?>

        </tbody>
      </table>

    </div>


    <!-- ===================== -->
    <!-- MOBILE CARD -->
    <!-- ===================== -->

    <div class="d-block d-md-none p-2">

      <?php for ($d = 1; $d <= $days; $d++):

        $date = "$year-$month-" . sprintf('%02d', $d);
        $dayName = $dayMap[date('l', strtotime($date))];

        $isSunday = date('w', strtotime($date)) == 0;
        $isHoliday = in_array($date, $holidayDates);
        $isOff = ($isSunday || $isHoliday);

        $row = $logs[$date] ?? null;
      ?>

        <div class="card mb-2 <?= $isOff ? 'border-danger' : '' ?>" data-date="<?= $date ?>">

          <div class="card-body p-2">

            <div class="d-flex justify-content-between mb-2">

              <strong class="<?= $isOff ? 'text-danger' : '' ?>">
                <?= $d ?> - <?= $dayName ?>
              </strong>

              <small><?= date('d M Y', strtotime($date)) ?></small>

            </div>

            <div class="form-group mb-1">
              <label class="small">START</label>
              <input class="form-control start"
                <?= $isOff ? 'disabled' : '' ?>
                value="<?= $row['start_meter'] ?? '' ?>">
            </div>

            <div class="form-group mb-1">
              <label class="small">STOP</label>
              <input class="form-control stop"
                <?= $isOff ? 'disabled' : '' ?>
                value="<?= $row['stop_meter'] ?? '' ?>">
            </div>

            <div class="form-group mb-1">
              <label class="small">PEMAKAIAN (M³)</label>
              <input class="form-control pemakaian"
                <?= $isOff ? 'disabled' : '' ?>
                value="<?= $row['pemakaian'] ?? '' ?>">
            </div>

            <div class="form-group mb-0">
              <label class="small">KET</label>
              <input class="form-control ket"
                <?= $isOff ? 'disabled' : '' ?>
                value="<?= $row['ket'] ?? '' ?>">
            </div>

          </div>
        </div>

      <?php endfor ?>

    </div>

  </div>
</div>


<style>
  .offday-row {
    background: #ffe6e6 !important;
  }

  .offday-cell {
    background: #dc3545 !important;
    color: #fff;
    font-weight: 600;
  }

  .table td,
  .table th {
    vertical-align: middle;
  }

  @media (max-width:768px) {

    .container-fluid {
      max-width: 100% !important;
      padding-left: 8px !important;
      padding-right: 8px !important;
    }

    .content-wrapper {
      overflow-x: hidden;
    }

    .card {
      width: 100%;
    }
</style>


<script>
  document.querySelectorAll('.start,.stop,.pemakaian,.ket')
    .forEach(input => {

      input.addEventListener('change', function() {

        let container = this.closest('tr') || this.closest('.card');

        let date = container.dataset.date;

        let start = parseFloat(container.querySelector('.start').value) || 0;
        let stop = parseFloat(container.querySelector('.stop').value) || 0;

        // AUTO HITUNG PEMAKAIAN
        let pemakaian = stop - start;

        if (pemakaian < 0) {
          pemakaian = 0;
        }

        // isi field pemakaian otomatis
        container.querySelector('.pemakaian').value = pemakaian.toFixed(2);

        fetch('/ipal/save', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: new URLSearchParams({

            date: date,
            start: start,
            stop: stop,
            pemakaian: pemakaian,
            ket: container.querySelector('.ket').value

          })

        });

      });

    });
</script>

<?= $this->endSection() ?>