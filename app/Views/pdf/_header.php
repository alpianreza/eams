<div class="pdf-header">
  <table class="header-table">
    <tr>
      <td class="header-left">
        <table>
          <tr>
            <td style="width:70px">
              <img
                src="<?= FCPATH ?>assets/images/company/logo.png"
                style="height:55px">
            </td>
            <td>
              <div class="company-name">PT YOUNGHYUN STAR</div>
              <div class="company-address">
                Kmp. Kebon Randu RT.01/04<br>
                Ds. Sekarwangi Kec. Cibadak Kab. Sukabumi
              </div>
            </td>
          </tr>
        </table>
      </td>

      <td class="header-right">
        <div class="report-title"><?= esc($title) ?></div>
        <?php if (!empty($subtitle ?? null)): ?>
          <div class="report-subtitle">(<?= esc($subtitle) ?>)</div>
        <?php endif; ?>
      </td>
    </tr>
  </table>

  <table class="meta">
    <?php if (!empty($itemName ?? null)): ?>
      <tr>
        <td>Item</td>
        <td>:</td>
        <td><?= esc($itemName) ?></td>
      </tr>
    <?php endif; ?>

    <?php if (!empty($inventoryNo ?? null)): ?>
      <tr>
        <td>No Inventaris</td>
        <td>:</td>
        <td><?= esc($inventoryNo) ?></td>
      </tr>
    <?php endif; ?>

    <?php if (!empty($location ?? null)): ?>
      <tr>
        <td>Lokasi</td>
        <td>:</td>
        <td><?= esc($location) ?></td>
      </tr>
    <?php endif; ?>

    <tr>
      <td>Periode</td>
      <td>:</td>
      <td><?= esc($periodLabel) ?></td>
    </tr>
  </table>
</div>