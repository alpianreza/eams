<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
  <div class="col-md-6">

    <div class="card shadow-sm border-0">
      <div class="card-body">

        <h5 class="mb-4">Ganti Password</h5>

        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger">
            <?= session()->getFlashdata('error') ?>
          </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
          <div class="alert alert-success">
            <?= session()->getFlashdata('success') ?>
          </div>
        <?php endif; ?>

        <form method="post"
          action="<?= base_url('settings/change-password') ?>">

          <?= csrf_field() ?>

          <div class="mb-3">
            <label>Password Lama</label>
            <input type="password"
              name="old_password"
              class="form-control"
              required>
          </div>

          <div class="mb-3">
            <label>Password Baru</label>
            <input type="password"
              name="new_password"
              class="form-control"
              required>
          </div>

          <div class="mb-3">
            <label>Konfirmasi Password Baru</label>
            <input type="password"
              name="confirm_password"
              class="form-control"
              required>
          </div>

          <button class="btn btn-primary">
            Simpan Perubahan
          </button>

        </form>

      </div>
    </div>

  </div>
</div>

<?= $this->endSection() ?>