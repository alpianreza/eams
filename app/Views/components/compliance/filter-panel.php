<section class="console-filter-panel" aria-label="Filter data">
  <div class="console-filter-panel__bar">
    <span class="console-filter-panel__label"><i class="bi bi-sliders" aria-hidden="true"></i> Filter tampilan</span>
    <?php if (!empty($hint)): ?>
      <span class="console-filter-panel__hint"><?= esc($hint) ?></span>
    <?php endif; ?>
  </div>
  <div class="console-filter-panel__body">
    <?= $fields ?>
  </div>
</section>
