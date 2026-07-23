<div class="console-section-heading">
  <div>
    <?php if (!empty($eyebrow)): ?>
      <div class="console-section-heading__eyebrow"><?= esc($eyebrow) ?></div>
    <?php endif; ?>
    <h2 class="console-section-heading__title"><?= esc($title) ?></h2>
  </div>
  <?php if (!empty($meta)): ?>
    <div class="console-section-heading__meta"><?= $meta ?></div>
  <?php endif; ?>
</div>
