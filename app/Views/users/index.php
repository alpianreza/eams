<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<a href="<?= base_url('users/create') ?>" class="btn btn-primary mb-3">
  + Tambah User
</a>

<div class="table-responsive">
  <table class="table table-bordered table-striped align-middle">
    <thead class="table-dark">
      <tr>
        <th width="50">No</th>
        <th>Nama</th>
        <th>Username</th>
        <th>WhatsApp</th> <!-- 🔥 TAMBAHAN -->
        <th>Role</th>
        <th>Permission</th>
        <th>Status</th>
        <th width="180">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($users)): ?>
        <?php $i = 1;
        foreach ($users as $u): ?>
          <tr>
            <td><?= $i++ ?></td>

            <td><?= esc($u['name']) ?></td>

            <td><?= esc($u['username']) ?></td>

            <!-- 🔥 WHATSAPP -->
            <td>
              <?php if (!empty($u['wa_number'])): ?>
                <span class="text-success fw-bold">
                  <?= esc($u['wa_number']) ?>
                </span>
              <?php else: ?>
                <span class="text-danger">Belum ada</span>
              <?php endif; ?>
            </td>

            <td><?= esc($u['role']) ?></td>

            <td><?= esc($u['permission']) ?></td>

            <!-- 🔥 STATUS BADGE -->
            <td>
              <?php if ($u['status'] === 'active'): ?>
                <span class="badge bg-success">Aktif</span>
              <?php else: ?>
                <span class="badge bg-secondary">Nonaktif</span>
              <?php endif; ?>
            </td>

            <!-- 🔥 AKSI -->
            <td>
              <a href="<?= base_url('users/edit/' . $u['id']) ?>"
                class="btn btn-sm btn-warning">
                Edit
              </a>

              <?php if ($u['status'] === 'active'): ?>
                <a href="<?= base_url('users/deactivate/' . $u['id']) ?>"
                  class="btn btn-sm btn-danger"
                  onclick="return confirm('Nonaktifkan user ini?')">
                  Nonaktifkan
                </a>
              <?php else: ?>
                <span class="text-muted small">-</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach ?>
      <?php else: ?>
        <tr>
          <td colspan="8" class="text-center text-muted">
            Tidak ada data user
          </td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?= $this->endSection() ?>