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

$daysInMonth = (int) date('t', strtotime("$year-$month-01"));
$monthLabel = ($monthMap[$month] ?? date('F', strtotime("$year-$month-01"))) . ' ' . $year;
$filledDays = count($logs);
$timeOptions = [];
for ($hour = 7; $hour <= 17; $hour++) {
  foreach ([0, 30] as $minute) {
    $timeOptions[] = sprintf('%02d:%02d', $hour, $minute);
  }
}
?>

<div class="utility-shell utility-pdam-shell">
  <section class="card utility-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="utility-kicker mb-1">Boiler & Utility</p>
        <h5 class="mb-1 fw-bold">Pengecekan Air PDAM Boiler</h5>
        <p class="text-muted mb-0">Monitoring harian pembacaan meter air PDAM boiler per tanggal.</p>
      </div>

      <div class="utility-actions d-flex flex-wrap align-items-center gap-2">
        <form method="get" class="utility-month-form">
          <input type="month"
            name="monthpicker"
            value="<?= $year . '-' . $month ?>"
            class="form-control form-control-sm"
            onchange="this.form.submit()">
        </form>

        <a href="<?= base_url('pdam-water-boiler/export-excel?year=' . $year . '&month=' . $month) ?>"
          class="btn btn-success btn-sm d-inline-flex align-items-center gap-1">
          <i class="bi bi-file-earmark-spreadsheet"></i>
          Export Excel
        </a>

        <a href="<?= base_url('pdam-water-boiler/export-pdf?year=' . $year . '&month=' . $month) ?>"
          target="_blank"
          class="btn btn-danger btn-sm d-inline-flex align-items-center gap-1">
          <i class="bi bi-file-earmark-pdf"></i>
          Export PDF
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
          <div class="utility-stat-label">Hari Terisi</div>
          <div class="utility-stat-value"><?= number_format($filledDays, 0, ',', '.') ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4">
      <div class="card utility-stat-card no-lift">
        <div class="card-body">
          <div class="utility-stat-label">Meter Terakhir</div>
          <div class="utility-stat-value"><?= $latestMeter !== null ? number_format((float) $latestMeter, 2, ',', '.') : '-' ?></div>
        </div>
      </div>
    </div>
  </section>

  <section class="card utility-table-card no-lift">
    <div class="card-body p-0">
      <div class="table-responsive d-none d-md-block utility-table-wrap">
        <table class="table table-bordered align-middle mb-0 utility-table" id="pdamTable">
          <thead>
            <tr>
              <th width="56" class="text-center">No</th>
              <th width="130">Hari</th>
              <th width="150">Tanggal</th>
              <th width="120">Jam</th>
              <th width="160">Meteran Air</th>
              <th>Keterangan</th>
              <th width="110" class="text-center">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php for ($d = 1; $d <= $daysInMonth; $d++): ?>
              <?php
              $date = "$year-$month-" . sprintf('%02d', $d);
              $dayName = $dayMap[date('l', strtotime($date))] ?? date('l', strtotime($date));
              $isOff = is_date_offday($date, $holidayDates ?? []);
              $row = $logs[$date] ?? null;
              $timeValue = $row && !empty($row['log_time']) ? (string) $row['log_time'] : '';
              $meterValue = $row && $row['meter_reading'] !== null ? (string) $row['meter_reading'] : '';
              $noteValue = $row ? (string) ($row['note'] ?? '') : '';
              ?>
              <tr class="<?= $isOff ? 'utility-offday-row' : '' ?>" data-date="<?= esc($date) ?>" data-offday="<?= $isOff ? '1' : '0' ?>">
                <td class="text-center fw-semibold"><?= $d ?></td>
                <td><?= esc($dayName) ?></td>
                <td><?= esc(date('d M Y', strtotime($date))) ?></td>
                <td>
                  <select
                    class="form-select form-select-sm pdam-time"
                    <?= $isOff ? 'disabled' : '' ?>>
                    <option value="">Pilih Jam</option>
                    <?php foreach ($timeOptions as $option): ?>
                      <option value="<?= esc($option) ?>" <?= $timeValue === $option ? 'selected' : '' ?>>
                        <?= esc($option) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td>
                  <input
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-control form-control-sm pdam-meter"
                    value="<?= esc($meterValue) ?>"
                    <?= $isOff ? 'disabled' : '' ?>>
                </td>
                <td>
                  <input
                    type="text"
                    class="form-control form-control-sm pdam-note"
                    value="<?= esc($noteValue) ?>"
                    <?= $isOff ? 'disabled' : '' ?>>
                </td>
                <td class="text-center">
                  <?php if ($isOff): ?>
                    <span class="badge text-bg-danger">Libur</span>
                  <?php elseif ($row): ?>
                    <span class="badge text-bg-success">Terisi</span>
                  <?php else: ?>
                    <span class="badge text-bg-secondary">Belum</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>

      <div class="d-block d-md-none p-2" id="pdamMobileList">
        <?php for ($d = 1; $d <= $daysInMonth; $d++): ?>
          <?php
          $date = "$year-$month-" . sprintf('%02d', $d);
          $dayName = $dayMap[date('l', strtotime($date))] ?? date('l', strtotime($date));
          $isOff = is_date_offday($date, $holidayDates ?? []);
          $row = $logs[$date] ?? null;
          $timeValue = $row && !empty($row['log_time']) ? (string) $row['log_time'] : '';
          $meterValue = $row && $row['meter_reading'] !== null ? (string) $row['meter_reading'] : '';
          $noteValue = $row ? (string) ($row['note'] ?? '') : '';
          ?>
          <article class="card utility-mobile-card mb-2 <?= $isOff ? 'utility-mobile-offday' : '' ?>" data-date="<?= esc($date) ?>" data-offday="<?= $isOff ? '1' : '0' ?>">
            <div class="card-body p-3">
              <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <div>
                  <div class="fw-semibold"><?= $d ?> - <?= esc($dayName) ?></div>
                  <div class="text-muted small"><?= esc(date('d M Y', strtotime($date))) ?></div>
                </div>
                <?php if ($isOff): ?>
                  <span class="badge text-bg-danger">Libur</span>
                <?php elseif ($row): ?>
                  <span class="badge text-bg-success">Terisi</span>
                <?php else: ?>
                  <span class="badge text-bg-secondary">Belum</span>
                <?php endif; ?>
              </div>

              <div class="mb-2">
                <label class="form-label small mb-1">Jam</label>
                <select
                  class="form-select pdam-time"
                  <?= $isOff ? 'disabled' : '' ?>>
                  <option value="">Pilih Jam</option>
                  <?php foreach ($timeOptions as $option): ?>
                    <option value="<?= esc($option) ?>" <?= $timeValue === $option ? 'selected' : '' ?>>
                      <?= esc($option) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="mb-2">
                <label class="form-label small mb-1">Meteran Air</label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  class="form-control pdam-meter"
                  value="<?= esc($meterValue) ?>"
                  <?= $isOff ? 'disabled' : '' ?>>
              </div>

              <div class="mb-1">
                <label class="form-label small mb-1">Keterangan</label>
                <input
                  type="text"
                  class="form-control pdam-note"
                  value="<?= esc($noteValue) ?>"
                  <?= $isOff ? 'disabled' : '' ?>>
              </div>
            </div>
          </article>
        <?php endfor; ?>
      </div>
    </div>
  </section>

  <section class="card utility-stat-card no-lift mt-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>
        <div class="utility-stat-label">Status</div>
        <div id="saveState" class="text-muted small">Perubahan tersimpan otomatis</div>
      </div>
      <div class="d-flex flex-wrap gap-3">
        <div class="utility-inline-metric">
          <span>Hari Terisi</span>
          <strong id="filledDaysCount"><?= number_format($filledDays, 0, ',', '.') ?></strong>
        </div>
        <div class="utility-inline-metric">
          <span>Meter Terakhir</span>
          <strong id="latestMeterValue"><?= $latestMeter !== null ? number_format((float) $latestMeter, 2) : '0.00' ?></strong>
        </div>
      </div>
    </div>
  </section>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="/assets/css/utility-ops.css?v=<?= filemtime(FCPATH . 'assets/css/utility-ops.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  (() => {
    const saveUrl = "/pdam-water-boiler/save";
    const saveState = document.getElementById("saveState");
    let timers = {};

    const setSaveState = (text, tone = "muted") => {
      if (!saveState) return;
      saveState.className = "small";
      if (tone === "success") saveState.classList.add("text-success");
      else if (tone === "error") saveState.classList.add("text-danger");
      else saveState.classList.add("text-muted");
      saveState.textContent = text;
    };

    const normalizeTimeValue = (rawValue) => String(rawValue || "").trim();

    const mirrorRowState = (date, payload = {}) => {
      document.querySelectorAll(`[data-date="${date}"]`).forEach((row) => {
        const timeInput = row.querySelector(".pdam-time");
        const meterInput = row.querySelector(".pdam-meter");
        const noteInput = row.querySelector(".pdam-note");
        const badge = row.querySelector(".badge");

        if (typeof payload.time !== "undefined" && timeInput) timeInput.value = payload.time;
        if (typeof payload.meter !== "undefined" && meterInput) meterInput.value = payload.meter;
        if (typeof payload.note !== "undefined" && noteInput) noteInput.value = payload.note;

        if (badge && row.dataset.offday !== "1") {
          const hasValue = (payload.time || payload.meter || payload.note);
          badge.className = `badge ${hasValue ? "text-bg-success" : "text-bg-secondary"}`;
          badge.textContent = hasValue ? "Terisi" : "Belum";
        }
      });
    };

    const recalcSummary = () => {
      let filled = 0;
      let latestDate = "";
      let latestMeter = 0;

      document.querySelectorAll('#pdamTable tbody tr[data-date]').forEach((row) => {
        if (row.dataset.offday === "1") return;
        const time = row.querySelector(".pdam-time")?.value || "";
        const meter = row.querySelector(".pdam-meter")?.value || "";
        const note = row.querySelector(".pdam-note")?.value || "";
        const hasValue = time || meter || note;
        if (hasValue) {
          filled += 1;
          const date = row.dataset.date || "";
          const meterNumber = parseFloat(meter);
          if (date >= latestDate && Number.isFinite(meterNumber)) {
            latestDate = date;
            latestMeter = meterNumber;
          }
        }
      });

      document.getElementById("filledDaysCount").textContent = filled.toLocaleString("id-ID");
      document.getElementById("latestMeterValue").textContent = latestMeter ? latestMeter.toFixed(2) : "0.00";
    };

    const saveDay = async (date, source = null) => {
      source = source || document.querySelector(`#pdamTable tbody tr[data-date="${date}"]`) || document.querySelector(`#pdamMobileList [data-date="${date}"]`);
      if (!source) return;

      const timeInput = source.querySelector(".pdam-time");
      const meterInput = source.querySelector(".pdam-meter");
      const noteInput = source.querySelector(".pdam-note");

      const normalizedTime = normalizeTimeValue(timeInput?.value || "");
      if (timeInput) timeInput.value = normalizedTime;

      const meter = meterInput?.value || "";
      const note = noteInput?.value || "";

      if (!normalizedTime && !meter && !note) {
        mirrorRowState(date, { time: "", meter: "", note: "" });
        recalcSummary();
        setSaveState("Kosongkan semua field lalu simpan manual belum diperlukan.", "muted");
        return;
      }

      if (!normalizedTime) {
        setSaveState("Isi jam terlebih dahulu untuk menyimpan.", "error");
        return;
      }

      setSaveState("Menyimpan...", "muted");

      try {
        const response = await fetch(saveUrl, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            "X-Requested-With": "XMLHttpRequest"
          },
          body: new URLSearchParams({
            date,
            time: normalizedTime,
            meter_reading: meter,
            note
          })
        });

        if (!response.ok) {
          throw new Error("Gagal simpan");
        }

        await response.json();
        mirrorRowState(date, { time: normalizedTime, meter, note });
        recalcSummary();
        setSaveState("Perubahan tersimpan.", "success");
      } catch (error) {
        setSaveState("Gagal menyimpan data.", "error");
      }
    };

    const handleFieldChange = (event) => {
      const input = event.target;
      if (!(input instanceof HTMLElement)) return;
      if (!input.matches(".pdam-time, .pdam-meter, .pdam-note")) return;

      const row = input.closest("[data-date]");
      if (!row || row.dataset.offday === "1") return;

      const date = row.dataset.date;
      if (!date) return;

      setSaveState("Perubahan belum tersimpan", "muted");
      if (timers[date]) {
        clearTimeout(timers[date]);
      }
      const sourceRow = row;
      timers[date] = setTimeout(() => {
        saveDay(date, sourceRow);
      }, 700);
    };

    document.addEventListener("input", handleFieldChange);
    document.addEventListener("change", handleFieldChange);

    recalcSummary();
  })();
</script>
<?= $this->endSection() ?>
