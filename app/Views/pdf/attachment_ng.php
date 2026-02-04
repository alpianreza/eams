<h4>Lampiran Ketidaksesuaian</h4>

<?php foreach ($ngItems as $ng): ?>
  <div class="ng-block">
    <p><strong>Pertanyaan:</strong> <?= esc($ng['question']) ?></p>
    <p><strong>Catatan:</strong> <?= esc($ng['note']) ?></p>

    <?php if ($ng['photo']): ?>
      <img src="<?= $ng['photo_path'] ?>" class="photo">
    <?php endif; ?>
  </div>
<?php endforeach; ?>