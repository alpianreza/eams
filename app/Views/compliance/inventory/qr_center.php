<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">

  <!-- HEADER -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">QR Center</h4>
  </div>

  <!-- =====================
  ALBUM GRID
  ===================== -->
  <div id="albumContainer">

    <div class="row g-3">

      <?php foreach ($albums as $itemName => $album): ?>

        <div class="col-xl-2 col-lg-3 col-md-4 col-6">

          <div class="qr-album"
            data-name="<?= esc($itemName) ?>">

            <img src="<?= base_url('uploads/qr/' . $album['cover']) . '?v=' . time() ?>" class="img-fluid">

            <div class="album-meta">
              <div class="fw-bold small"><?= esc($itemName) ?></div>
              <div class="text-muted small"><?= $album['count'] ?> QR</div>
            </div>

          </div>

        </div>

      <?php endforeach; ?>

    </div>

  </div>

  <!-- =====================
  ISI ALBUM (AJAX)
  ===================== -->
  <div id="albumContent" style="display:none">

    <button class="btn btn-sm btn-outline-secondary mb-3" onclick="backAlbum()">
      ← Kembali
    </button>

    <div id="albumAjax"></div>

  </div>

</div>

<!-- =====================
STYLE
===================== -->
<style>
  .qr-album {
    cursor: pointer;
    border-radius: 14px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 2px 6px rgba(0, 0, 0, .1);
    transition: .15s;
  }

  .qr-album:hover {
    transform: translateY(-3px);
  }

  .album-meta {
    padding: 8px;
    text-align: center;
  }

  .qr-card {
    background: #fff;
    border-radius: 12px;
    padding: 6px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, .08);
  }
</style>

<!-- =====================
SCRIPT AJAX NAVIGATION
===================== -->
<script>
  document.addEventListener("DOMContentLoaded", function() {

    document.querySelectorAll('.qr-album').forEach(card => {

      card.addEventListener('click', () => {

        const name = card.dataset.name;

        fetch('<?= base_url("compliance/inventory/qr-album") ?>/' + encodeURIComponent(name))
          .then(r => r.text())
          .then(html => {

            document.getElementById('albumAjax').innerHTML = html;

            document.getElementById('albumContainer').style.display = 'none';
            document.getElementById('albumContent').style.display = 'block';

            window.scrollTo({
              top: 0,
              behavior: 'smooth'
            });

          });

      });

    });

  });

  function backAlbum() {
    document.getElementById('albumContent').style.display = 'none';
    document.getElementById('albumContainer').style.display = 'block';
  }

  function downloadAlbum(name) {

    Swal.fire({
      title: 'Download album?',
      text: 'Semua QR akan diunduh sebagai ZIP',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Download'
    }).then(r => {
      if (!r.isConfirmed) return;

      window.location = '<?= base_url("compliance/inventory/qr-album-download") ?>/' + encodeURIComponent(name);
    });

  }

  function regenAlbum(name) {

    Swal.fire({
      title: 'Regenerate QR?',
      text: 'Semua QR di album ini akan dibuat ulang',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, regenerate',
      cancelButtonText: 'Batal'
    }).then(result => {

      if (!result.isConfirmed) return;

      Swal.fire({
        title: 'Memproses...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
      });

      fetch('<?= base_url("compliance/inventory/qr-album-regen") ?>/' + encodeURIComponent(name))
        .then(r => r.json())
        .then(res => {

          if (res.status) {

            Swal.fire({
              icon: 'success',
              title: 'Berhasil',
              text: res.message,
              timer: 1500,
              showConfirmButton: false
            });

            // reload album
            fetch('<?= base_url("compliance/inventory/qr-album") ?>/' + encodeURIComponent(name))
              .then(r => r.text())
              .then(html => {
                document.getElementById('albumAjax').innerHTML = html;
              });

          } else {

            Swal.fire({
              icon: 'error',
              title: 'Gagal',
              text: 'Regenerate gagal'
            });

          }

        }).catch(() => {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Terjadi kesalahan'
          });
        });

    });

  }

  function printAlbum(name) {
    window.open(
      '<?= base_url("compliance/inventory/qr-album-print") ?>/' + encodeURIComponent(name),
      '_blank'
    );
  }
</script>

<?= $this->endSection() ?>