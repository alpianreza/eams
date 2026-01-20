<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<form method="post" action="<?= base_url('users/update/' . $user['id']) ?>">
  <?= csrf_field() ?>

  <div class="mb-3">
    <label>Nama</label>
    <input name="name" value="<?= esc($user['name']) ?>" class="form-control">
  </div>

  <div class="mb-3">
    <label>Username</label>
    <input name="username" value="<?= esc($user['username']) ?>" class="form-control">
  </div>

  <div class="mb-3">
    <label>Password (opsional)</label>
    <input type="password" name="password" class="form-control">
  </div>

  <div class="mb-3">
    <label>Role</label>
    <select name="role" class="form-select">
      <option value="staff" <?= $user['role'] == 'staff' ? 'selected' : '' ?>>Staff</option>
      <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
    </select>
  </div>

  <div class="mb-3">
    <label>Permission</label>
    <select name="permission" class="form-select">
      <option value="read" <?= $user['permission'] == 'read' ? 'selected' : '' ?>>Read Only</option>
      <option value="write" <?= $user['permission'] == 'write' ? 'selected' : '' ?>>Read & Write</option>
    </select>
  </div>

  <div class="mb-3">
    <label>Status</label>
    <select name="status" class="form-select">
      <option value="active" <?= $user['status'] == 'active' ? 'selected' : '' ?>>Active</option>
      <option value="inactive" <?= $user['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
    </select>
  </div>

  <button class="btn btn-primary">Update</button>

  <?= $this->endSection() ?>