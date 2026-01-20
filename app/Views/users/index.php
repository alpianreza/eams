<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<a href="<?= base_url('users/create') ?>"
  class="btn btn-primary mb-3">
  + Tambah User
</a>

<table class="table table-bordered">
  <thead>
    <tr>
      <th>No</th>
      <th>Nama</th>
      <th>Username</th>
      <th>Role</th>
      <th>Permission</th>
      <th>Status</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php $i = 1;
    foreach ($users as $u): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><?= esc($u['name']) ?></td>
        <td><?= esc($u['username']) ?></td>
        <td><?= esc($u['role']) ?></td>
        <td><?= esc($u['permission']) ?></td>
        <td><?= esc($u['status']) ?></td>
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
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>

<?= $this->endSection() ?>