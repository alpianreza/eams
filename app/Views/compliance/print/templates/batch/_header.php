<?php
$logoPath = FCPATH . 'assets/images/company/logo.png';
$logoUrl = is_file($logoPath)
  ? (parse_url(base_url('assets/images/company/logo.png'), PHP_URL_PATH) ?: '/assets/images/company/logo.png')
  : '';

$company = $layout['company'] ?? [];
$signatures = ['Diperiksa oleh', 'Dimonitor oleh', 'Mengetahui'];
$period = $layout['period'] ?? ['label' => 'HARI / TANGGAL', 'value' => ''];
?>

<table class="batch-header-table">
  <tr>
    <td class="batch-company-cell">
      <div class="batch-company-brand">
        <?php if ($logoUrl !== ''): ?>
          <img src="<?= esc($logoUrl) ?>" alt="Logo perusahaan" class="batch-company-logo">
        <?php endif; ?>

        <div>
          <div class="batch-company-name"><?= esc($company['name'] ?? 'PT. YOUNGHYUN STAR') ?></div>

          <?php foreach (($company['address'] ?? []) as $line): ?>
            <div class="batch-company-line"><?= esc($line) ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    </td>

    <td class="batch-title-cell">
      <div class="batch-title-main"><?= esc($layout['headerTitle'] ?? 'CHECKLIST') ?></div>
      <div class="batch-title-sub"><?= esc($layout['headerSubtitle'] ?? '') ?></div>
    </td>

    <?php foreach ($signatures as $signatureLabel): ?>
      <td class="batch-sign-cell">
        <div class="batch-sign-label"><?= esc($signatureLabel) ?>,</div>
        <div class="batch-sign-line"></div>
      </td>
    <?php endforeach; ?>
  </tr>

  <tr>
    <td colspan="<?= 2 + count($signatures) ?>" class="batch-date-cell">
      <?= esc($period['label'] ?? 'HARI / TANGGAL') ?> :
      <?php if (!empty($period['value'])): ?>
        <span style="font-weight:600;"><?= esc($period['value']) ?></span>
      <?php endif; ?>
    </td>
  </tr>
</table>
