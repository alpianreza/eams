<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Edit User</h5>
    <a href="<?= base_url('users') ?>" class="btn btn-sm btn-secondary">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>
  </div>

  <div class="card-body">

    <form method="post"
      action="<?= base_url('users/update/' . $user['id']) ?>"
      enctype="multipart/form-data">

      <?= csrf_field() ?>

      <!-- FOTO PREVIEW -->
      <div class="mb-3 text-center">
        <?php
        $photoUrl = !empty($user['photo'])
          ? base_url('uploads/users/' . $user['photo'])
          : 'https://ui-avatars.com/api/?name=' . urlencode($user['name']);
        ?>
        <img id="photoPreview"
          src="<?= $photoUrl ?>"
          class="rounded-circle shadow"
          width="120"
          height="120"
          style="object-fit: cover;">
      </div>

      <div class="mb-3">
        <label class="form-label">Foto</label>
        <input type="file"
          name="photo"
          id="photoInput"
          class="form-control"
          accept="image/*">
        <small class="text-muted">Maksimal 2MB (JPG, PNG, WEBP)</small>
      </div>

      <div class="mb-3">
        <label class="form-label">Nama</label>
        <input name="name"
          value="<?= esc($user['name']) ?>"
          class="form-control"
          required>
      </div>

      <div class="mb-3">
        <label class="form-label">Username</label>
        <input name="username"
          value="<?= esc($user['username']) ?>"
          class="form-control"
          required>
      </div>

      <div class="mb-3">
        <label class="form-label">Password (opsional)</label>
        <input type="password"
          name="password"
          class="form-control"
          placeholder="Kosongkan jika tidak ingin mengganti">
      </div>

      <div class="col-md-4 mb-3">
        <label class="form-label">Role</label>
        <select name="role" class="form-select">
          <option value="staff" <?= $user['role'] == 'staff' ? 'selected' : '' ?>>Staff</option>
          <option value="compliance" <?= $user['role'] == 'compliance' ? 'selected' : '' ?>>Compliance</option>
          <option value="auditor" <?= $user['role'] == 'auditor' ? 'selected' : '' ?>>Auditor</option>
          <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
      </div>


      <div class="col-md-4 mb-3">
        <label class="form-label">Permission</label>
        <select name="permission" class="form-select">
          <option value="read" <?= $user['permission'] == 'read' ? 'selected' : '' ?>>Read Only</option>
          <option value="write" <?= $user['permission'] == 'write' ? 'selected' : '' ?>>Read & Write</option>
        </select>
      </div>

      <div class="col-md-4 mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="active" <?= $user['status'] == 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= $user['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
  </div>

  <div class="text-end">
    <button type="submit" class="btn btn-primary">
      <i class="bi bi-save"></i> Update User
    </button>
  </div>

  </form>
</div>
</div>
<!-- Modal Crop -->
<div class="modal fade" id="cropModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Crop Foto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div style="width:100%; height:400px;">
          <img id="cropImage" style="max-width:100%; display:block;">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="saveCrop">Simpan</button>
      </div>
    </div>
  </div>
</div>


<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<script>
  document.getElementById('photoInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(event) {
      document.getElementById('photoPreview').src = event.target.result;
    };
    reader.readAsDataURL(file);
  });
</script>

<script>
  let cropper;
  const photoInput = document.getElementById('photoInput');
  const cropImage = document.getElementById('cropImage');
  const cropModal = new bootstrap.Modal(document.getElementById('cropModal'));
  const photoPreview = document.getElementById('photoPreview');

  photoInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(event) {
      cropImage.src = event.target.result;
      cropModal.show();

      cropper = new Cropper(cropImage, {
        aspectRatio: 1,
        viewMode: 1
      });
    };
    reader.readAsDataURL(file);
  });

  document.getElementById('saveCrop').addEventListener('click', function() {

    const canvas = cropper.getCroppedCanvas({
      width: 300,
      height: 300
    });

    canvas.toBlob(function(blob) {
      const fileInput = document.getElementById('photoInput');
      const dataTransfer = new DataTransfer();

      const croppedFile = new File([blob], "cropped.jpg", {
        type: "image/jpeg"
      });

      dataTransfer.items.add(croppedFile);
      fileInput.files = dataTransfer.files;

      photoPreview.src = URL.createObjectURL(blob);

      cropModal.hide();
      cropper.destroy();
    }, 'image/jpeg', 0.9);
  });
</script>

<?= $this->endSection() ?>