<section class="console-page-header" aria-labelledby="consolePageTitle">
  <div class="console-page-header__identity">
    <?php if (!empty($backUrl)): ?>
      <a href="<?= esc($backUrl) ?>" class="console-back-button" aria-label="Kembali">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
      </a>
    <?php endif; ?>
    <div>
      <?php if (!empty($eyebrow)): ?>
        <p class="console-eyebrow mb-1"><?= esc($eyebrow) ?></p>
      <?php endif; ?>
      <h1 id="consolePageTitle" class="console-page-title mb-1"><?= esc($title) ?></h1>
      <?php if (!empty($summary)): ?>
        <p class="console-page-summary mb-0"><?= esc($summary) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($actions)): ?>
    <div class="console-page-header__actions">
      <?= $actions ?>
    </div>
  <?php endif; ?>
</section>
