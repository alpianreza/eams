<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">

  <!-- HEADER FILTER -->
  <div class="d-flex justify-content-between align-items-center mb-3">

    <h5 class="mb-0 fw-semibold">
      Monitoring Progress User
    </h5>

    <div class="d-flex gap-2">

      <select id="monthFilter"
        class="form-select form-select-sm"
        style="width:170px;">

        <?php
        $start = new DateTime('2026-01-01');
        $end   = new DateTime(date('Y-m-01'));
        $currentMonth = date('Y-m');

        while ($start <= $end):
          $value = $start->format('Y-m');
        ?>
          <option value="<?= $value ?>"
            <?= $value == $currentMonth ? 'selected' : '' ?>>
            <?= $start->format('F Y') ?>
          </option>
        <?php
          $start->modify('+1 month');
        endwhile;
        ?>
      </select>

      <a id="exportBtn"
        class="btn btn-sm btn-outline-primary">
        <i class="bi bi-download me-1"></i> Export
      </a>

    </div>

  </div>

  <!-- SUMMARY CARDS -->
  <div class="row mb-3" id="summaryCards"></div>

  <!-- TABLE CARD -->
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div id="progressTableContainer"></div>
    </div>
  </div>

</div>

<!-- DETAIL MODAL -->
<div class="modal fade" id="userDetailModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail Progress</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalContent">
        <div class="text-center py-4">
          <div class="spinner-border spinner-border-sm"></div>
        </div>
      </div>
    </div>
  </div>
</div>



<script>
  function showLoading(container) {
    container.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border spinner-border-sm text-primary"
                 role="status">
            </div>
        </div>`;
  }
</script>


<script>
  document.addEventListener("DOMContentLoaded", function() {

    const container = document.getElementById("progressTableContainer");
    const summaryDiv = document.getElementById("summaryCards");
    const monthSelect = document.getElementById("monthFilter");
    const exportBtn = document.getElementById("exportBtn");

    let currentController = null;
    let progressUsers = [];

    function showLoading(el) {
      el.innerHTML = `
      <div class="text-center py-4">
        <div class="spinner-border spinner-border-sm text-primary"></div>
      </div>`;
    }

    function loadData() {

      const month = monthSelect.value;

      if (currentController) currentController.abort();
      currentController = new AbortController();

      showLoading(container);
      showLoading(summaryDiv);

      fetch(`<?= base_url('compliance/progress/ajax') ?>?month=${month}`, {
          signal: currentController.signal
        })
        .then(res => res.json())
        .then(data => {

          progressUsers = data;

          // ===== SUMMARY =====
          const totalUser = data.length;

          const avgProgress = totalUser > 0 ?
            Math.round(data.reduce((s, u) => s + u.progress, 0) / totalUser) :
            0;

          const totalPending = data.reduce((s, u) => s + u.pending, 0);
          const totalLate = data.reduce((s, u) => s + u.late, 0);

          summaryDiv.innerHTML = `
        <div class="col-md-3">
          <div class="card shadow-sm text-center p-2">
            <small>Total User</small>
            <h5>${totalUser}</h5>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card shadow-sm text-center p-2">
            <small>Avg Progress</small>
            <h5>${avgProgress}%</h5>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card shadow-sm text-center p-2 text-warning">
            <small>Total Pending</small>
            <h5>${totalPending}</h5>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card shadow-sm text-center p-2 text-danger">
            <small>Total Late</small>
            <h5>${totalLate}</h5>
          </div>
        </div>
      `;

          // ===== TABLE =====
          let html = `
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>User</th>
              <th>Total Periode</th>
              <th>Done</th>
              <th>Pending</th>
              <th>Late</th>
              <th width="20%">Progress</th>
            </tr>
          </thead>
          <tbody>`;

          if (data.length === 0) {
            html += `
          <tr>
            <td colspan="6" class="text-center py-4 text-muted">
              Tidak ada data
            </td>
          </tr>`;
          }

          data.forEach(u => {

            let color = 'bg-success';
            if (u.progress < 50) color = 'bg-danger';
            else if (u.progress < 80) color = 'bg-warning';

            html += `
          <tr>
            <td>
              <a href="#" class="user-detail fw-semibold" data-id="${u.id}">
                ${u.name}
              </a>
            </td>
            <td>${u.required}</td>
            <td class="text-success">${u.done}</td>
            <td class="text-warning">${u.pending}</td>
            <td class="text-danger">${u.late}</td>
            <td>
              <div class="progress" style="height:14px;">
                <div class="progress-bar ${color}" style="width:${u.progress}%"></div>
              </div>
              <small>${u.progress}%</small>
            </td>
          </tr>`;
          });

          html += "</tbody></table>";
          container.innerHTML = html;

        })
        .catch(err => {
          if (err.name === "AbortError") return;

          container.innerHTML = `
        <div class="text-center py-4 text-danger">
          Gagal memuat data
        </div>`;
        });
    }

    // ===== CLICK USER DETAIL =====
    document.addEventListener("click", function(e) {

      if (!e.target.classList.contains("user-detail")) return;

      e.preventDefault();

      const userId = e.target.dataset.id;
      const user = progressUsers.find(u => u.id == userId);
      if (!user) return;

      const modal = new bootstrap.Modal(document.getElementById('userDetailModal'));
      const modalBody = document.getElementById("modalContent");

      let html = `
      <h6 class="mb-3">${user.name}</h6>
      <table class="table table-sm table-bordered">
        <thead>
          <tr>
            <th>Inventory</th>
            <th>Frekuensi</th>
            <th>Missing</th>
          </tr>
        </thead>
        <tbody>`;

      user.detailMissing.forEach(row => {

        let badges = '';

        if (row.missing.length === 0) {
          badges = `<span class="badge bg-success">Complete</span>`;
        } else {
          badges = row.missing.map(m => `<span class="badge bg-warning me-1">${m}</span>`).join('');
        }

        html += `
        <tr>
          <td>${row.inventory}</td>
          <td>${row.frequency}</td>
          <td>${badges}</td>
        </tr>`;
      });

      html += "</tbody></table>";

      modalBody.innerHTML = html;
      modal.show();
    });

    // ===== EXPORT =====
    exportBtn.addEventListener("click", () => {
      window.location.href = "<?= base_url('compliance/progress/export') ?>?month=" + monthSelect.value;
    });

    // INIT
    loadData();
    monthSelect.addEventListener("change", loadData);

  });
</script>


<?= $this->endSection() ?>