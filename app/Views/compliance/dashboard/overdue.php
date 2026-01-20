<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm border-0">
  <div class="card-body">

    <h5 class="mb-3">Checklist Overdue</h5>

    <?php if (empty($overdues)): ?>
      <div class="alert alert-success">
        🎉 Tidak ada checklist yang overdue
      </div>
    <?php else: ?>
      <table class="table table-bordered table-hover">
        <thead>
          <tr>
            <th>Asset</th>
            <th>Code</th>
            <th>Location</th>
            <th>Checklist</th>
            <th>Period</th>
            <th>Last Check</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($overdues as $o): ?>
            <tr>
              <td><?= esc($o['asset_type']) ?></td>
              <td><?= esc($o['asset_code']) ?></td>
              <td><?= esc($o['location']) ?></td>
              <td><?= esc($o['checklist']) ?></td>
              <td><?= esc($o['period']) ?></td>
              <td><?= esc($o['last_check'] ?? '-') ?></td>
              <td>
                <a href="<?= base_url(
                            'compliance/inventory/' . $o['inventory_id']
                          ) ?>"
                  class="btn btn-sm btn-danger">
                  Detail
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

  </div>
</div>

<?= $this->endSection() ?>