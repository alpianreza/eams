<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?= view('components/compliance/page-header', [
    'title' => 'Dashboard Compliance',
    'summary' => 'Pantau performa ceklis, risiko tidak sesuai, dan daftar tertunda dalam satu layar.',
    'actions' => '
      <form method="get" class="d-flex align-items-center gap-2">
        <label for="dashboardYear" class="form-label form-label-sm mb-0">Tahun</label>
        <select id="dashboardYear" name="year" class="form-select form-select-sm" onchange="this.form.submit()">
          ' . implode('', array_map(fn($y) => "<option value='{$y}' " . ($y == $selectedYear ? 'selected' : '') . ">{$y}</option>", $availableYears)) . '
        </select>
      </form>
    '
]) ?>

<!-- Ringkasan Cepat -->
<?= view('components/compliance/section-heading', [
    'title' => 'Ringkasan Cepat',
    'meta' => 'Periode tahun ' . esc($selectedYear)
]) ?>

<!-- Ringkasan Cepat -->
<?= view('components/compliance/section-heading', [
    'title' => 'Ringkasan Cepat',
    'meta' => 'Periode tahun ' . esc($selectedYear)
]) ?>

<?php
$cards = [
    ['label' => 'Total Item', 'value' => $kpi['total'], 'tone' => 'slate', 'icon' => 'bi-grid-3x3-gap'],
    ['label' => 'Sesuai', 'value' => $kpi['sesuai'], 'tone' => 'success', 'icon' => 'bi-check-circle'],
    ['label' => 'Tidak Sesuai', 'value' => $kpi['tidak_sesuai'], 'tone' => 'danger', 'icon' => 'bi-exclamation-octagon'],
    ['label' => 'Tidak Berlaku', 'value' => $kpi['tidak_berlaku'], 'tone' => 'info', 'icon' => 'bi-dash-circle'],
    ['label' => 'Belum Diceklis', 'value' => $kpi['late'], 'tone' => 'warning', 'icon' => 'bi-clock-history'],
];
?>

<div class="row g-3 mb-4">
    <?php foreach ($cards as $card): ?>
        <div class="col-6 col-lg-4 col-xl">
            <article class="console-metric-card console-metric-card--<?= esc($card['tone']) ?>" data-icon="<?= esc($card['icon']) ?>">
                <div class="console-metric-card__top">
                    <span class="console-metric-card__label"><?= esc($card['label']) ?></span>
                    <span class="console-metric-card__icon"><i class="bi <?= esc($card['icon']) ?>"></i></span>
                </div>
                <h5 class="console-metric-card__value"><?= esc((string)$card['value']) ?></h5>
            </article>
        </div>
    <?php endforeach; ?>
