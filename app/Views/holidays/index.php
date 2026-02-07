<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h5 class="mb-3">Kelola Hari Libur Tahun <?= esc($year) ?></h5>

<form method="get" class="mb-3">
  <input type="number" name="year"
    value="<?= esc($year) ?>"
    class="form-control w-auto d-inline">
  <button class="btn btn-sm btn-primary">Filter</button>
</form>

<!-- Form Tambah -->
<form method="post"
  action="<?= site_url('holidays/store') ?>"
  class="row g-2 mb-4">
  <?= csrf_field() ?>

  <div class="col-md-3">
    <input type="date"
      name="holiday_date"
      class="form-control"
      required>
  </div>

  <div class="col-md-5">
    <input type="text"
      name="description"
      class="form-control"
      placeholder="Keterangan"
      required>
  </div>

  <div class="col-md-2">
    <button class="btn btn-success w-100">
      Tambah
    </button>
  </div>
</form>

<table class="table table-bordered">
  <thead>
    <tr>
      <th>Tanggal</th>
      <th>Keterangan</th>
      <th width="120">Aksi</th>
    </tr>
  </thead>
  <tbody>

    <?php if (empty($holidays)): ?>
      <tr>
        <td colspan="3" class="text-center text-muted">
          Belum ada data
        </td>
      </tr>
    <?php endif; ?>

    <?php foreach ($holidays as $h): ?>
      <tr>
        <td><?= esc($h['holiday_date']) ?></td>
        <td><?= esc($h['description']) ?></td>
        <td>

          <!-- Tombol Edit -->
          <button class="btn btn-sm btn-warning"
            data-bs-toggle="modal"
            data-bs-target="#editModal<?= $h['id'] ?>">
            Edit
          </button>

          <!-- Tombol Hapus -->
          <form method="post"
            action="<?= site_url('holidays/delete/' . $h['id']) ?>"
            class="form-delete"
            style="display:inline;">

            <?= csrf_field() ?>

            <button type="button"
              class="btn btn-sm btn-danger btn-delete"
              data-name="<?= esc($h['description']) ?>">
              Hapus
            </button>

          </form>

        </td>

      </tr>

      <div class="modal fade" id="editModal<?= $h['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">

            <form method="post"
              action="<?= site_url('holidays/update/' . $h['id']) ?>">

              <?= csrf_field() ?>

              <div class="modal-header">
                <h5 class="modal-title">Edit Hari Libur</h5>
                <button type="button"
                  class="btn-close"
                  data-bs-dismiss="modal"></button>
              </div>

              <div class="modal-body">

                <div class="mb-3">
                  <label>Tanggal</label>
                  <input type="date"
                    name="holiday_date"
                    class="form-control"
                    value="<?= esc($h['holiday_date']) ?>"
                    required>
                </div>

                <div class="mb-3">
                  <label>Keterangan</label>
                  <input type="text"
                    name="description"
                    class="form-control"
                    value="<?= esc($h['description']) ?>"
                    required>
                </div>

              </div>

              <div class="modal-footer">
                <button type="button"
                  class="btn btn-secondary"
                  data-bs-dismiss="modal">
                  Batal
                </button>

                <button class="btn btn-primary">
                  Simpan
                </button>
              </div>

            </form>

          </div>
        </div>
      </div>

    <?php endforeach; ?>

  </tbody>
</table>

<script>
  document.querySelectorAll('.btn-delete').forEach(function(button) {

    button.addEventListener('click', function() {

      const form = this.closest('.form-delete');
      const holidayName = this.getAttribute('data-name');

      Swal.fire({
        title: 'Yakin hapus?',
        html: `Hari libur <b>${holidayName}</b> akan dihapus.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
      }).then((result) => {

        if (result.isConfirmed) {
          form.submit();
        }

      });

    });

  });
</script>

<?= $this->endSection() ?>