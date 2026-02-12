<div class="row">
  <?php foreach ($evidences as $ev): ?>
    <div class="col-md-3 mb-4">
      <div class="card">
        <img src="/uploads/<?= esc($ev['photo']) ?>" class="card-img-top" style="height:200px;object-fit:cover;">

        <div class="card-body p-2">
          <small class="text-muted"><?= date('d M Y', strtotime($ev['check_date'])) ?></small>
          <h6 class="mb-1"><?= esc($ev['item_name']) ?></h6>
          <small>No: <?= esc($ev['no_inventaris']) ?></small><br>
          <small>Lokasi: <?= esc($ev['area_name']) ?></small>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="mt-3">
  <?= $pager->links() ?>
</div>