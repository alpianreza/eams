<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="ranking-page">
  <section class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
          <p class="text-muted mb-0 small">Ranking user berdasarkan checklist</p>
          <h5 class="fw-bold mb-0">Ranking Checklist User</h5>
        </div>

        <!-- NAV BULAN -->
        <form method="get" class="d-flex align-items-center gap-2">
          <a href="?ym=<?= esc($prevYM) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-chevron-left"></i>
          </a>
          <select name="ym" class="form-select form-select-sm" style="width:auto;min-width:140px" onchange="this.form.submit()">
            <?php for ($y = (int) date('Y'); $y >= 2025; $y--): ?>
              <?php for ($m = 1; $m <= 12; $m++): ?>
                <?php $val = sprintf('%04d-%02d', $y, $m); ?>
                <?php if ($val > date('Y-m')) continue; ?>
                <option value="<?= $val ?>" <?= $val === $ym ? 'selected' : '' ?>>
                  <?= date('F Y', strtotime($val . '-01')) ?>
                </option>
              <?php endfor; ?>
            <?php endfor; ?>
          </select>
          <?php if ($canNext): ?>
            <a href="?ym=<?= esc($nextYM) ?>" class="btn btn-outline-secondary btn-sm">
              <i class="bi bi-chevron-right"></i>
            </a>
          <?php else: ?>
            <button class="btn btn-outline-secondary btn-sm" disabled>
              <i class="bi bi-chevron-right"></i>
            </button>
          <?php endif; ?>
        </form>
      </div>

      <!-- RINGKASAN CARD -->
      <?php
      $topUser = $rankings[0] ?? null;
      $worstUser = ! empty($rankings) ? end($rankings) : null;
      $avgScore = ! empty($rankings) ? round(array_sum(array_column($rankings, 'score')) / count($rankings), 1) : 0;
      $avgOntime = ! empty($rankings) ? round(array_sum(array_column($rankings, 'ontime_pct')) / count($rankings), 1) : 0;
      ?>
      <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
          <div class="card bg-success bg-opacity-10 border-0 h-100">
            <div class="card-body text-center py-2 px-2">
              <small class="text-muted d-block">Terbaik</small>
              <strong class="fs-6"><?= esc($topUser['user'] ?? '-') ?></strong>
              <small class="d-block text-success fw-semibold">Score: <?= (int) ($topUser['score'] ?? 0) ?></small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card bg-info bg-opacity-10 border-0 h-100">
            <div class="card-body text-center py-2 px-2">
              <small class="text-muted d-block">Rata-rata Score</small>
              <strong class="fs-6"><?= number_format($avgScore, 1) ?></strong>
              <small class="d-block text-info fw-semibold"><?= number_format($avgOntime, 1) ?>% on-time</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card bg-warning bg-opacity-10 border-0 h-100">
            <div class="card-body text-center py-2 px-2">
              <small class="text-muted d-block">Total User Aktif</small>
              <strong class="fs-6"><?= count($rankings) ?></strong>
              <small class="d-block text-warning fw-semibold">user</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card bg-primary bg-opacity-10 border-0 h-100">
            <div class="card-body text-center py-2 px-2">
              <small class="text-muted d-block">Total Checklist</small>
              <strong class="fs-6"><?= number_format(array_sum(array_column($rankings, 'total'))) ?></strong>
              <small class="d-block text-primary fw-semibold">submission</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- TABEL RANKING -->
  <section class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table align-middle mb-0 ranking-table">
          <thead class="table-light">
            <tr>
              <th style="width:8%">Rank</th>
              <th>User</th>
              <th class="text-center" style="width:9%">Total</th>
              <th class="text-center" style="width:9%">On-time</th>
              <th class="text-center" style="width:8%">Late</th>
              <th class="text-center" style="width:12%">% On-time</th>
              <th class="text-center d-none d-md-table-cell" style="width:8%">✅ OK</th>
              <th class="text-center d-none d-md-table-cell" style="width:8%">⚠️ Not OK</th>
              <th class="text-center" style="width:9%">Score</th>
              <th class="d-none d-lg-table-cell" style="width:12%">Terakhir</th>
            </tr>
          </thead>
          <tbody>
            <?php if (! empty($rankings)): ?>
              <?php
              $prevScore = null;
              $prevRank = 0;
              ?>
              <?php foreach ($rankings as $i => $r): ?>
                <?php
                if ($r['score'] !== $prevScore) {
                  $rank = $i + 1;
                  $prevScore = $r['score'];
                  $prevRank = $rank;
                } else {
                  $rank = $prevRank;
                }

                $rankBadge = match (true) {
                  $rank === 1 => 'bg-warning text-dark',
                  $rank === 2 => 'bg-secondary',
                  $rank === 3 => 'bg-danger bg-opacity-75',
                  $rank <= ceil(count($rankings) * 0.25) => 'bg-success',
                  $rank > ceil(count($rankings) * 0.75) => 'bg-light text-muted border',
                  default => 'bg-info bg-opacity-50 text-dark',
                };

                $rankIcon = match ($rank) {
                  1 => '<i class="bi bi-trophy-fill text-warning"></i>',
                  2 => '<i class="bi bi-trophy-fill text-secondary"></i>',
                  3 => '<i class="bi bi-trophy-fill" style="color:#cd7f32"></i>',
                  default => "#{$rank}"
                };

                $ontimeClass = match (true) {
                  $r['ontime_pct'] >= 90 => 'text-success fw-bold',
                  $r['ontime_pct'] >= 70 => 'text-warning fw-semibold',
                  default => 'text-danger',
                };
                ?>
                <tr>
                  <td class="text-center">
                    <span class="badge <?= $rankBadge ?> rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width:32px;height:32px">
                      <?= $rankIcon ?>
                    </span>
                  </td>
                  <td>
                    <span class="fw-semibold small"><?= esc($r['user']) ?></span>
                  </td>
                  <td class="text-center fw-semibold"><?= (int) $r['total'] ?></td>
                  <td class="text-center text-success fw-semibold"><?= (int) $r['ontime'] ?></td>
                  <td class="text-center text-danger"><?= (int) $r['late'] ?></td>
                  <td class="text-center <?= $ontimeClass ?>"><?= number_format($r['ontime_pct'], 1) ?>%</td>
                  <td class="text-center d-none d-md-table-cell"><?= (int) $r['ok_count'] ?></td>
                  <td class="text-center d-none d-md-table-cell">
                    <?php if ($r['not_ok'] > 0): ?>
                      <span class="text-danger"><?= (int) $r['not_ok'] ?></span>
                    <?php else: ?>
                      <span class="text-muted">0</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <strong class="<?= $rank <= 3 ? 'text-success' : '' ?>"><?= (int) $r['score'] ?></strong>
                  </td>
                  <td class="small text-muted d-none d-lg-table-cell">
                    <?= $r['last_date'] ? date('d/m/Y', strtotime($r['last_date'])) : '-' ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="10" class="text-center text-muted py-4">
                  <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                  Belum ada data checklist bulan ini
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  .ranking-page .ranking-table th {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
    vertical-align: middle;
  }
  .ranking-page .ranking-table td {
    font-size: 0.85rem;
    vertical-align: middle;
  }
  @media (max-width: 768px) {
    .ranking-page .table-responsive {
      overflow: hidden;
    }

    .ranking-page .ranking-table {
      table-layout: fixed;
      width: 100%;
      min-width: 0;
    }

    .ranking-page .ranking-table th,
    .ranking-page .ranking-table td {
      font-size: 0.62rem;
      padding: 0.3rem 0.2rem;
      word-wrap: break-word;
      white-space: normal;
      line-height: 1.25;
    }

    .ranking-page .ranking-table th {
      font-size: 0.55rem;
      letter-spacing: 0;
    }

    .ranking-page .ranking-table .badge {
      width: 24px !important;
      height: 24px !important;
      padding: 0.1rem !important;
      font-size: 0.55rem;
    }

    .ranking-page .ranking-table td:nth-child(4),
    .ranking-page .ranking-table th:nth-child(4) {
      display: none;
    }
  }
</style>
<?= $this->endSection() ?>
