<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<form method="post" action="<?= base_url('users/store') ?>">
  <?= csrf_field() ?>

  <div class="mb-3">
    <label>Nama</label>
    <input name="name" class="form-control" required>
  </div>

  <div class="mb-3">
    <label>Username</label>
    <input name="username" class="form-control" required>
  </div>

  <div class="mb-3">
    <label>Password</label>
    <input type="password" name="password" class="form-control" required>
  </div>

  <div class="mb-3">
    <label>Role</label>
    <select name="role" class="form-select">
      <option value="staff">Staff</option>
      <option value="admin">Admin</option>
    </select>
  </div>

  <div class="mb-3">
    <label>Permission</label>
    <select name="permission" class="form-select">
      <option value="read">Read Only</option>
      <option value="write">Read & Write</option>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">No WhatsApp</label>
    <input type="text"
      name="wa_number"
      class="form-control"
      placeholder="081234567890"
      value="<?= esc($user['wa_number'] ?? '') ?>">
  </div>

  <button class="btn btn-success">Simpan</button>

  <?= $this->endSection() ?>