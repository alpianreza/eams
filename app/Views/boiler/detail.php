<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">
      Boiler Fuel Log - <?= date('d M Y', strtotime($date)) ?>
    </h3>
  </div>

  <div class="card-body">

    <?php if ($isSunday || $isHoliday): ?>
      <div class="alert alert-danger">
        Hari Libur / Minggu
      </div>
    <?php endif; ?>

    <!-- ========================= -->
    <!-- DESKTOP TABLE -->
    <!-- ========================= -->
    <div class="table-responsive d-none d-md-block">
      <table class="table table-bordered table-sm" id="logTable">
        <thead>
          <tr>
            <th width="120">Jam</th>
            <th width="120">Polybag</th>
            <th width="150">KG</th>
            <th>Keterangan</th>
            <th width="80">Aksi</th>
          </tr>
        </thead>
        <tbody>

          <?php foreach ($logs as $row): ?>
            <tr data-id="<?= $row['id'] ?>">
              <td>
                <input type="time" class="form-control form-control-sm time"
                  value="<?= $row['log_time'] ?>">
              </td>
              <td>
                <input type="number" class="form-control form-control-sm polybag"
                  value="<?= $row['polybag'] ?>">
              </td>
              <td>
                <input type="number" step="0.01"
                  class="form-control form-control-sm kg"
                  value="<?= $row['kg'] ?>">
              </td>
              <td>
                <input type="text" class="form-control form-control-sm note"
                  value="<?= $row['note'] ?>">
              </td>
              <td>
                <button class="btn btn-danger btn-sm deleteRow">X</button>
              </td>
            </tr>
          <?php endforeach; ?>

        </tbody>
      </table>

      <button class="btn btn-primary btn-sm mt-2" id="addRow">
        + Tambah Baris
      </button>
    </div>


    <!-- ========================= -->
    <!-- MOBILE VERSION -->
    <!-- ========================= -->
    <div class="d-block d-md-none" id="mobileLog">

      <?php foreach ($logs as $row): ?>
        <div class="card mb-3 shadow-sm log-card" data-id="<?= $row['id'] ?>">
          <div class="card-body p-3">

            <div class="form-group">
              <label class="small font-weight-bold">Jam</label>
              <input type="time"
                class="form-control form-control-lg time"
                value="<?= $row['log_time'] ?>">
            </div>

            <div class="form-group">
              <label class="small font-weight-bold">Polybag</label>
              <input type="number"
                class="form-control form-control-lg polybag"
                value="<?= $row['polybag'] ?>">
            </div>

            <div class="form-group">
              <label class="small font-weight-bold">KG</label>
              <input type="number"
                step="0.01"
                class="form-control form-control-lg kg"
                value="<?= $row['kg'] ?>">
            </div>

            <div class="form-group">
              <label class="small font-weight-bold">Keterangan</label>
              <input type="text"
                class="form-control note"
                value="<?= $row['note'] ?>">
            </div>

            <button class="btn btn-danger btn-block deleteRow">
              Hapus
            </button>

          </div>
        </div>
      <?php endforeach; ?>

      <button class="btn btn-primary btn-lg btn-block mb-3" id="addMobileRow">
        + Tambah Baris
      </button>

    </div>

    <hr>

    <h5>Total Harian:</h5>
    <div>
      Polybag: <span id="totalPoly">0</span><br>
      KG: <span id="totalKg">0.00</span>
    </div>

  </div>
</div>

<script>
  let date = "<?= $date ?>";

  function calculateTotal() {
    let totalPoly = 0;
    let totalKg = 0;

    document.querySelectorAll('.polybag').forEach(el => {
      totalPoly += parseFloat(el.value) || 0;
    });

    document.querySelectorAll('.kg').forEach(el => {
      totalKg += parseFloat(el.value) || 0;
    });

    document.getElementById('totalPoly').innerText = totalPoly;
    document.getElementById('totalKg').innerText = totalKg.toFixed(2);
  }

  calculateTotal();

  // AUTO SAVE (desktop + mobile)
  document.addEventListener('change', function(e) {

    let container = e.target.closest('tr') || e.target.closest('.log-card');
    if (!container) return;

    let id = container.dataset.id || '';

    let time = container.querySelector('.time')?.value;
    let polybag = container.querySelector('.polybag')?.value;
    let kg = container.querySelector('.kg')?.value;
    let note = container.querySelector('.note')?.value;

    if (!time) return;

    fetch("/boiler/save", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          "X-Requested-With": "XMLHttpRequest"
        },
        body: new URLSearchParams({
          id: id,
          date: date,
          time: time,
          polybag: polybag,
          kg: kg,
          note: note
        })
      })
      .then(res => res.json())
      .then(data => {
        if (!id) {
          container.dataset.id = data.id;
        }
        calculateTotal();
      });
  });

  // ADD DESKTOP ROW
  document.getElementById('addRow')?.addEventListener('click', function() {
    let tr = document.createElement('tr');
    tr.innerHTML = `
    <td><input type="time" class="form-control form-control-sm time"></td>
    <td><input type="number" class="form-control form-control-sm polybag"></td>
    <td><input type="number" step="0.01" class="form-control form-control-sm kg"></td>
    <td><input type="text" class="form-control form-control-sm note"></td>
    <td><button class="btn btn-danger btn-sm deleteRow">X</button></td>
  `;
    document.querySelector('#logTable tbody').appendChild(tr);
  });

  // ADD MOBILE ROW
  document.getElementById('addMobileRow')?.addEventListener('click', function() {
    let container = document.getElementById('mobileLog');

    let div = document.createElement('div');
    div.className = "card mb-3 shadow-sm log-card";

    div.innerHTML = `
    <div class="card-body p-3">
      <div class="form-group">
        <label class="small font-weight-bold">Jam</label>
        <input type="time" class="form-control form-control-lg time">
      </div>
      <div class="form-group">
        <label class="small font-weight-bold">Polybag</label>
        <input type="number" class="form-control form-control-lg polybag">
      </div>
      <div class="form-group">
        <label class="small font-weight-bold">KG</label>
        <input type="number" step="0.01" class="form-control form-control-lg kg">
      </div>
      <div class="form-group">
        <label class="small font-weight-bold">Keterangan</label>
        <input type="text" class="form-control note">
      </div>
      <button class="btn btn-danger btn-block deleteRow">Hapus</button>
    </div>
  `;

    container.insertBefore(div, this);
  });

  // DELETE (desktop + mobile)
  document.addEventListener('click', function(e) {

    if (!e.target.classList.contains('deleteRow')) return;

    let container = e.target.closest('tr') || e.target.closest('.log-card');
    let id = container.dataset.id;

    if (!id) {
      container.remove();
      calculateTotal();
      return;
    }

    fetch("/boiler/delete", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          "X-Requested-With": "XMLHttpRequest"
        },
        body: new URLSearchParams({
          id: id
        })
      })
      .then(res => res.json())
      .then(() => {
        container.remove();
        calculateTotal();
      });

  });
</script>

<?= $this->endSection() ?>