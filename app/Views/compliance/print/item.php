<div class="card">
  <div class="card-body">

    <h5>Print Per Inventory</h5>

    <div class="form-group">

      <label>Item Type</label>

      <select id="itemTypeSelect" class="form-control">

        <option value="">-- pilih item --</option>

        <?php foreach ($itemTypes as $it): ?>

          <option
            value="<?= $it['id'] ?>"
            data-frequency="<?= $it['checklist_frequency'] ?>">
            <?= $it['name'] ?>
          </option>

        <?php endforeach; ?>

      </select>

    </div>


    <div id="inventoryContainer"></div>

    <div id="periodContainer"></div>

  </div>
</div>
