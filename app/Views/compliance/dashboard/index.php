<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">

  <!-- ================= HEADER ================= -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Compliance Control Center</h4>

    <form method="get" class="d-flex align-items-center gap-2">
      <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
        <?php foreach ($availableYears as $yearItem): ?>
          <option value="<?= $yearItem ?>" <?= $yearItem == $selectedYear ? 'selected' : '' ?>>
            <?= $yearItem ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>


  <!-- ================= KPI SECTION ================= -->
  <h6 class="text-muted mb-3">Overview</h6>

  <div class="row g-3 mb-4">

    <?php
    $cards = [
      ['label' => 'Total Item', 'value' => $kpi['total'], 'color' => 'secondary'],
      ['label' => '✓ Sesuai', 'value' => $kpi['sesuai'], 'color' => 'success'],
      ['label' => '✗ Tidak Sesuai', 'value' => $kpi['tidak_sesuai'], 'color' => 'danger'],
      ['label' => '– Tidak Berlaku', 'value' => $kpi['tidak_berlaku'], 'color' => 'info'],
      ['label' => 'Late', 'value' => $kpi['late'], 'color' => 'warning'],
    ];
    ?>

    <?php foreach ($cards as $card): ?>
      <div class="col-md col-6">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="text-muted small"><?= $card['label'] ?></div>
                <div class="h4 fw-bold mb-0"><?= $card['value'] ?></div>
              </div>
              <div class="text-<?= $card['color'] ?> fs-5">●</div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

  </div>


  <!-- ================= TREND SECTION ================= -->
  <h6 class="text-muted mb-3">Compliance Trend</h6>

  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center border-0">

      <div>
        <button class="btn btn-sm btn-outline-primary tab-frequency active" data-type="monthly">Monthly</button>
        <button class="btn btn-sm btn-outline-primary tab-frequency" data-type="weekly">Weekly</button>
        <button class="btn btn-sm btn-outline-primary tab-frequency" data-type="daily">Daily</button>
      </div>

      <select id="monthFilter" class="form-select form-select-sm w-auto">
        <?php for ($m = 1; $m <= 12; $m++): ?>
          <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>"
            <?= $m == date('m') ? 'selected' : '' ?>>
            <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
          </option>
        <?php endfor; ?>
      </select>

    </div>

    <div class="card-body">
      <div style="height:380px;">
        <canvas id="complianceChart"></canvas>
      </div>
    </div>
  </div>


  <!-- ================= OPERATIONAL SECTION ================= -->
  <h6 class="text-muted mb-3">Operational Monitoring</h6>

  <div class="row g-3">

    <!-- FILTER -->
    <div class="col-12 d-flex gap-2">
      <select id="progressType" class="form-select form-select-sm w-auto">
        <option value="monthly">Monthly</option>
        <option value="weekly">Weekly</option>
        <option value="daily">Daily</option>
      </select>

      <select id="progressYear" class="form-select form-select-sm w-auto">
        <?php for ($y = 2026; $y <= date('Y'); $y++): ?>
          <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>>
            <?= $y ?>
          </option>
        <?php endfor; ?>
      </select>

      <select id="progressMonth" class="form-select form-select-sm w-auto">
        <?php for ($m = 1; $m <= 12; $m++): ?>
          <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>"
            <?= $m == date('m') ? 'selected' : '' ?>>
            <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
          </option>
        <?php endfor; ?>
      </select>
    </div>


    <!-- PROGRESS CHART -->
    <div class="col-md-8">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <h6 class="fw-semibold mb-3">Progress Checklist</h6>
          <div style="height:350px;">
            <canvas id="progressChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- PIE -->
    <div class="col-md-4">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <h6 class="fw-semibold mb-3">Status Distribusi</h6>
          <div style="height:350px;">
            <canvas id="statusPieChart"></canvas>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- ================= RISK INSIGHT ================= -->
  <h6 class="text-muted mt-5 mb-3">Risk Insight</h6>

  <div class="row g-3">

    <div class="col-md-6">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h6 class="fw-semibold mb-3">Top 5 Item Paling Sering ✗</h6>
          <ul id="topItemRisk" class="list-group list-group-flush small">
            <li class="text-muted">Loading...</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h6 class="fw-semibold mb-3">Top 5 Area Paling Sering ✗</h6>
          <ul id="topAreaRisk" class="list-group list-group-flush small">
            <li class="text-muted">Loading...</li>
          </ul>
        </div>
      </div>
    </div>

  </div>

</div>

<h6 class="text-muted mt-5 mb-3">🚨 Pending Checklist (Periode Aktif)</h6>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <div class="card mb-3 shadow-sm">
      <div class="card-body d-flex flex-wrap gap-2 align-items-center">

        <!-- Search -->
        <input type="text"
          id="pendingSearch"
          class="form-control form-control-sm"
          placeholder="Cari inventory / area / PIC..."
          style="max-width:250px;">

        <!-- Month -->
        <select id="pendingMonth"
          class="form-select form-select-sm w-auto">
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>"
              <?= $m == date('m') ? 'selected' : '' ?>>
              <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
            </option>
          <?php endfor; ?>
        </select>

        <!-- Frequency -->
        <select id="pendingFrequency"
          class="form-select form-select-sm w-auto">
          <option value="">Semua Frequency</option>
          <option value="daily">Daily</option>
          <option value="weekly">Weekly</option>
          <option value="monthly">Monthly</option>
        </select>

      </div>
    </div>

    <table class="table table-sm align-middle mb-0">
      <thead>
        <tr>
          <th>Inventory</th>
          <th>Area</th>
          <th>PIC</th>
          <th>Frequency</th>
          <th>Unchecked</th>
        </tr>
      </thead>

      <tbody id="pendingTableBody">
        <tr>
          <td colspan="4" class="text-muted">Loading...</td>
        </tr>
      </tbody>
    </table>
    <div class="mt-3" id="pendingPagination"></div>

  </div>
</div>

</div>



<script>
  const baseUrl = "<?= rtrim(base_url(), '/') ?>";
  const selectedYear = "<?= $selectedYear ?>";
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= base_url('/js/dashboard.js') ?>"></script>

<?= $this->endSection() ?>