</div>
  </section>

  <!-- Tren -->
  <?= view('components/compliance/section-heading', [
      'title' => 'Tren Pergerakan Status',
      'meta' => 'Bandingkan status sesuai, tidak sesuai, dan tidak berlaku per periode.'
  ]) ?>

  <article class="console-work-panel mb-4" id="trendPanel">
    <div class="dashboard-loading-overlay">
      <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
      <span>Memuat tren...</span>
    </div>

    <div class="console-work-panel__header">
      <h6 class="console-work-panel__title">Pergerakan Status</h6>
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

    <div class="console-work-panel__body">
      <div class="dashboard-chart-main compliance-chart-wrap">
        <canvas id="complianceChart"></canvas>
        <div class="chart-empty-state d-none" id="trendEmptyState">Belum ada data tren untuk filter ini.</div>
      </div>
    </div>
  </article>

  <!-- Pemantauan Operasional -->
  <?= view('components/compliance/section-heading', [
      'title' => 'Pemantauan Operasional'
  ]) ?>

  <div class="row g-3 mb-4">
    <div class="col-lg-8">
      <article class="console-work-panel h-100" id="progressPanel">
        <div class="dashboard-loading-overlay">
          <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
          <span>Memuat progress...</span>
        </div>
        <div class="console-work-panel__header">
          <div>
            <h6 class="console-work-panel__title">Progres Ceklis</h6>
            <p class="console-work-panel__copy">Periode aktif berdasarkan frekuensi yang dipilih.</p>
          </div>
          <small id="progressMeta" class="text-muted"></small>
        </div>
        <div class="console-work-panel__body">
            <div class="d-flex flex-wrap gap-2 dashboard-progress-filters mb-2">
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
            <div class="dashboard-chart-progress compliance-chart-wrap">
              <canvas id="progressChart"></canvas>
              <div class="chart-empty-state d-none" id="progressEmptyState">Belum ada data progress untuk filter ini.</div>
            </div>
        </div>
      </article>
    </div>

    <div class="col-lg-4">
      <article class="console-work-panel h-100" id="piePanel">
        <div class="dashboard-loading-overlay">
          <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
          <span>Memuat distribusi...</span>
        </div>
        <div class="console-work-panel__body">
          <h6 class="console-work-panel__title mb-1">Distribusi Status</h6>
          <p class="console-work-panel__copy mb-2">Proporsi item sesuai vs tidak sesuai pada bulan dipilih.</p>
          <small id="statusPieMeta" class="text-muted d-block mb-2"></small>

          <div class="dashboard-chart-pie compliance-chart-wrap">
            <canvas id="statusPieChart"></canvas>
            <div class="chart-empty-state d-none" id="pieEmptyState">Belum ada data status untuk filter ini.</div>
          </div>
        </div>
      </article>
    </div>
  </div>

  <!-- Wawasan Risiko -->
  <?= view('components/compliance/section-heading', [
      'title' => 'Wawasan Risiko'
  ]) ?>
  <div class="row g-3 mb-4">
    <div class="col-lg-6">
      <article class="console-work-panel h-100" id="riskItemPanel">
        <div class="dashboard-loading-overlay">
          <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
          <span>Memuat risiko item...</span>
        </div>
        <div class="console-work-panel__body">
          <h6 class="console-work-panel__title mb-2">5 Item Teratas Paling Sering Tidak Sesuai</h6>
          <ul id="topItemRisk" class="list-group list-group-flush small compliance-risk-list">
            <li class="list-group-item text-muted">Memuat data...</li>
          </ul>
        </div>
      </article>
    </div>

    <div class="col-lg-6">
      <article class="console-work-panel h-100" id="riskAreaPanel">
        <div class="dashboard-loading-overlay">
          <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
          <span>Memuat risiko area...</span>
        </div>
        <div class="console-work-panel__body">
          <h6 class="console-work-panel__title mb-2">5 Area Teratas Paling Sering Tidak Sesuai</h6>
          <ul id="topAreaRisk" class="list-group list-group-flush small compliance-risk-list">
            <li class="list-group-item text-muted">Memuat data...</li>
          </ul>
        </div>
      </article>
    </div>
  </div>

  <!-- Ceklis Tertunda -->
  <?= view('components/compliance/section-heading', [
      'title' => 'Ceklis Tertunda (Periode Aktif)'
  ]) ?>

  <article class="console-work-panel" id="pendingPanel">
    <div class="dashboard-loading-overlay">
      <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
      <span>Memuat ceklis tertunda...</span>
    </div>
    <div class="console-data-toolbar">
      <div class="d-flex flex-wrap gap-2 pending-controls-left">
        <input type="text" id="pendingSearch" class="form-control form-control-sm pending-search" placeholder="Cari item, area, atau PIC...">
        <select id="pendingMonth" class="form-select form-select-sm w-auto">
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= $m == date('m') ? 'selected' : '' ?>><?= date('M', mktime(0, 0, 0, $m, 1)) ?></option>
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
      <div class="console-data-toolbar__meta">
        <span id="pendingCount">0</span> hasil
      </div>
    </div>

    <div class="console-work-panel__body">
      <div class="d-flex flex-wrap gap-2 mb-3 compliance-summary-cards">
        <div class="summary-pill summary-pill-daily"><small>Harian</small><strong id="summaryDaily">0</strong></div>
        <div class="summary-pill summary-pill-weekly"><small>Mingguan</small><strong id="summaryWeekly">0</strong></div>
        <div class="summary-pill summary-pill-monthly"><small>Bulanan</small><strong id="summaryMonthly">0</strong></div>
        <div class="summary-pill summary-pill-total"><small>Total</small><strong id="summaryTotal">0</strong></div>
      </div>

      <div class="console-table-wrap">
        <table class="table console-table pending-table">
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
            <tr><td colspan="5" class="text-muted">Memuat data ceklis tertunda...</td></tr>
          </tbody>
        </table>
      </div>

      <div class="pt-3">
        <div id="pendingPagination" class="pending-pagination d-flex flex-wrap gap-1"></div>
      </div>
    </div>
  </article>
</div>

<script>
  const baseUrl = "<?= rtrim(base_url(), '/') ?>";
  const selectedYear = "<?= esc($selectedYear) ?>";
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= base_url('js/dashboard.js') ?>?v=<?= time() ?>"></script>

<?= $this->endSection() ?>