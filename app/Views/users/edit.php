<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php $roles = $roles ?? []; ?>

<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Edit User</h5>
    <a href="<?= base_url('users') ?>" class="btn btn-sm btn-secondary">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>
  </div>

  <div class="card-body">
    <form method="post" action="<?= base_url('users/update/' . $user['id']) ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="row g-3">
        <div class="col-md-6 text-center">
          <?php
          $photoUrl = !empty($user['photo'])
            ? base_url('uploads/users/' . $user['photo'])
            : 'https://ui-avatars.com/api/?name=' . urlencode((string) ($user['name'] ?? 'User'));
          ?>
          <img
            id="photoPreview"
            src="<?= esc($photoUrl) ?>"
            class="rounded-circle shadow-sm mb-3"
            width="120"
            height="120"
            style="object-fit: cover;">
          <input type="file" name="photo" id="photoInput" class="form-control" accept="image/*">
          <small class="text-muted">Maksimal 2MB (JPG, PNG, WEBP)</small>
        </div>

        <div class="col-md-6">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Nama</label>
              <input name="name" value="<?= esc($user['name']) ?>" class="form-control" required>
            </div>

            <div class="col-12">
              <label class="form-label">Username</label>
              <input name="username" value="<?= esc($user['username']) ?>" class="form-control" required>
            </div>

            <div class="col-12">
              <label class="form-label">Password (opsional)</label>
              <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengganti">
            </div>

            <div class="col-12">
              <label class="form-label">No WhatsApp</label>
              <input type="text" name="wa_number" class="form-control" placeholder="081234567890" value="<?= esc($user['wa_number'] ?? '') ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label">Role</label>
              <input list="userRoleOptions" name="role" class="form-control" value="<?= esc($user['role']) ?>" required>
              <datalist id="userRoleOptions">
                <?php foreach ($roles as $role): ?>
                  <option value="<?= esc($role['name']) ?>"><?= esc($role['label']) ?></option>
                <?php endforeach; ?>
              </datalist>
            </div>

            <div class="col-md-6">
              <label class="form-label">Permission</label>
              <select name="permission" class="form-select">
                <option value="read" <?= $user['permission'] == 'read' ? 'selected' : '' ?>>Read Only</option>
                <option value="write" <?= $user['permission'] == 'write' ? 'selected' : '' ?>>Read & Write</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="active" <?= $user['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $user['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-4 d-flex justify-content-end gap-2">
        <a href="<?= base_url('users') ?>" class="btn btn-outline-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-save"></i> Update User
        </button>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('photoInput');
    const preview = document.getElementById('photoPreview');
    if (!input || !preview) return;
    input.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = function(event) {
        preview.src = event.target.result;
      };
      reader.readAsDataURL(file);
    });
  });
</script>
<?= $this->endSection() ?>
