<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid compliance-dashboard tw-p-4">
<div id="compliance-dashboard-meta" class="d-none"
     data-year="<?= esc((string)$selectedYear) ?>"
     data-base-url="<?= rtrim(base_url(), '/') ?>"></div>

  <section class="card shadow-sm border-0 mb-4 compliance-hero-card no-lift">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="compliance-kicker mb-1">Dashboard Compliance</p>
        <h4 class="mb-1 fw-bold">Control Center Compliance</h4>
        <p class="text-muted mb-0">Pantau performa ceklis, risiko <strong>tidak sesuai</strong>, dan daftar tertunda dalam satu layar.</p>
      </div>

      <form method="get" class="dashboard-year-filter">
        <label for="dashboardYear" class="form-label form-label-sm mb-1">Tahun</label>
        <select
          id="dashboardYear"
          name="year"
          class="form-select form-select-sm"
          onchange="this.form.submit()">
          <?php foreach ($availableYears as $yearItem): ?>
            <option value="<?= esc($yearItem) ?>" <?= $yearItem == $selectedYear ? 'selected' : '' ?>>
              <?= esc($yearItem) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
  </section>

  <section class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h6 class="text-muted mb-0">Ringkasan Cepat</h6>
      <small class="text-muted">Periode tahun <?= esc($selectedYear) ?></small>
    </div>

    <?php
    $cards = [
      ['label' => 'Total Item', 'value' => $kpi['total'], 'tone' => 'slate', 'icon' => 'bi-grid-3x3-gap'],
      ['label' => 'Sesuai', 'value' => $kpi['sesuai'], 'tone' => 'success', 'icon' => 'bi-check-circle'],
      ['label' => 'Tidak Sesuai', 'value' => $kpi['tidak_sesuai'], 'tone' => 'danger', 'icon' => 'bi-exclamation-octagon'],
      ['label' => 'Tidak Berlaku', 'value' => $kpi['tidak_berlaku'], 'tone' => 'info', 'icon' => 'bi-dash-circle'],
      ['label' => 'Belum Diceklis', 'value' => $kpi['late'], 'tone' => 'warning', 'icon' => 'bi-clock-history'],
    ];
    ?>

    <div class="row g-3">
      <?php foreach ($cards as $card): ?>
        <div class="col-6 col-lg-4 col-xl">
          <article class="card shadow-sm border-0 h-100 compliance-kpi-card no-lift kpi-tone-<?= esc($card['tone']) ?>">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                  <p class="compliance-kpi-label mb-1"><?= esc($card['label']) ?></p>
                  <h5 class="mb-0 fw-bold"><?= esc((string)$card['value']) ?></h5>
                </div>
                <span class="compliance-kpi-icon">
                  <i class="bi <?= esc($card['icon']) ?>"></i>
                </span>
              </div>
            </div>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="mb-4">
    <h6 class="text-muted mb-2">Tren</h6>
    <article class="card shadow-sm border-0 compliance-panel no-lift" id="trendPanel">
      <div class="dashboard-loading-overlay">
        <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
        <span>Memuat tren...</span>
      </div>
      <div class="card-header bg-white border-0 pb-0">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
          <div>
            <h6 class="fw-semibold mb-1">Pergerakan Status</h6>
            <p class="text-muted small mb-0">Bandingkan status sesuai, tidak sesuai, dan tidak berlaku per periode.</p>
          </div>

          <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="btn-group btn-group-sm frequency-tabs" role="group" aria-label="Pilihan frekuensi tren">
              <button type="button" class="btn btn-outline-primary tab-frequency active" data-type="monthly">Bulanan</button>
              <button type="button" class="btn btn-outline-primary tab-frequency" data-type="weekly">Mingguan</button>
              <button type="button" class="btn btn-outline-primary tab-frequency" data-type="daily">Harian</button>
            </div>

            <select id="monthFilter" class="form-select form-select-sm w-auto">
              <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= $m == date('m') ? 'selected' : '' ?>>
                  <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                </option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="card-body pt-3">
        <div class="dashboard-chart-main compliance-chart-wrap">
          <canvas id="complianceChart"></canvas>
          <div class="chart-empty-state d-none" id="trendEmptyState">Belum ada data tren untuk filter ini.</div>
        </div>
      </div>
    </article>
  </section>

  <section class="mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
      <h6 class="text-muted mb-0">Pemantauan Operasional</h6>
      <div class="d-flex flex-wrap gap-2 dashboard-progress-filters">
        <select id="progressType" class="form-select form-select-sm w-auto">
          <option value="monthly">Bulanan</option>
          <option value="weekly">Mingguan</option>
          <option value="daily">Harian</option>
        </select>

        <select id="progressYear" class="form-select form-select-sm w-auto">
          <?php foreach ($availableYears as $yearItem): ?>
            <option value="<?= esc($yearItem) ?>" <?= $yearItem == $selectedYear ? 'selected' : '' ?>>
              <?= esc($yearItem) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <select id="progressMonth" class="form-select form-select-sm w-auto">
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= $m == date('m') ? 'selected' : '' ?>>
              <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-lg-8">
        <article class="card shadow-sm border-0 h-100 compliance-panel no-lift" id="progressPanel">
          <div class="dashboard-loading-overlay">
            <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
            <span>Memuat progress...</span>
          </div>
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
              <div>
                <h6 class="fw-semibold mb-1">Progres Ceklis</h6>
                <p class="text-muted small mb-0">Periode aktif berdasarkan frekuensi yang dipilih.</p>
              </div>
              <small id="progressMeta" class="text-muted"></small>
            </div>

            <div class="dashboard-chart-progress compliance-chart-wrap">
              <canvas id="progressChart"></canvas>
              <div class="chart-empty-state d-none" id="progressEmptyState">Belum ada data progress untuk filter ini.</div>
            </div>
          </div>
        </article>
      </div>

      <div class="col-lg-4">
        <article class="card shadow-sm border-0 h-100 compliance-panel no-lift" id="piePanel">
          <div class="dashboard-loading-overlay">
            <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
            <span>Memuat distribusi...</span>
          </div>
          <div class="card-body">
            <h6 class="fw-semibold mb-1">Distribusi Status</h6>
            <p class="text-muted small mb-2">Proporsi item sesuai vs tidak sesuai pada bulan dipilih.</p>
            <small id="statusPieMeta" class="text-muted d-block mb-2"></small>

            <div class="dashboard-chart-pie compliance-chart-wrap">
              <canvas id="statusPieChart"></canvas>
              <div class="chart-empty-state d-none" id="pieEmptyState">Belum ada data status untuk filter ini.</div>
            </div>
          </div>
        </article>
      </div>
    </div>
  </section>

  <section class="mb-4">
    <h6 class="text-muted mb-2">Wawasan Risiko</h6>
    <div class="row g-3">
      <div class="col-lg-6">
        <article class="card shadow-sm border-0 h-100 compliance-panel no-lift" id="riskItemPanel">
          <div class="dashboard-loading-overlay">
            <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
            <span>Memuat risiko item...</span>
          </div>
          <div class="card-body">
            <h6 class="fw-semibold mb-2">5 Item Teratas Paling Sering Tidak Sesuai</h6>
            <ul id="topItemRisk" class="list-group list-group-flush small compliance-risk-list">
              <li class="list-group-item text-muted">Memuat data...</li>
            </ul>
          </div>
        </article>
      </div>

      <div class="col-lg-6">
        <article class="card shadow-sm border-0 h-100 compliance-panel no-lift" id="riskAreaPanel">
          <div class="dashboard-loading-overlay">
            <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
            <span>Memuat risiko area...</span>
          </div>
          <div class="card-body">
            <h6 class="fw-semibold mb-2">5 Area Teratas Paling Sering Tidak Sesuai</h6>
            <ul id="topAreaRisk" class="list-group list-group-flush small compliance-risk-list">
              <li class="list-group-item text-muted">Memuat data...</li>
            </ul>
          </div>
        </article>
      </div>
    </div>
  </section>

  <section>
    <h6 class="text-muted mb-2">Ceklis Tertunda (Periode Aktif)</h6>
    <article class="card shadow-sm border-0 compliance-panel no-lift" id="pendingPanel">
      <div class="dashboard-loading-overlay">
        <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
        <span>Memuat ceklis tertunda...</span>
      </div>
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2 pending-controls">
          <div class="d-flex gap-2 flex-wrap align-items-center pending-controls-left">
            <input
              type="text"
              id="pendingSearch"
              class="form-control form-control-sm pending-search"
              placeholder="Cari item, area, atau PIC...">

            <select id="pendingMonth" class="form-select form-select-sm w-auto">
              <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= $m == date('m') ? 'selected' : '' ?>>
                  <?= date('M', mktime(0, 0, 0, $m, 1)) ?>
                </option>
              <?php endfor; ?>
            </select>

            <select id="pendingFrequency" class="form-select form-select-sm w-auto">
              <option value="">Semua Frekuensi</option>
              <option value="daily">Harian</option>
              <option value="weekly">Mingguan</option>
              <option value="monthly">Bulanan</option>
            </select>

            <select id="pendingSort" class="form-select form-select-sm w-auto">
              <option value="name">Urutkan: Item</option>
              <option value="area">Urutkan: Area</option>
              <option value="frequency">Urutkan: Frekuensi</option>
              <option value="status">Urutkan: Belum Diceklis Terbanyak</option>
            </select>
          </div>

          <small class="text-muted">
            <span id="pendingCount">0</span> hasil
          </small>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3 compliance-summary-cards">
          <div class="summary-pill summary-pill-daily">
            <small>Harian</small>
            <strong id="summaryDaily">0</strong>
          </div>

          <div class="summary-pill summary-pill-weekly">
            <small>Mingguan</small>
            <strong id="summaryWeekly">0</strong>
          </div>

          <div class="summary-pill summary-pill-monthly">
            <small>Bulanan</small>
            <strong id="summaryMonthly">0</strong>
          </div>

          <div class="summary-pill summary-pill-total">
            <small>Total</small>
            <strong id="summaryTotal">0</strong>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0 pending-table">
          <thead>
            <tr>
              <th>Item</th>
              <th>Area</th>
              <th>PIC</th>
              <th>Frekuensi</th>
              <th>Belum Diceklis</th>
            </tr>
          </thead>
          <tbody id="pendingTableBody">
            <tr>
              <td colspan="5" class="text-muted">Memuat data ceklis tertunda...</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="card-body pt-3">
        <div id="pendingPagination" class="pending-pagination d-flex flex-wrap gap-1"></div>
      </div>
    </article>
  </section>
</div>

<script>
  const baseUrl = "<?= rtrim(base_url(), '/') ?>";
  const selectedYear = "<?= esc($selectedYear) ?>";
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= base_url('js/dashboard.js') ?>?v=<?= time() ?>"></script>

<?= $this->endSection() ?>