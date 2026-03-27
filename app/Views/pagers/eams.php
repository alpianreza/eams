<?php $pager->setSurroundCount(2); ?>

<?php if ($pager->hasPrevious()): ?>
  <li class="page-item">
    <a class="page-link rounded-pill px-3 d-inline-flex align-items-center justify-content-center" href="<?= $pager->getPrevious() ?>" aria-label="Halaman sebelumnya">
      <i class="bi bi-chevron-left"></i>
    </a>
  </li>
<?php endif; ?>

<?php foreach ($pager->links() as $link): ?>
  <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
    <a class="page-link rounded-pill px-3" href="<?= $link['uri'] ?>" aria-label="Halaman <?= esc($link['title']) ?>">
      <?= $link['title'] ?>
    </a>
  </li>
<?php endforeach; ?>

<?php if ($pager->hasNext()): ?>
  <li class="page-item">
    <a class="page-link rounded-pill px-3 d-inline-flex align-items-center justify-content-center" href="<?= $pager->getNext() ?>" aria-label="Halaman berikutnya">
      <i class="bi bi-chevron-right"></i>
    </a>
  </li>
<?php endif; ?>
