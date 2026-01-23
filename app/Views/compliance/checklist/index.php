<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<a href="<?= base_url('compliance/inventory/detail/' . $inventory['id']) ?>"
  class="btn btn-sm btn-secondary mb-3">
  ← Kembali
</a>

<?php $today = date('Y-m-d'); ?>

<!-- ================= KALENDER PERIODE ================= -->
<div class="card mb-4">
  <div class="card-header">
    <strong>Checklist Periode (<?= strtoupper($frequency) ?>)</strong>
  </div>

  <div class="card-body">

    <!-- =================================================
     ================ DAILY CALENDAR ====================
     ================================================= -->
    <?php if ($frequency === 'daily'): ?>
      <?php
      $year  = date('Y');
      $month = date('m');
      $daysInMonth = date('t');
      $firstDayOfMonth = date('w', strtotime("$year-$month-01"));
      $day = 1;
      ?>

      <table class="table table-bordered text-center align-middle">
        <thead class="table-light">
          <tr>
            <th>Min</th>
            <th>Sen</th>
            <th>Sel</th>
            <th>Rab</th>
            <th>Kam</th>
            <th>Jum</th>
            <th>Sab</th>
          </tr>
        </thead>
        <tbody>
          <?php for ($row = 0; $row < 6; $row++): ?>
            <tr>
              <?php for ($col = 0; $col < 7; $col++): ?>
                <td>
                  <?php if (($row === 0 && $col < $firstDayOfMonth) || $day > $daysInMonth): ?>
                    &nbsp;
                  <?php else: ?>
                    <?php
                    $key = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                    ?>

                    <?php if ($key <= $today): ?>
                      <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?period_key=<?= $key ?>"
                        class="btn btn-sm <?= $key === $period_key ? 'btn-primary' : 'btn-outline-primary' ?>">
                        <?= $day ?>
                      </a>
                    <?php else: ?>
                      <span class="text-muted"><?= $day ?></span>
                    <?php endif ?>

                    <?php $day++; ?>
                  <?php endif ?>
                </td>
              <?php endfor ?>
            </tr>
          <?php endfor ?>
        </tbody>
      </table>
    <?php endif ?>

    <!-- =================================================
     ================ WEEKLY CALENDAR ===================
     ================================================= -->
    <?php if ($frequency === 'weekly'): ?>
      <div class="row g-2">
        <?php foreach ($periods as $p): ?>
          <?php
          // format: YYYY-MM-Wn
          preg_match('/^(\d{4})-(\d{2})-W([1-4])$/', $p['period_key'], $m);
          $year  = $m[1];
          $month = $m[2];
          $week  = (int)$m[3];

          $endDay = ($week === 4)
            ? date('t', strtotime("$year-$month-01"))
            : $week * 7;

          $weekEndDate = "$year-$month-" . str_pad($endDay, 2, '0', STR_PAD_LEFT);
          ?>

          <div class="col-6">
            <?php if ($weekEndDate <= $today): ?>
              <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?period_key=<?= $p['period_key'] ?>"
                class="btn w-100 <?= $p['period_key'] === $period_key ? 'btn-primary' : 'btn-outline-primary' ?>">
                <?= esc($p['label']) ?>
              </a>
            <?php else: ?>
              <div class="btn w-100 btn-light disabled">
                <?= esc($p['label']) ?>
              </div>
            <?php endif ?>
          </div>
        <?php endforeach ?>
      </div>
    <?php endif ?>

    <!-- =================================================
     ================ MONTHLY CALENDAR ==================
     ================================================= -->
    <?php if ($frequency === 'monthly'): ?>
      <div class="row g-2">
        <?php foreach ($periods as $p): ?>
          <?php
          $monthEnd = date('Y-m-t', strtotime($p['period_key'] . '-01'));
          ?>
          <div class="col-4">
            <?php if ($monthEnd <= $today): ?>
              <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?period_key=<?= $p['period_key'] ?>"
                class="btn w-100 <?= $p['period_key'] === $period_key ? 'btn-primary' : 'btn-outline-primary' ?>">
                <?= esc($p['label']) ?>
              </a>
            <?php else: ?>
              <div class="btn w-100 btn-light disabled">
                <?= esc($p['label']) ?>
              </div>
            <?php endif ?>
          </div>
        <?php endforeach ?>
      </div>
    <?php endif ?>

  </div>
</div>

<!-- ================= INFO ================= -->
<h5><?= esc($inventory['item_display_name']) ?></h5>

<p class="text-muted mb-4">
  Frekuensi: <strong><?= strtoupper($frequency) ?></strong><br>
  Periode aktif: <strong><?= esc($period_label) ?></strong>
</p>

<?php if ($isLocked): ?>
  <div class="alert alert-info">
    Checklist untuk periode ini sudah diisi dan terkunci.
  </div>
<?php endif ?>

<!-- ================= FORM CHECKLIST ================= -->
<?php if (! $isLocked && ! empty($questions)): ?>

  <form action="<?= base_url('compliance/checklist/submit') ?>"
    method="post"
    enctype="multipart/form-data">

    <?= csrf_field() ?>

    <input type="hidden" name="inventory_id" value="<?= $inventory['id'] ?>">
    <input type="hidden" name="item_type_id" value="<?= $inventory['item_type_id'] ?>">
    <input type="hidden" name="frequency" value="<?= $frequency ?>">
    <input type="hidden" name="period_key" value="<?= $period_key ?>">

    <table class="table table-bordered align-middle">
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

              <div class="form-check form-check-inline">
                <input class="form-check-input status-radio"
                  type="radio"
                  name="questions[<?= $q['id'] ?>]"
                  value="ok"
                  data-qid="<?= $q['id'] ?>"
                  required>
                <label class="form-check-label">✅ OK</label>
              </div>

              <div class="form-check form-check-inline">
                <input class="form-check-input status-radio"
                  type="radio"
                  name="questions[<?= $q['id'] ?>]"
                  value="ng"
                  data-qid="<?= $q['id'] ?>"
                  required>
                <label class="form-check-label">❌ NOT OK</label>
              </div>

              <div class="mt-2 d-none" id="remark-box-<?= $q['id'] ?>">
                <textarea name="remarks[<?= $q['id'] ?>]"
                  class="form-control form-control-sm"
                  placeholder="Wajib diisi jika NOT OK"></textarea>
              </div>

              <div class="mt-2 d-none" id="photo-box-<?= $q['id'] ?>">
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

    <button class="btn btn-success">
      Simpan Checklist
    </button>

  </form>

<?php else: ?>
  <div class="alert alert-warning">
    Tidak ada checklist untuk periode ini.
  </div>
<?php endif ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/checklist.js') ?>"></script>
<?= $this->endSection() ?>