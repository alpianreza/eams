<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Assign Asset</h4>

<p><strong>Asset:</strong> <?= esc($asset['asset_name']) ?></p>

<form method="post" action="<?= base_url('it-assets/assign/'.$asset['id']) ?>">
    <div class="mb-3">
        <label>Karyawan</label>
        <select name="employee_id" class="form-select" required>
            <option value="">-- pilih karyawan --</option>
            <?php foreach ($employees as $e): ?>
                <option value="<?= $e['id'] ?>">
                    <?= esc($e['name']) ?> (<?= esc($e['employee_id']) ?>)
                </option>
            <?php endforeach ?>
        </select>
    </div>

    <button class="btn btn-primary">Assign</button>
    <a href="<?= base_url('it-assets/detail/'.$asset['id']) ?>"
       class="btn btn-secondary">Batal</a>
</form>

<?= $this->endSection() ?>
