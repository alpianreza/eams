<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?= view('components/compliance/page-header', [
    'title' => 'Beranda Compliance',
    'eyebrow' => 'Dashboard Utama',
    'summary' => 'Status checklist periode <strong>' . esc($selectedMonthLabel) . '</strong>'
]) ?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['label' => 'Total Inventory', 'value' => (int)$summary['total'], 'tone' => 'slate', 'icon' => 'bi-box-seam'],
        ['label' => 'Belum Checklist', 'value' => (int)$summary['pending'], 'tone' => 'warning', 'icon' => 'bi-hourglass-split'],
        ['label' => 'Temuan', 'value' => (int)$summary['not_ok'], 'tone' => 'danger', 'icon' => 'bi-exclamation-triangle'],
        ['label' => 'Progress', 'value' => (int)$progress . '%', 'tone' => 'success', 'icon' => 'bi-graph-up-arrow'],
    ];
    ?>
    <?php foreach ($cards as $card): ?>
        <div class="col-6 col-lg-3">
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

<div class="row g-3">
    <!-- Main Content Area -->
    <div class="col-lg-8">
        <?= view('components/compliance/section-heading', [
            'title' => 'Inventory Belum Checklist'
        ]) ?>

        <article class="console-work-panel">
            <div class="console-data-toolbar">
                <div class="console-data-toolbar__title">List item tertunda</div>
                <div class="console-data-toolbar__meta">
                    <form method="get" class="d-flex align-items-center gap-2">
                        <label for="monthFilter" class="form-label form-label-sm mb-0">Periode</label>
                        <select id="monthFilter" name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                          <?php
                          $start = new DateTime('2026-01-01');
                          $end   = new DateTime(date('Y-m-01'));
                          while ($start <= $end):
                            $value = $start->format('Y-m');
                            $label = ($monthMap[(int) $start->format('n')] ?? $start->format('F')) . ' ' . $start->format('Y');
                          ?>
                            <option value="<?= esc($value) ?>" <?= $selectedMonth === $value ? 'selected' : '' ?>>
                              <?= esc($label) ?>
                            </option>
                          <?php
                            $start->modify('+1 month');
                          endwhile;
                          ?>
                        </select>
                    </form>
                </div>
            </div>
            <div class="console-work-panel__body">
                <div class="console-table-wrap">
                    <table class="table console-table home-pending-table">
                        <thead>
                            <tr>
                                <th scope="col">No</th>
                                <th scope="col">Nama Item</th>
                                <th scope="col">Lokasi</th>
                                <th scope="col">Freq</th>
                                <th scope="col">Sisa</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pendingList)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Semua periode sudah selesai.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pendingList as $i => $inv): ?>
                                    <?php
                                    $missingPeriods = $inv['missing_periods'] ?? [];
                                    $frequencyRaw = strtolower((string)($inv['checklist_frequency'] ?? 'monthly'));
                                    $frequencyLabel = match ($frequencyRaw) {
                                        'daily' => 'Harian',
                                        'weekly' => 'Mingguan',
                                        default => 'Bulanan',
                                    };
                                    ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td class="fw-bold text-slate-800"><?= esc($inv['item_name'] ?? '-') ?></td>
                                        <td><?= esc($inv['specific_area'] ?? '-') ?></td>
                                        <td><span class="console-status console-status--inactive"><?= esc($frequencyLabel) ?></span></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-warning open-popover" data-id="<?= (int) $inv['id'] ?>" data-frequency="<?= esc($frequencyRaw) ?>" data-missing="<?= esc(json_encode($missingPeriods), 'attr') ?>">
                                                <?= (int) ($inv['remaining'] ?? 0) ?>
                                            </button>
                                        </td>
                                        <td>
                                            <a href="<?= esc(base_url('compliance/checklist/' . (int) $inv['id']) . '?period_key=' . urlencode($selectedMonth)) ?>" class="btn btn-primary btn-sm console-action-button">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </article>
    </div>

    <!-- Right Sidebar/Widget Area -->
    <div class="col-lg-4">
        <?= view('components/compliance/section-heading', [
            'title' => 'Quick Actions'
        ]) ?>
        <div class="d-grid gap-2">
            <a href="<?= base_url('compliance/inventory') ?>" class="btn btn-primary">
              <i class="bi bi-list-check me-2"></i> Mulai Ceklis
            </a>
            <?php if (hasRole(['admin', 'compliance', 'auditor'])): ?>
                <a href="<?= base_url('compliance/dashboard') ?>" class="btn btn-outline-primary">
                  <i class="bi bi-clipboard-data me-2"></i> Dashboard Compliance
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/home-dashboard.css?v=' . filemtime(FCPATH . 'assets/css/home-dashboard.css')) ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  window.HOME_DASHBOARD = {
    selectedMonth: "<?= esc($selectedMonth) ?>",
    checklistBaseUrl: "<?= rtrim(base_url('compliance/checklist'), '/') ?>"
  };
</script>
<script src="<?= base_url('js/home-dashboard.js?v=' . filemtime(FCPATH . 'js/home-dashboard.js')) ?>"></script>
<?= $this->endSection() ?>
