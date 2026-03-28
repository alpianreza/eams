<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

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

$dayMap = [
  'Sunday' => 'Minggu',
  'Monday' => 'Senin',
  'Tuesday' => 'Selasa',
  'Wednesday' => 'Rabu',
  'Thursday' => 'Kamis',
  'Friday' => 'Jumat',
  'Saturday' => 'Sabtu'
];

$days = (int) date('t', strtotime("$year-$month-01"));
$monthLabel = ($monthMap[$month] ?? date('F', strtotime("$year-$month-01"))) . ' ' . $year;
$totalPemakaian = 0.0;
$filledDays = 0;

for ($d = 1; $d <= $days; $d++) {
  $date = "$year-$month-" . sprintf('%02d', $d);
  $row = $logs[$date] ?? null;
  if ($row && $row['pemakaian'] !== null && $row['pemakaian'] !== '') {
    $filledDays++;
    $totalPemakaian += (float) $row['pemakaian'];
  }
}
?>

<div class="utility-shell utility-ipal-shell">
  <section class="card utility-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="utility-kicker mb-1">IPAL Monitoring</p>
        <h5 class="mb-1 fw-bold">Laporan Limbah Domestik (IPAL)</h5>
        <p class="text-muted mb-0">Input meter start/stop harian, pemakaian otomatis dihitung.</p>
      </div>

      <div class="utility-actions d-flex flex-wrap align-items-center gap-2">
        <form method="get" class="utility-month-form">
          <input type="month"
            name="monthpicker"
            value="<?= $year . '-' . $month ?>"
            class="form-control form-control-sm"
            onchange="this.form.submit()">
        </form>

        <a href="<?= base_url('ipal/export?year=' . $year . '&month=' . $month) ?>"
          class="btn btn-success btn-sm d-inline-flex align-items-center gap-1">
          <i class="bi bi-file-earmark-spreadsheet"></i>
          Export Excel
        </a>
      </div>
    </div>
  </section>

  <section class="row g-2 mb-3 utility-stat-grid">
    <div class="col-12 col-md-4">
      <div class="card utility-stat-card no-lift">
        <div class="card-body">
          <div class="utility-stat-label">Periode</div>
          <div class="utility-stat-value"><?= esc($monthLabel) ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4">
      <div class="card utility-stat-card no-lift">
        <div class="card-body">
          <div class="utility-stat-label">Terisi</div>
          <div class="utility-stat-value"><?= esc((string)$filledDays) ?> hari</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4">
      <div class="card utility-stat-card no-lift">
        <div class="card-body">
          <div class="utility-stat-label">Total Pemakaian</div>
          <div class="utility-stat-value"><?= number_format($totalPemakaian, 2) ?> m3</div>
        </div>
      </div>
    </div>
  </section>

  <section class="card utility-table-card no-lift">
    <div class="card-body p-0">
      <div class="table-responsive d-none d-md-block utility-table-wrap">
        <table class="table table-bordered align-middle mb-0 utility-table">
          <thead>
            <tr>
              <th width="56" class="text-center">No</th>
              <th width="120">Hari</th>
              <th width="140">Tanggal</th>
              <th width="120">Start</th>
              <th width="120">Stop</th>
              <th width="140">Pemakaian (m3)</th>
              <th>Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <?php for ($d = 1; $d <= $days; $d++): ?>
              <?php
              $date = "$year-$month-" . sprintf('%02d', $d);
              $dayName = $dayMap[date('l', strtotime($date))] ?? date('l', strtotime($date));
              $isOff = is_date_offday($date, $holidayDates ?? []);
              $row = $logs[$date] ?? null;
              ?>
              <tr class="<?= $isOff ? 'utility-offday-row' : '' ?>" data-date="<?= esc($date) ?>">
                <td class="text-center fw-semibold"><?= $d ?></td>
                <td><?= esc($dayName) ?></td>
                <td><?= esc(date('d M Y', strtotime($date))) ?></td>
                <td>
                  <input type="number" step="0.01" class="form-control form-control-sm start" <?= $isOff ? 'disabled' : '' ?> value="<?= esc((string)($row['start_meter'] ?? '')) ?>">
                </td>
                <td>
                  <input type="number" step="0.01" class="form-control form-control-sm stop" <?= $isOff ? 'disabled' : '' ?> value="<?= esc((string)($row['stop_meter'] ?? '')) ?>">
                </td>
                <td>
                  <input type="number" step="0.01" class="form-control form-control-sm pemakaian" <?= $isOff ? 'disabled' : '' ?> value="<?= esc((string)($row['pemakaian'] ?? '')) ?>" readonly>
                </td>
                <td>
                  <input type="text" class="form-control form-control-sm ket" <?= $isOff ? 'disabled' : '' ?> value="<?= esc((string)($row['ket'] ?? '')) ?>">
                </td>
              </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>

      <div class="d-block d-md-none p-2">
        <?php for ($d = 1; $d <= $days; $d++): ?>
          <?php
          $date = "$year-$month-" . sprintf('%02d', $d);
          $dayName = $dayMap[date('l', strtotime($date))] ?? date('l', strtotime($date));
          $isOff = is_date_offday($date, $holidayDates ?? []);
          $row = $logs[$date] ?? null;
          ?>
          <article class="card utility-mobile-card mb-2 <?= $isOff ? 'utility-mobile-offday' : '' ?>" data-date="<?= esc($date) ?>">
            <div class="card-body p-3">
              <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <div>
                  <div class="fw-semibold"><?= $d ?> - <?= esc($dayName) ?></div>
                  <div class="text-muted small"><?= esc(date('d M Y', strtotime($date))) ?></div>
                </div>
                <?php if ($isOff): ?>
                  <span class="badge text-bg-danger">Libur</span>
                <?php endif; ?>
              </div>

              <div class="row g-2 mb-2">
                <div class="col-6">
                  <label class="form-label small mb-1">Start</label>
                  <input type="number" step="0.01" class="form-control start" <?= $isOff ? 'disabled' : '' ?> value="<?= esc((string)($row['start_meter'] ?? '')) ?>">
                </div>
                <div class="col-6">
                  <label class="form-label small mb-1">Stop</label>
                  <input type="number" step="0.01" class="form-control stop" <?= $isOff ? 'disabled' : '' ?> value="<?= esc((string)($row['stop_meter'] ?? '')) ?>">
                </div>
              </div>

              <div class="mb-2">
                <label class="form-label small mb-1">Pemakaian (m3)</label>
                <input type="number" step="0.01" class="form-control pemakaian" <?= $isOff ? 'disabled' : '' ?> value="<?= esc((string)($row['pemakaian'] ?? '')) ?>" readonly>
              </div>

              <div>
                <label class="form-label small mb-1">Keterangan</label>
                <input type="text" class="form-control ket" <?= $isOff ? 'disabled' : '' ?> value="<?= esc((string)($row['ket'] ?? '')) ?>">
              </div>
            </div>
          </article>
        <?php endfor; ?>
      </div>
    </div>
  </section>

  <div class="text-muted small mt-2 px-1" id="ipalSaveState">
    Perubahan tersimpan otomatis saat nilai diubah.
  </div>
