<?php if ($pager): ?>

  <?php
  $currentPage = $pager->getCurrentPage();
  $perPage     = $pager->getPerPage();
  $total       = $pager->getTotal();

  $start = ($currentPage - 1) * $perPage + 1;
  $end   = min($currentPage * $perPage, $total);
  ?>

  <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">

    <div class="text-muted small">
      Showing <?= $start ?> to <?= $end ?> of <?= $total ?> entries
    </div>

    <div class="pagination-wrapper">
      <?= $pager->links() ?>
    </div>

  </div>

<?php endif ?>