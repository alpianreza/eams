<?php if (isset($pager)): ?>
  <?php $pager->only(['category', 'area', 'q', 'perPage', 'sort', 'direction']); ?>
  <nav class="inventory-pagination-nav d-flex justify-content-center" aria-label="Navigasi halaman inventory">
    <ul class="pagination pagination-sm mb-0 inventory-pagination flex-wrap justify-content-center">
      <?= $pager->links('default', 'eams') ?>
    </ul>
  </nav>
<?php endif; ?>