</div>

<script>
  (() => {
    const saveUrl = "<?= base_url('ipal/save') ?>";
    const saveState = document.getElementById("ipalSaveState");

    const parseNum = (value) => {
      const n = parseFloat(value);
      return Number.isFinite(n) ? n : 0;
    };

    const setSaveState = (text, tone = "muted") => {
      if (!saveState) return;
      saveState.className = "small mt-2 px-1";
      if (tone === "success") saveState.classList.add("text-success");
      else if (tone === "error") saveState.classList.add("text-danger");
      else saveState.classList.add("text-muted");
      saveState.textContent = text;
    };

    let saveTimer = null;

    const recalcPemakaian = (container) => {
      const start = parseNum(container.querySelector(".start")?.value);
      const stop = parseNum(container.querySelector(".stop")?.value);
      const pemakaian = Math.max(0, stop - start);
      const pemakaianInput = container.querySelector(".pemakaian");
      if (pemakaianInput) {
        pemakaianInput.value = pemakaian.toFixed(2);
      }
      return { start, stop, pemakaian };
    };

    const saveRow = (container) => {
      const date = container.dataset.date;
      if (!date) return;

      const { start, stop, pemakaian } = recalcPemakaian(container);
      const ket = container.querySelector(".ket")?.value || "";

      setSaveState("Menyimpan...", "muted");

      fetch(saveUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          "X-Requested-With": "XMLHttpRequest"
        },
        body: new URLSearchParams({
          date,
          start: String(start),
          stop: String(stop),
          pemakaian: String(pemakaian),
          ket
        })
      })
        .then((res) => {
          if (!res.ok) throw new Error("Save gagal");
          return res.json();
        })
        .then(() => {
          setSaveState("Data berhasil disimpan.", "success");
        })
        .catch(() => {
          setSaveState("Gagal menyimpan data.", "error");
        });
    };

    document.addEventListener("input", (event) => {
      const input = event.target;
      if (!(input instanceof HTMLElement)) return;
      if (!input.matches(".start, .stop, .ket")) return;

      const container = input.closest("tr[data-date], article[data-date]");
      if (!container) return;

      recalcPemakaian(container);

      clearTimeout(saveTimer);
      saveTimer = setTimeout(() => {
        saveRow(container);
      }, 450);
    });
  })();
</script>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/utility-ops.css?v=' . filemtime(FCPATH . 'assets/css/utility-ops.css')) ?>">
<?= $this->endSection() ?>

