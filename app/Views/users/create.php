<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$roles = $roles ?? [];
$roleValue = old('role', 'staff');
?>

<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Tambah User</h5>
    <a href="<?= base_url('users') ?>" class="btn btn-sm btn-secondary">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>
  </div>

  <div class="card-body">
    <form method="post" action="<?= base_url('users/store') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nama</label>
          <input name="name" class="form-control" value="<?= esc(old('name')) ?>" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Username</label>
          <input name="username" class="form-control" value="<?= esc(old('username')) ?>" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">No WhatsApp</label>
          <input type="text"
            name="wa_number"
            class="form-control"
            placeholder="081234567890"
            value="<?= esc(old('wa_number')) ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label">Role</label>
          <input list="userRoleOptions" name="role" class="form-control" value="<?= esc($roleValue) ?>" required>
          <datalist id="userRoleOptions">
            <?php foreach ($roles as $role): ?>
              <option value="<?= esc($role['name']) ?>"><?= esc($role['label']) ?></option>
            <?php endforeach; ?>
          </datalist>
          <small class="text-muted">Bisa pilih role yang sudah ada atau ketik role baru.</small>
        </div>

        <div class="col-md-6">
          <label class="form-label">Permission</label>
          <select name="permission" class="form-select">
            <option value="read" <?= old('permission') === 'read' ? 'selected' : '' ?>>Read Only</option>
            <option value="write" <?= old('permission', 'read') === 'write' ? 'selected' : '' ?>>Read & Write</option>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Foto</label>
          <input type="file" name="photo" class="form-control" accept="image/*">
        </div>
      </div>

      <div class="mt-4 d-flex justify-content-end gap-2">
        <a href="<?= base_url('users') ?>" class="btn btn-outline-secondary">Batal</a>
        <button class="btn btn-success">Simpan</button>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
