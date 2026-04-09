<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid progress-modern-page">

  <div class="card border-0 shadow-sm progress-hero mb-3">
    <div class="card-body p-3 p-md-4 d-flex flex-column gap-3">
      <div>
        <h5 class="mb-1 fw-semibold">Monitoring Progress User</h5>
        <small class="text-muted">
          Pantau progres checklist semua user, temukan pending lebih cepat, dan buka detail per user.
        </small>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center progress-toolbar">
        <select id="monthFilter" class="form-select form-select-sm progress-month-filter">
          <?php
          $start = new DateTime('2026-01-01');
          $end = new DateTime(date('Y-m-01'));
          $selectedMonth = $selectedMonth ?? date('Y-m');

          while ($start <= $end):
            $value = $start->format('Y-m');
          ?>
            <option value="<?= esc($value) ?>" <?= $value === $selectedMonth ? 'selected' : '' ?>>
              <?= esc($start->format('F Y')) ?>
            </option>
          <?php
            $start->modify('+1 month');
          endwhile;
          ?>
        </select>

        <div class="input-group input-group-sm progress-search-wrap">
          <span class="input-group-text bg-white">
            <i class="bi bi-search"></i>
          </span>
          <input type="text"
            id="searchUser"
            class="form-control"
            placeholder="Cari nama user...">
        </div>

        <button id="refreshBtn" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>

        <a id="exportBtn" class="btn btn-sm btn-outline-primary ms-md-auto">
          <i class="bi bi-download me-1"></i> Export CSV
        </a>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3" id="summaryCards"></div>

  <div class="card border-0 shadow-sm progress-table-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
      <div>
        <h6 class="mb-0 fw-semibold">Daftar Progress User</h6>
        <small class="text-muted" id="resultMeta">Memuat data...</small>
      </div>
    </div>

    <div class="card-body p-0">
      <div id="progressTableContainer"></div>
    </div>
  </div>

</div>

<div class="modal fade" id="userDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title">Detail Missing Checklist</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalContent">
        <div class="text-center py-4">
          <div class="spinner-border spinner-border-sm text-primary"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .progress-modern-page .progress-hero {
    background:
      radial-gradient(circle at 10% 10%, rgba(70, 128, 255, 0.16), transparent 45%),
      radial-gradient(circle at 95% 10%, rgba(4, 169, 245, 0.12), transparent 42%),
      #fff;
  }

  .progress-modern-page .progress-month-filter {
    max-width: 180px;
  }

  .progress-modern-page .progress-search-wrap {
    min-width: 240px;
    max-width: 340px;
  }

  .summary-card {
    border: 0;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(18, 38, 63, 0.07);
  }

  .summary-card .summary-label {
    font-size: .78rem;
    color: #64748b;
  }

  .summary-card .summary-value {
    font-size: 1.4rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.1;
  }

  .summary-accent {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
  }

  .summary-accent.primary {
    background: rgba(70, 128, 255, .14);
    color: #2f63d8;
  }

  .summary-accent.success {
    background: rgba(34, 197, 94, .14);
    color: #0f9f46;
  }

  .summary-accent.warning {
    background: rgba(245, 158, 11, .18);
    color: #d97706;
  }

  .summary-accent.danger {
    background: rgba(239, 68, 68, .16);
    color: #dc2626;
  }

  .progress-modern-page .progress-table-card .table tbody tr {
    transition: background-color .2s ease;
  }

  .progress-modern-page .progress-table-card .table tbody tr:hover {
    background-color: rgba(70, 128, 255, .03);
  }

  .progress-user-link {
    color: #1d4ed8;
    text-decoration: none;
    font-weight: 600;
  }

  .progress-user-link:hover {
    text-decoration: underline;
  }

  .loading-skeleton {
    position: relative;
    overflow: hidden;
    background: #edf2f7;
    border-radius: 8px;
  }

  .loading-skeleton::after {
    content: "";
    position: absolute;
    inset: 0;
    transform: translateX(-100%);
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .7), transparent);
    animation: shimmer 1.2s infinite;
  }

  @keyframes shimmer {
    100% {
      transform: translateX(100%);
    }
  }

  .detail-badge {
    font-size: .7rem;
    padding: .22rem .45rem;
    border-radius: 999px;
  }

  @media (max-width: 768px) {
    .progress-modern-page .progress-month-filter {
      max-width: 100%;
      width: 100%;
    }

    .progress-modern-page .progress-search-wrap {
      min-width: 100%;
      max-width: 100%;
      width: 100%;
    }
  }
</style>

