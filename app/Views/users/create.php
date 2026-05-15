<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$roles = $roles ?? [];
$roleValue = old('role', 'staff');
$accessGroups = $accessGroups ?? [];
$selectedPageAccess = old('page_access');
$selectedPageAccess = is_array($selectedPageAccess)
  ? $selectedPageAccess
  : array_keys(access_menu_catalog());
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

        <div class="col-12">
          <div class="border rounded-3 p-3 bg-light-subtle">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
              <div>
                <label class="form-label fw-semibold mb-1">Halaman Yang Ditampilkan</label>
                <div class="text-muted small">Centang halaman yang boleh tampil dan diakses oleh user ini.</div>
              </div>
            </div>

            <div class="row g-3">
              <?php foreach ($accessGroups as $groupName => $items): ?>
                <div class="col-md-6 col-xl-4">
                  <div class="border rounded-3 p-3 h-100 bg-white">
                    <div class="fw-semibold mb-2"><?= esc($groupName) ?></div>
                    <?php foreach ($items as $key => $item): ?>
                      <div class="form-check mb-2">
                        <input
                          class="form-check-input"
                          type="checkbox"
                          name="page_access[]"
                          value="<?= esc($key) ?>"
                          id="page_access_<?= esc($key) ?>"
                          <?= in_array($key, $selectedPageAccess, true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="page_access_<?= esc($key) ?>">
                          <?= esc($item['label'] ?? $key) ?>
                        </label>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
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
