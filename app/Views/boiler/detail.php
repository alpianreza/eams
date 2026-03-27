<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$monthRef = date('Y-m', strtotime($date));
$backUrl = base_url('boiler?monthpicker=' . $monthRef);
?>

<div class="utility-shell utility-boiler-shell">
  <section class="card utility-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="utility-kicker mb-1">Boiler Monitoring</p>
        <h5 class="mb-1 fw-bold">Detail Log Bahan Bakar</h5>
        <p class="text-muted mb-0">Tanggal <?= esc(date('d F Y', strtotime($date))) ?></p>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= esc($backUrl) ?>" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
          <i class="bi bi-arrow-left"></i>
          Kembali
        </a>
        <button class="btn btn-primary btn-sm d-none d-md-inline-flex align-items-center gap-1" id="addRow" type="button">
          <i class="bi bi-plus-circle"></i>
          Tambah Baris
        </button>
      </div>
    </div>
  </section>

  <?php if ($isSunday || $isHoliday): ?>
    <div class="alert alert-danger utility-alert mb-3">
      Hari ini termasuk hari libur. Pengisian tetap tersedia jika diperlukan.
    </div>
  <?php endif; ?>

  <section class="card utility-table-card no-lift">
    <div class="card-body p-0">
      <div class="table-responsive d-none d-md-block utility-table-wrap">
        <table class="table table-bordered align-middle mb-0 utility-table" id="logTable">
          <thead>
            <tr>
              <th width="120">Jam</th>
              <th width="140">Polybag</th>
              <th width="140">KG</th>
              <th>Keterangan</th>
              <th width="90" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($logs as $row): ?>
              <tr data-id="<?= (int) $row['id'] ?>">
                <td>
                  <input
                    type="text"
                    inputmode="numeric"
                    autocomplete="off"
                    placeholder="07:30"
                    class="form-control form-control-sm time"
                    value="<?= esc(substr((string) $row['log_time'], 0, 5)) ?>">
                </td>
                <td>
                  <input type="number" class="form-control form-control-sm polybag" value="<?= esc((string) $row['polybag']) ?>">
                </td>
                <td>
                  <input type="number" step="0.01" class="form-control form-control-sm kg" value="<?= esc((string) $row['kg']) ?>">
                </td>
                <td>
                  <input type="text" class="form-control form-control-sm note" value="<?= esc($row['note']) ?>">
                </td>
                <td class="text-center">
                  <button class="btn btn-outline-danger btn-sm deleteRow" type="button" title="Hapus baris">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="d-block d-md-none p-2" id="mobileLog">
        <?php foreach ($logs as $row): ?>
          <article class="card utility-mobile-card mb-2 log-card" data-id="<?= (int) $row['id'] ?>">
            <div class="card-body p-3">
              <div class="mb-2">
                <label class="form-label small mb-1">Jam</label>
                <input
                  type="text"
                  inputmode="numeric"
                  autocomplete="off"
                  placeholder="07:30"
                  class="form-control time"
                  value="<?= esc(substr((string) $row['log_time'], 0, 5)) ?>">
              </div>

              <div class="row g-2 mb-2">
                <div class="col-6">
                  <label class="form-label small mb-1">Polybag</label>
                  <input type="number" class="form-control polybag" value="<?= esc((string) $row['polybag']) ?>">
                </div>
                <div class="col-6">
                  <label class="form-label small mb-1">KG</label>
                  <input type="number" step="0.01" class="form-control kg" value="<?= esc((string) $row['kg']) ?>">
                </div>
              </div>

              <div class="mb-2">
                <label class="form-label small mb-1">Keterangan</label>
                <input type="text" class="form-control note" value="<?= esc($row['note']) ?>">
              </div>

              <button class="btn btn-outline-danger btn-sm w-100 deleteRow" type="button">
                Hapus
              </button>
            </div>
          </article>
        <?php endforeach; ?>

        <button class="btn btn-primary btn-sm w-100 mt-1 mb-2 d-inline-flex align-items-center justify-content-center gap-1" id="addMobileRow" type="button">
          <i class="bi bi-plus-circle"></i>
          Tambah Baris
        </button>
      </div>
    </div>
  </section>

  <section class="card utility-stat-card no-lift mt-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>
        <div class="utility-stat-label">Total Harian</div>
        <div id="saveState" class="text-muted small">Perubahan tersimpan otomatis</div>
      </div>
      <div class="d-flex flex-wrap gap-3">
        <div class="utility-inline-metric">
          <span>Polybag</span>
          <strong id="totalPoly">0</strong>
        </div>
        <div class="utility-inline-metric">
          <span>KG</span>
          <strong id="totalKg">0.00</strong>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
  (() => {
    const date = "<?= esc($date) ?>";
    const saveUrl = "<?= base_url('boiler/save') ?>";
    const deleteUrl = "<?= base_url('boiler/delete') ?>";
    const isMobile = () => window.matchMedia("(max-width: 767.98px)").matches;
    const saveState = document.getElementById("saveState");

    const desktopTbody = document.querySelector("#logTable tbody");
    const mobileWrap = document.getElementById("mobileLog");

    const getActiveScope = () => (isMobile() ? mobileWrap : desktopTbody);

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

    const normalizeTimeValue = (rawValue) => {
      const raw = String(rawValue || "").trim().replace(/\./g, ":").replace(/\s+/g, "");
      if (!raw) return "";

      const build = (h, m) => {
        const hh = parseInt(h, 10);
        const mm = parseInt(m, 10);
        if (!Number.isFinite(hh) || !Number.isFinite(mm)) return "";
        if (hh < 0 || hh > 23 || mm < 0 || mm > 59) return "";
        return `${String(hh).padStart(2, "0")}:${String(mm).padStart(2, "0")}`;
      };

      if (/^\d{1,2}$/.test(raw)) {
        return build(raw, "0");
      }

      if (/^\d{3,4}$/.test(raw)) {
        const hhPart = raw.slice(0, raw.length - 2);
        const mmPart = raw.slice(-2);
        return build(hhPart, mmPart);
      }

      if (/^\d{1,2}:\d{1,2}(:\d{1,2})?$/.test(raw)) {
        const parts = raw.split(":");
        return build(parts[0], parts[1]);
      }

      return "";
    };

    const calculateTotal = () => {
      const scope = getActiveScope();
      if (!scope) return;

      let totalPoly = 0;
      let totalKg = 0;

      scope.querySelectorAll(".polybag").forEach((el) => {
        totalPoly += parseNum(el.value);
      });

      scope.querySelectorAll(".kg").forEach((el) => {
        totalKg += parseNum(el.value);
      });

      document.getElementById("totalPoly").textContent = totalPoly.toLocaleString("id-ID");
      document.getElementById("totalKg").textContent = totalKg.toFixed(2);
    };

    const syncIdAcrossViews = (oldId, newId) => {
      if (!oldId || oldId === newId) return;
      document.querySelectorAll(`[data-id="${oldId}"]`).forEach((node) => {
        node.dataset.id = newId;
      });
    };

    const saveRow = (container) => {
      const id = container.dataset.id || "";
      const timeInput = container.querySelector(".time");
      const normalizedTime = normalizeTimeValue(timeInput?.value || "");
      if (timeInput) {
        timeInput.value = normalizedTime;
      }

      const time = normalizedTime;
      const polybag = container.querySelector(".polybag")?.value || "";
      const kg = container.querySelector(".kg")?.value || "";
      const note = container.querySelector(".note")?.value || "";

      if (!time) {
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
          id,
          date,
          time,
          polybag,
          kg,
          note
        })
      })
        .then((res) => {
          if (!res.ok) throw new Error("Gagal simpan");
          return res.json();
        })
        .then((data) => {
          if (!id && data?.id) {
            container.dataset.id = data.id;
            syncIdAcrossViews(id, String(data.id));
          }
          calculateTotal();
          setSaveState("Perubahan tersimpan.", "success");
        })
        .catch(() => {
          setSaveState("Gagal menyimpan data.", "error");
        });
    };

    const createDesktopRow = () => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td><input type="text" inputmode="numeric" autocomplete="off" placeholder="07:30" class="form-control form-control-sm time"></td>
        <td><input type="number" class="form-control form-control-sm polybag"></td>
        <td><input type="number" step="0.01" class="form-control form-control-sm kg"></td>
        <td><input type="text" class="form-control form-control-sm note"></td>
        <td class="text-center">
          <button class="btn btn-outline-danger btn-sm deleteRow" type="button"><i class="bi bi-trash"></i></button>
        </td>
      `;
      desktopTbody?.appendChild(tr);
      calculateTotal();
      tr.querySelector(".time")?.focus();
    };

    const createMobileCard = () => {
      const card = document.createElement("article");
      card.className = "card utility-mobile-card mb-2 log-card";
      card.innerHTML = `
        <div class="card-body p-3">
          <div class="mb-2">
            <label class="form-label small mb-1">Jam</label>
            <input type="text" inputmode="numeric" autocomplete="off" placeholder="07:30" class="form-control time">
          </div>
          <div class="row g-2 mb-2">
            <div class="col-6">
              <label class="form-label small mb-1">Polybag</label>
              <input type="number" class="form-control polybag">
            </div>
            <div class="col-6">
              <label class="form-label small mb-1">KG</label>
              <input type="number" step="0.01" class="form-control kg">
            </div>
          </div>
          <div class="mb-2">
            <label class="form-label small mb-1">Keterangan</label>
            <input type="text" class="form-control note">
          </div>
          <button class="btn btn-outline-danger btn-sm w-100 deleteRow" type="button">Hapus</button>
        </div>
      `;
      mobileWrap?.insertBefore(card, document.getElementById("addMobileRow"));
      calculateTotal();
      card.querySelector(".time")?.focus();
    };

    document.getElementById("addRow")?.addEventListener("click", createDesktopRow);
    document.getElementById("addMobileRow")?.addEventListener("click", createMobileCard);

    document.addEventListener("change", (event) => {
      const input = event.target;
      if (!(input instanceof HTMLElement)) return;
      if (!input.matches(".time, .polybag, .kg, .note")) return;

      const container = input.closest("tr") || input.closest(".log-card");
      if (!container) return;
      saveRow(container);
    });

    document.addEventListener("click", (event) => {
      const deleteBtn = event.target.closest(".deleteRow");
      if (!deleteBtn) return;

      const container = deleteBtn.closest("tr") || deleteBtn.closest(".log-card");
      if (!container) return;

      const id = container.dataset.id || "";
      if (!id) {
        container.remove();
        calculateTotal();
        return;
      }

      fetch(deleteUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          "X-Requested-With": "XMLHttpRequest"
        },
        body: new URLSearchParams({ id })
      })
        .then((res) => {
          if (!res.ok) throw new Error("Gagal hapus");
          return res.json();
        })
        .then(() => {
          document.querySelectorAll(`[data-id="${id}"]`).forEach((node) => node.remove());
          calculateTotal();
          setSaveState("Baris berhasil dihapus.", "success");
        })
        .catch(() => {
          setSaveState("Gagal menghapus data.", "error");
        });
    });

    window.addEventListener("resize", calculateTotal);
    calculateTotal();
  })();
</script>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/utility-ops.css?v=' . filemtime(FCPATH . 'assets/css/utility-ops.css')) ?>">
<?= $this->endSection() ?>
