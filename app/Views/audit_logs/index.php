<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="audit-log-page" x-data="auditLogSearch()">
  <section class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
          <p class="text-muted mb-0 small">Log aktivitas sistem</p>
          <h5 class="fw-bold mb-0">Audit Log</h5>
        </div>
        <small class="text-muted">
          Total: <strong><?= number_format($pager['total']) ?></strong> entri
        </small>
      </div>

      <!-- ── FILTER FORM ── -->
      <form method="get" action="<?= site_url('audit-logs') ?>" class="row g-2 align-items-end">
        <!-- Search -->
        <div class="col-12 col-md-3">
          <label class="form-label small mb-1">Cari</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input
              type="text"
              name="q"
              class="form-control"
              placeholder="Deskripsi, aksi, atau user..."
              value="<?= esc($q) ?>">
          </div>
        </div>

        <!-- Action filter -->
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Aksi</label>
          <select name="action" class="form-select form-select-sm">
            <option value="">Semua Aksi</option>
            <?php foreach ($actionList as $a): ?>
              <option value="<?= esc($a['action']) ?>" <?= $filterAction === $a['action'] ? 'selected' : '' ?>>
                <?= esc($a['action']) ?> (<?= (int) $a['cnt'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- User filter -->
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">User</label>
          <select name="user_id" class="form-select form-select-sm">
            <option value="">Semua User</option>
            <?php foreach ($userList as $u): ?>
              <option value="<?= (int) $u['user_id'] ?>" <?= $filterUserId !== '' && (int) $filterUserId === (int) $u['user_id'] ? 'selected' : '' ?>>
                <?= esc($u['name'] ?: $u['username']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Date from -->
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Dari Tgl</label>
          <input type="date" name="date_from" class="form-control form-control-sm" value="<?= esc($dateFrom) ?>">
        </div>

        <!-- Date to -->
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Sampai Tgl</label>
          <input type="date" name="date_to" class="form-control form-control-sm" value="<?= esc($dateTo) ?>">
        </div>

        <!-- Buttons -->
        <div class="col-12 col-md-1 d-flex gap-1">
          <button type="submit" class="btn btn-primary btn-sm flex-fill">
            <i class="bi bi-funnel"></i> Filter
          </button>
          <a href="<?= site_url('audit-logs') ?>" class="btn btn-outline-secondary btn-sm flex-fill">
            <i class="bi bi-x-lg"></i>
          </a>
        </div>
      </form>
    </div>
  </section>

  <!-- ── TABLE ── -->
  <section class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 audit-log-table">
          <thead class="table-light">
            <tr>
              <th style="width:50px">#</th>
              <th>
                <a href="<?= esc(build_sort_url('user_name')) ?>" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                  User
                  <?= sort_icon('user_name', $sort, $dir) ?>
                </a>
              </th>
              <th>
                <a href="<?= esc(build_sort_url('action')) ?>" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                  Aksi
                  <?= sort_icon('action', $sort, $dir) ?>
                </a>
              </th>
              <th>Deskripsi</th>
              <th>IP</th>
              <th>
                <a href="<?= esc(build_sort_url('created_at')) ?>" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                  Waktu
                  <?= sort_icon('created_at', $sort, $dir) ?>
                </a>
              </th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($logs)): ?>
              <?php $i = ($pager['page'] - 1) * $pager['per_page'] + 1; ?>
              <?php foreach ($logs as $log): ?>
                <tr>
                  <td class="text-muted small"><?= $i++ ?></td>
                  <td>
                    <span class="fw-semibold small"><?= esc($log['user_name'] ?? 'System') ?></span>
                    <?php if (!empty($log['username'])): ?>
                      <br><small class="text-muted">@<?= esc($log['username']) ?></small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php $badgeClass = match ($log['action']) {
                      'login', 'logout' => 'bg-info',
                      'create_user', 'create_role' => 'bg-success',
                      'update_user', 'change_password' => 'bg-warning text-dark',
                      'delete_user' => 'bg-danger',
                      'assign_asset', 'unassign_asset' => 'bg-primary',
                      default => 'bg-secondary'
                    } ?>
                    <span class="badge <?= $badgeClass ?>"><?= esc($log['action']) ?></span>
                  </td>
                  <td class="small"><?= esc($log['description']) ?></td>
                  <td>
                    <code class="small text-muted"><?= esc($log['ip_address'] ?? '-') ?></code>
                  </td>
                  <td class="small text-nowrap">
                    <?= esc(date('d/m/Y H:i', strtotime($log['created_at']))) ?>
                  </td>
                </tr>
              <?php endforeach ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-center text-muted py-4">
                  <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                  Tidak ada data audit log
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── PAGINATION ── -->
    <?php if ($pager['total'] > $pager['per_page']): ?>
      <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <small class="text-muted">
          Menampilkan <?= (($pager['page'] - 1) * $pager['per_page'] + 1) ?> –
          <?= min($pager['page'] * $pager['per_page'], $pager['total']) ?>
          dari <?= number_format($pager['total']) ?>
        </small>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <?php $totalPages = (int) ceil($pager['total'] / $pager['per_page']); ?>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <?php if ($p === 1 || $p === $totalPages || abs($p - $pager['page']) <= 2): ?>
                <li class="page-item <?= $p === $pager['page'] ? 'active' : '' ?>">
                  <a class="page-link" href="<?= esc(build_page_url($p)) ?>"><?= $p ?></a>
                </li>
              <?php elseif (abs($p - $pager['page']) === 3): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
              <?php endif; ?>
            <?php endfor; ?>
          </ul>
        </nav>
      </div>
    <?php endif; ?>
  </section>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  .audit-log-page .audit-log-table th {
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
    vertical-align: middle;
  }
  .audit-log-page .audit-log-table td {
    font-size: 0.85rem;
    vertical-align: middle;
  }
  .audit-log-page .badge {
    font-weight: 500;
  }
  .audit-log-page code {
    font-size: 0.75rem;
  }

  @media (max-width: 768px) {
    .audit-log-page .table-responsive {
      overflow: hidden;
    }
    .audit-log-page .audit-log-table {
      table-layout: fixed;
      width: 100%;
    }
    .audit-log-page .audit-log-table th {
      font-size: 0.55rem;
      letter-spacing: 0;
    }
    .audit-log-page .audit-log-table td {
      font-size: 0.62rem;
      word-wrap: break-word;
      white-space: normal;
      padding: 0.3rem 0.2rem;
      line-height: 1.25;
    }
    .audit-log-page .audit-log-table td:nth-child(5),
    .audit-log-page .audit-log-table th:nth-child(5) {
      display: none;
    }
    .audit-log-page .audit-log-table td:nth-child(4) {
      max-width: 180px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .audit-log-page .audit-log-table .badge {
      font-size: 0.55rem;
      padding: 0.08rem 0.25rem;
    }
    .audit-log-page code {
      font-size: 0.58rem;
    }
  }
</style>
<?= $this->endSection() ?>
