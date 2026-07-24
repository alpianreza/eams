<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm border-0">
  <div class="card-body">

    <h5 class="mb-3">Audit Log</h5>

    <table class="table table-sm table-bordered table-striped">
      <thead class="table-light">
        <tr>
          <th>No</th>
          <th>User</th>
          <th>Action</th>
          <th>Deskripsi</th>
          <th>Waktu</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($logs)): ?>
          <?php $i = 1;
          foreach ($logs as $log): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td>
                <?= esc($log['user_name'] ?? 'System') ?><br>
                <small class="text-muted">
                  <?= esc($log['username'] ?? '-') ?>
                </small>
              </td>
              <td>
                <span class="badge bg-secondary">
                  <?= esc($log['action']) ?>
                </span>
              </td>
              <td><?= esc($log['description']) ?></td>
              <td><?= esc($log['created_at']) ?></td>
            </tr>
          <?php endforeach ?>
        <?php else: ?>
          <tr>
            <td colspan="5" class="text-center text-muted">
              Belum ada data audit
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

  </div>
</div>

<?= $this->endSection() ?>