<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$monthRef = date('Y-m', strtotime($date));
$backUrl = '/pdam-water-boiler?monthpicker=' . rawurlencode($monthRef);
$timeValue = !empty($log['log_time']) ? substr((string) $log['log_time'], 0, 5) : '';
$meterValue = isset($log['meter_reading']) ? (string) $log['meter_reading'] : '';
$noteValue = isset($log['note']) ? (string) $log['note'] : '';
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
        <h5 class="mb-1 fw-bold">Detail Pengecekan Air PDAM Boiler</h5>
        <p class="text-muted mb-0">Tanggal <?= esc(date('d F Y', strtotime($date))) ?> - 1 data per hari</p>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= esc($backUrl) ?>" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
          <i class="bi bi-arrow-left"></i>
          Kembali
        </a>
      </div>
    </div>
  </section>

  <?php if ($isSunday || $isHoliday): ?>
    <div class="alert alert-danger utility-alert mb-3">
      Hari ini termasuk hari libur. Pengisian tetap tersedia jika diperlukan.
    </div>
  <?php endif; ?>

  <section class="card utility-table-card no-lift">
    <div class="card-body p-3 p-md-4">
      <div class="row g-3" id="pdamForm">
        <div class="col-md-4">
          <label class="form-label small mb-1">Jam</label>
          <select class="form-select time">
            <option value="">Pilih Jam</option>
            <?php foreach ($timeOptions as $option): ?>
              <option value="<?= esc($option) ?>" <?= $timeValue === $option ? 'selected' : '' ?>>
                <?= esc($option) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label small mb-1">Meteran Air</label>
          <input type="number" step="0.01" class="form-control meter_reading" value="<?= esc($meterValue) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small mb-1">Keterangan</label>
          <input type="text" class="form-control note" value="<?= esc($noteValue) ?>">
        </div>
      </div>

      <div class="d-flex justify-content-end mt-3">
        <button class="btn btn-outline-danger btn-sm deleteRow" type="button">
          Hapus Data Hari Ini
        </button>
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
          <span>Status</span>
          <strong id="totalLogs"><?= $log ? 'Terisi' : 'Kosong' ?></strong>
        </div>
        <div class="utility-inline-metric">
          <span>Meter Hari Ini</span>
          <strong id="latestMeter"><?= $meterValue !== '' ? number_format((float) $meterValue, 2) : '0.00' ?></strong>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
  (() => {
    const date = "<?= esc($date) ?>";
    const saveUrl = "/pdam-water-boiler/save";
    const deleteUrl = "/pdam-water-boiler/delete";
    const saveState = document.getElementById("saveState");
    const form = document.getElementById("pdamForm");

    const setSaveState = (text, tone = "muted") => {
      if (!saveState) return;
      saveState.className = "small";
      if (tone === "success") saveState.classList.add("text-success");
      else if (tone === "error") saveState.classList.add("text-danger");
      else saveState.classList.add("text-muted");
      saveState.textContent = text;
    };

    const parseNum = (value) => {
      const n = parseFloat(value);
      return Number.isFinite(n) ? n : 0;
    };

    const normalizeTimeValue = (rawValue) => String(rawValue || "").trim();

    const calculateSummary = () => {
      const meter = parseNum(form?.querySelector(".meter_reading")?.value || 0);
      const time = normalizeTimeValue(form?.querySelector(".time")?.value || "");
      document.getElementById("totalLogs").textContent = time || meter ? "Terisi" : "Kosong";
      document.getElementById("latestMeter").textContent = meter ? meter.toFixed(2) : "0.00";
    };

    const saveRow = () => {
      const timeInput = form?.querySelector(".time");
      const normalizedTime = normalizeTimeValue(timeInput?.value || "");
      if (timeInput) {
        timeInput.value = normalizedTime;
      }

      const meterReading = form?.querySelector(".meter_reading")?.value || "";
      const note = form?.querySelector(".note")?.value || "";

      if (!normalizedTime) {
        setSaveState("Isi jam terlebih dahulu untuk menyimpan.", "error");
        return;
      }

      setSaveState("Menyimpan...", "muted");

      fetch(saveUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          "X-Requested-With": "XMLHttpRequest"
        },
        body: new URLSearchParams({
          date,
          time: normalizedTime,
          meter_reading: meterReading,
          note
        })
      })
        .then((res) => {
          if (!res.ok) throw new Error("Gagal simpan");
          return res.json();
        })
        .then(() => {
          calculateSummary();
          setSaveState("Perubahan tersimpan.", "success");
        })
        .catch(() => {
          setSaveState("Gagal menyimpan data.", "error");
        });
    };

    document.addEventListener("change", (event) => {
      const input = event.target;
      if (!(input instanceof HTMLElement)) return;
      if (!input.matches(".time, .meter_reading, .note")) return;
      if (!form?.contains(input)) return;
      saveRow();
    });

    document.addEventListener("click", (event) => {
      const deleteBtn = event.target.closest(".deleteRow");
      if (!deleteBtn) return;

      fetch(deleteUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          "X-Requested-With": "XMLHttpRequest"
        },
        body: new URLSearchParams({ date })
      })
        .then((res) => {
          if (!res.ok) throw new Error("Gagal hapus");
          return res.json();
        })
        .then(() => {
          form.querySelector(".time").value = "";
          form.querySelector(".meter_reading").value = "";
          form.querySelector(".note").value = "";
          calculateSummary();
          setSaveState("Data hari ini berhasil dihapus.", "success");
        })
        .catch(() => {
          setSaveState("Gagal menghapus data.", "error");
        });
    });

    calculateSummary();
  })();
</script>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="/assets/css/utility-ops.css?v=<?= filemtime(FCPATH . 'assets/css/utility-ops.css') ?>">
<?= $this->endSection() ?>
