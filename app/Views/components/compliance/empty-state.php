<div class="text-center p-5 border border-dashed border-slate-200 rounded-3 bg-slate-50">
  <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white border border-slate-100 text-sky-600 mb-3" style="width: 46px; height: 46px;">
    <i class="bi <?= esc($icon ?? 'bi-inbox') ?> fs-5"></i>
  </div>
  <h6 class="fw-bold text-slate-800 mb-1"><?= esc($title ?? 'Belum ada data') ?></h6>
  <?php if (!empty($description)): ?>
    <p class="text-sm text-slate-500 mb-0"><?= esc($description) ?></p>
  <?php endif; ?>
</div>
