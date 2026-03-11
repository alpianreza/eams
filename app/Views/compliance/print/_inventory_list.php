<h6 class="mt-3">Pilih Inventory</h6>

<?php foreach ($inventories as $inv): ?>

  <div class="form-check">

    <input
      class="form-check-input inventoryCheck"
      type="checkbox"
      value="<?= $inv['id'] ?>">

    <label class="form-check-label">

      <?= $inv['asset_code'] ?> — <?= $inv['specific_area'] ?>

    </label>

  </div>

<?php endforeach; ?>