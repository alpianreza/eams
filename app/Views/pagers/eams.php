<?php $pager->setSurroundCount(2); ?>

<?php if ($pager->hasPrevious()): ?>
  <li class="page-item">
    <a class="page-link rounded-pill px-3"
      href="<?= $pager->getPrevious() ?>">
      ‹
    </a>
  </li>
<?php endif; ?>

<?php foreach ($pager->links() as $link): ?>
  <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
    <a class="page-link rounded-pill px-3"
      href="<?= $link['uri'] ?>">
      <?= $link['title'] ?>
    </a>
  </li>
<?php endforeach; ?>

<?php if ($pager->hasNext()): ?>
  <li class="page-item">
    <a class="page-link rounded-pill px-3"
      href="<?= $pager->getNext() ?>">
      ›
    </a>
  </li>
<?php endif; ?>