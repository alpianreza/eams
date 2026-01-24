<div class="card-body checklist-content">

  <h5 class="checklist-title"><?= esc($inventory['item_display_name']) ?></h5>

  <p class="text-muted mb-4">
    Frekuensi: <strong><?= strtoupper($frequency) ?></strong><br>
    Periode aktif: <strong><?= esc($period_label) ?></strong>
  </p>

  <?php if ($isLocked): ?>
    <div class="alert alert-info">
      Checklist untuk periode ini sudah diisi dan terkunci.
    </div>
  <?php endif ?>

  <?php if (! $isLocked && ! empty($questions)): ?>

    <form action="<?= base_url('compliance/checklist/submit') ?>"
      method="post"
      enctype="multipart/form-data">

      <?= csrf_field() ?>

      <input type="hidden" name="inventory_id" value="<?= $inventory['id'] ?>">
      <input type="hidden" name="item_type_id" value="<?= $inventory['item_type_id'] ?>">
      <input type="hidden" name="frequency" value="<?= $frequency ?>">
      <input type="hidden" name="period_key" value="<?= $period_key ?>">

      <!-- ACTION BAR -->
      <div class="d-flex justify-content-end mb-3">
        <button type="button" class="btn btn-outline-success btn-sm" id="btn-ok-all">
          ✅ Tandai Semua OK
        </button>
      </div>

      <!-- TABLE RESPONSIVE -->
      <div class="table-responsive">
        <table class="table table-bordered table-checklist align-middle">
          <thead class="table-light">
            <tr>
              <th width="5%">No</th>
              <th>Pertanyaan</th>
              <th width="30%" class="text-center">Status</th>
            </tr>
          </thead>
          <tbody>

            <?php foreach ($questions as $i => $q): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= esc($q['question']) ?></td>
                <td class="text-center">

                  <!-- STATUS BUTTON -->
                  <div class="status-group" role="group">
                    <input type="radio"
                      class="btn-check status-radio"
                      name="questions[<?= $q['id'] ?>]"
                      id="ok-<?= $q['id'] ?>"
                      value="ok"
                      data-qid="<?= $q['id'] ?>"
                      required>
                    <label class="btn btn-outline-success btn-sm" for="ok-<?= $q['id'] ?>">
                      ✅ OK
                    </label>

                    <input type="radio"
                      class="btn-check status-radio"
                      name="questions[<?= $q['id'] ?>]"
                      id="ng-<?= $q['id'] ?>"
                      value="ng"
                      data-qid="<?= $q['id'] ?>"
                      required>
                    <label class="btn btn-outline-danger btn-sm" for="ng-<?= $q['id'] ?>">
                      ❌ NOT OK
                    </label>
                  </div>

                  <!-- NG ALERT -->
                  <div class="alert alert-warning ng-alert d-none" id="ng-alert-<?= $q['id'] ?>">
                    ⚠️ Item ini NOT OK. Mohon isi catatan dan foto.
                  </div>

                  <!-- NG FIELDS -->
                  <div class="ng-fields d-none" id="ng-fields-<?= $q['id'] ?>">
                    <textarea
                      name="remarks[<?= $q['id'] ?>]"
                      class="form-control form-control-sm mb-2"
                      placeholder="Wajib diisi jika NOT OK"></textarea>

                    <input type="file"
                      name="photos[<?= $q['id'] ?>]"
                      class="form-control form-control-sm"
                      accept="image/*">
                  </div>

                </td>
              </tr>
            <?php endforeach ?>

          </tbody>
        </table>
      </div>

      <!-- SUBMIT DESKTOP -->
      <div class="d-none d-md-block mt-3">
        <button class="btn btn-success">
          Simpan Checklist
        </button>
      </div>

      <!-- STICKY SUBMIT MOBILE -->
      <div class="sticky-submit d-md-none">
        <button class="btn btn-success w-100">
          Simpan Checklist
        </button>
      </div>

    </form>

  <?php else: ?>
    <div class="alert alert-warning">
      Tidak ada checklist untuk periode ini.
    </div>
  <?php endif ?>

  <script>
    // OK ALL
    document.getElementById('btn-ok-all')?.addEventListener('click', function() {
      document.querySelectorAll('.status-radio[value="ok"]').forEach(radio => {
        radio.checked = true;
        radio.dispatchEvent(new Event('change'));
      });
    });

    // TOGGLE NG FIELD
    document.querySelectorAll('.status-radio').forEach(radio => {
      radio.addEventListener('change', function() {
        const qid = this.dataset.qid;

        const alertBox = document.getElementById('ng-alert-' + qid);
        const fieldsBox = document.getElementById('ng-fields-' + qid);

        if (this.value === 'ng') {
          alertBox.classList.remove('d-none');
          fieldsBox.classList.remove('d-none');
        } else {
          alertBox.classList.add('d-none');
          fieldsBox.classList.add('d-none');
        }
      });
    });
  </script>

</div>