<script>
  document.addEventListener("DOMContentLoaded", function() {
    const summaryDiv = document.getElementById("summaryCards");
    const container = document.getElementById("progressTableContainer");
    const resultMeta = document.getElementById("resultMeta");
    const monthSelect = document.getElementById("monthFilter");
    const searchInput = document.getElementById("searchUser");
    const refreshBtn = document.getElementById("refreshBtn");
    const exportBtn = document.getElementById("exportBtn");

    let currentController = null;
    let rawUsers = [];
    let searchTimer = null;

    function escapeHtml(str) {
      if (str === null || str === undefined) return "";
      return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    }

    function renderSummarySkeleton() {
      summaryDiv.innerHTML = Array.from({
          length: 4
        })
        .map(() => `
          <div class="col-6 col-lg-3">
            <div class="summary-card p-3">
              <div class="loading-skeleton" style="height:12px; width:60%; margin-bottom:12px;"></div>
              <div class="loading-skeleton" style="height:26px; width:45%;"></div>
            </div>
          </div>
        `)
        .join("");
    }

    function renderTableSkeleton() {
      const skeletonRows = Array.from({
          length: 7
        })
        .map(() => `
          <tr>
            <td class="px-3 py-3"><div class="loading-skeleton" style="height:12px; width:120px;"></div></td>
            <td class="px-3 py-3"><div class="loading-skeleton" style="height:12px; width:40px;"></div></td>
            <td class="px-3 py-3"><div class="loading-skeleton" style="height:12px; width:42px;"></div></td>
            <td class="px-3 py-3"><div class="loading-skeleton" style="height:12px; width:42px;"></div></td>
            <td class="px-3 py-3"><div class="loading-skeleton" style="height:12px; width:42px;"></div></td>
            <td class="px-3 py-3"><div class="loading-skeleton" style="height:10px; width:95%;"></div></td>
          </tr>
        `)
        .join("");

      container.innerHTML = `
        <div class="table-responsive">
          <table class="table mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th>User</th>
                <th>Inventory</th>
                <th>Done</th>
                <th>Pending</th>
                <th>Late</th>
                <th width="24%">Progress</th>
              </tr>
            </thead>
            <tbody>${skeletonRows}</tbody>
          </table>
        </div>
      `;
    }

    function renderSummary(users) {
      const totalUser = users.length;
      const avgProgress = totalUser > 0 ?
        Math.round(users.reduce((s, u) => s + (u.progress || 0), 0) / totalUser) :
        0;
      const totalPending = users.reduce((s, u) => s + (u.pending || 0), 0);
      const totalLate = users.reduce((s, u) => s + (u.late || 0), 0);

      const cards = [{
          label: "Total User",
          value: totalUser,
          icon: "bi-people",
          accent: "primary"
        },
        {
          label: "Avg Progress",
          value: `${avgProgress}%`,
          icon: "bi-graph-up-arrow",
          accent: "success"
        },
        {
          label: "Total Pending",
          value: totalPending,
          icon: "bi-hourglass-split",
          accent: "warning"
        },
        {
          label: "Total Late",
          value: totalLate,
          icon: "bi-exclamation-triangle",
          accent: "danger"
        }
      ];

      summaryDiv.innerHTML = cards.map(card => `
        <div class="col-6 col-lg-3">
          <div class="summary-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <div>
                <div class="summary-label">${card.label}</div>
                <div class="summary-value mt-1">${card.value}</div>
              </div>
              <span class="summary-accent ${card.accent}">
                <i class="bi ${card.icon}"></i>
              </span>
            </div>
          </div>
        </div>
      `).join("");
    }

    function renderTable(users) {
      resultMeta.textContent = `${users.length} user ditampilkan`;

      if (!users.length) {
        container.innerHTML = `
          <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
            Tidak ada data user untuk filter saat ini.
          </div>
        `;
        return;
      }

      const rows = users.map(u => {
        let color = "bg-success";
        if ((u.progress || 0) < 50) color = "bg-danger";
        else if ((u.progress || 0) < 80) color = "bg-warning";

        return `
          <tr>
            <td>
              <a href="#" class="progress-user-link user-detail" data-id="${u.id}">
                ${escapeHtml(u.name)}
              </a>
            </td>
            <td>${u.totalInventory ?? 0}</td>
            <td class="text-success fw-semibold">${u.done ?? 0}</td>
            <td class="text-warning fw-semibold">${u.pending ?? 0}</td>
            <td class="text-danger fw-semibold">${u.late ?? 0}</td>
            <td>
              <div class="progress progress-mini">
                <div class="progress-bar ${color}" style="width:${u.progress ?? 0}%"></div>
              </div>
              <small class="text-muted">${u.progress ?? 0}%</small>
            </td>
            <td class="text-end">
              <button
                type="button"
                class="btn btn-sm btn-outline-success remind-user-btn"
                data-id="${u.id}"
                data-name="${escapeHtml(u.name)}"
                ${!u.pending ? "disabled" : ""}
              >
                <i class="bi bi-send me-1"></i> Reminder
              </button>
            </td>
          </tr>
        `;
      }).join("");

      container.innerHTML = `
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>User</th>
                <th>Inventory</th>
                <th>Done</th>
                <th>Pending</th>
                <th>Late</th>
                <th width="24%">Progress</th>
                <th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>${rows}</tbody>
          </table>
        </div>
      `;
    }

    function applyFilterAndRender() {
      const keyword = (searchInput.value || "").toLowerCase().trim();
      const filtered = !keyword ?
        rawUsers :
        rawUsers.filter(u => (u.name || "").toLowerCase().includes(keyword));

      renderSummary(filtered);
      renderTable(filtered);
    }

    function loadData() {
      const month = monthSelect.value;

      if (currentController) currentController.abort();
      currentController = new AbortController();

      renderSummarySkeleton();
      renderTableSkeleton();
      resultMeta.textContent = "Memuat data...";

      fetch(`/compliance/progress/ajax?month=${encodeURIComponent(month)}`, {
          signal: currentController.signal
        })
        .then(res => {
          if (!res.ok) throw new Error("bad_response");
          return res.json();
        })
        .then(data => {
          rawUsers = Array.isArray(data) ? data : [];
          applyFilterAndRender();
        })
        .catch(err => {
          if (err.name === "AbortError") return;

          summaryDiv.innerHTML = `
            <div class="col-12">
              <div class="alert alert-danger mb-0">
                Gagal memuat ringkasan progress.
              </div>
            </div>
          `;

          container.innerHTML = `
            <div class="text-center py-5 text-danger">
              <i class="bi bi-wifi-off fs-4 d-block mb-2"></i>
              Gagal memuat data table progress.
            </div>
          `;

          resultMeta.textContent = "Terjadi error saat memuat data";
        });
    }

    document.addEventListener("click", function(e) {
      const trigger = e.target.closest(".user-detail");
      if (!trigger) return;

      e.preventDefault();

      const userId = trigger.dataset.id;
      const user = rawUsers.find(u => String(u.id) === String(userId));
      if (!user) return;

      const modalBody = document.getElementById("modalContent");
      const modal = new bootstrap.Modal(document.getElementById("userDetailModal"));

      if (!user.detailMissing || user.detailMissing.length === 0) {
        modalBody.innerHTML = `
          <div class="alert alert-success mb-0">
            <strong>${escapeHtml(user.name)}</strong><br>
            Semua checklist untuk periode ini sudah lengkap.
          </div>
        `;
        modal.show();
        return;
      }

      const detailRows = user.detailMissing.map(row => {
        const badges = (row.missing || []).map(m =>
          `<span class="badge bg-warning text-dark detail-badge me-1 mb-1">${escapeHtml(m)}</span>`
        ).join("");

        return `
          <tr>
            <td>${escapeHtml(row.inventory)}</td>
            <td>${escapeHtml(row.frequency)}</td>
            <td>${badges}</td>
          </tr>
        `;
      }).join("");

      modalBody.innerHTML = `
        <h6 class="mb-3">${escapeHtml(user.name)}</h6>
        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Inventory</th>
                <th>Frekuensi</th>
                <th>Missing</th>
              </tr>
            </thead>
            <tbody>${detailRows}</tbody>
          </table>
        </div>
      `;

      modal.show();
    });

    document.addEventListener("click", function(e) {
      const trigger = e.target.closest(".remind-user-btn");
      if (!trigger) return;

      const userId = trigger.dataset.id;
      const userName = trigger.dataset.name || "user";
      const month = monthSelect.value;

      Swal.fire({
        icon: "question",
        title: "Kirim reminder?",
        text: `Reminder untuk ${userName} akan memakai periode ${month}.`,
        showCancelButton: true,
        confirmButtonText: "Kirim",
        cancelButtonText: "Batal"
      }).then(result => {
        if (!result.isConfirmed) return;

        trigger.disabled = true;

        fetch("/compliance/progress/remind", {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
            },
            body: new URLSearchParams({
              user_id: userId,
              month: month
            })
          })
          .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.ok) {
              throw new Error(data.message || "Reminder gagal dikirim.");
            }
            return data;
          })
          .then(data => {
            Swal.fire({
              icon: "success",
              title: "Reminder terkirim",
              text: data.message || "Reminder berhasil dikirim."
            });
          })
          .catch(err => {
            Swal.fire({
              icon: "error",
              title: "Gagal",
              text: err.message || "Reminder gagal dikirim."
            });
          })
          .finally(() => {
            trigger.disabled = false;
          });
      });
    });

    searchInput.addEventListener("input", function() {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(applyFilterAndRender, 180);
    });

    monthSelect.addEventListener("change", loadData);
    refreshBtn.addEventListener("click", loadData);

    exportBtn.addEventListener("click", function() {
      window.location.href = "<?= base_url('compliance/progress/export') ?>?month=" + monthSelect.value;
    });

    loadData();
  });
</script>

<?= $this->endSection() ?>
