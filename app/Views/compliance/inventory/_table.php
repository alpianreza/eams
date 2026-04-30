<?php
$totalRows = is_array($inventories) ? count($inventories) : 0;
$currentPage = isset($pager) ? (int) $pager->getCurrentPage('default') : 1;
$perPageValue = isset($perPage) ? (int) $perPage : $totalRows;
$runningNo = (($currentPage > 0 ? $currentPage : 1) - 1) * max($perPageValue, 1) + 1;
$activeSort = (string) ($sort ?? 'no');
$activeDirection = (string) ($direction ?? 'asc');
$sortIconClass = static function (string $key) use ($activeSort, $activeDirection): string {
  if ($activeSort !== $key) {
    return 'bi-arrow-down-up';
  }

  return $activeDirection === 'desc' ? 'bi-sort-down-alt' : 'bi-sort-down';
};
?>

<div class="inventory-desktop">
  <div class="card border-0 shadow-sm inventory-table-card no-lift">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table id="inventoryTable" class="table table-striped align-middle mb-0 inventory-table">
          <thead class="table-light">
            <tr>
              <th width="60">
                <button type="button" class="inventory-sort-btn" data-sort-key="no" @click.prevent="toggleSort('no')">
                  <span>No</span>
                  <i class="bi <?= esc($sortIconClass('no')) ?>"></i>
                </button>
              </th>
              <th class="d-none">Kategori</th>
              <th class="d-none">Area</th>
              <th>
                <button type="button" class="inventory-sort-btn" data-sort-key="item" @click.prevent="toggleSort('item')">
                  <span>Nama Item</span>
                  <i class="bi <?= esc($sortIconClass('item')) ?>"></i>
                </button>
              </th>
              <th>
                <button type="button" class="inventory-sort-btn" data-sort-key="asset_code" @click.prevent="toggleSort('asset_code')">
                  <span>No Inventaris</span>
                  <i class="bi <?= esc($sortIconClass('asset_code')) ?>"></i>
                </button>
              </th>
              <th>
                <button type="button" class="inventory-sort-btn" data-sort-key="type" @click.prevent="toggleSort('type')">
                  <span>Tipe</span>
                  <i class="bi <?= esc($sortIconClass('type')) ?>"></i>
                </button>
              </th>
              <th>
                <button type="button" class="inventory-sort-btn" data-sort-key="specific_area" @click.prevent="toggleSort('specific_area')">
                  <span>Area Spesifik</span>
                  <i class="bi <?= esc($sortIconClass('specific_area')) ?>"></i>
                </button>
              </th>
              <th>
                <button type="button" class="inventory-sort-btn" data-sort-key="pic" @click.prevent="toggleSort('pic')">
                  <span>PIC</span>
                  <i class="bi <?= esc($sortIconClass('pic')) ?>"></i>
                </button>
              </th>
              <th>
                <button type="button" class="inventory-sort-btn" data-sort-key="status" @click.prevent="toggleSort('status')">
                  <span>Status</span>
                  <i class="bi <?= esc($sortIconClass('status')) ?>"></i>
                </button>
              </th>
              <th>Catatan</th>
              <?php if (hasRole(['admin', 'compliance'])): ?>
                <th width="140" class="text-center">Aksi</th>
              <?php endif; ?>
            </tr>
          </thead>

          <tbody>
            <?php if (empty($inventories)): ?>
              <tr>
                <td colspan="<?= hasRole(['admin', 'compliance']) ? '11' : '10' ?>" class="text-center py-4 text-muted">
                  Belum ada data inventory untuk filter ini.
                </td>
              </tr>
            <?php endif; ?>

            <?php foreach ($inventories as $inv): ?>
              <?php
              $status = (string)($inv['status'] ?? '');
              $statusClass = 'bg-light text-dark';
              $statusText = '-';

              if ($status === 'Good') {
                $statusClass = 'bg-success';
                $statusText = 'Baik';
              } elseif ($status === 'Need Repair') {
                $statusClass = 'bg-warning text-dark';
                $statusText = 'Perlu Perbaikan';
              } elseif ($status === 'Not Active') {
                $statusClass = 'bg-secondary';
                $statusText = 'Tidak Aktif';
              }

              $rowClass = '';
              if ($status === 'Need Repair') {
                $rowClass = 'table-warning';
              }
              if ($status === 'Not Active') {
                $rowClass = 'table-secondary';
              }
              ?>

              <tr class="<?= $rowClass ?>" data-inventory-id="<?= $inv['id'] ?>">
                <td class="inventory-row-no"><?= $runningNo++ ?></td>

                <td class="d-none col-category"><?= esc($inv['category_name']) ?></td>
                <td class="d-none col-area"><?= esc($inv['area_name']) ?></td>

                <td class="col-item">
                  <a href="<?= base_url('compliance/inventory/detail/' . $inv['id']) ?>" class="fw-semibold text-decoration-none text-dark">
                    <?= esc($inv['item_display_name']) ?>
                  </a>
                </td>

                <td class="fw-semibold"><?= esc($inv['asset_code']) ?></td>
                <td><?= esc($inv['type_description'] ?? '-') ?></td>
                <td class="col-specific"><?= esc($inv['specific_area'] ?? '-') ?></td>
                <td><?= esc($inv['pic'] ?? '-') ?></td>

                <td>
                  <span class="badge <?= $statusClass ?>"><?= esc($statusText) ?></span>
                </td>

                <td><?= esc($inv['remark'] ?? '-') ?></td>

                <?php if (hasRole(['admin', 'compliance'])): ?>
                  <td class="text-center">
                    <div class="d-inline-flex align-items-center gap-1 inventory-row-actions">
                      <button
                        type="button"
                        class="btn btn-sm btn-outline-warning btn-edit"
                        data-id="<?= $inv['id'] ?>"
                        data-category-id="<?= $inv['category_id'] ?>"
                        data-item-type-id="<?= $inv['item_type_id'] ?>"
                        data-area-id="<?= $inv['area_id'] ?>"
                        data-code="<?= esc($inv['asset_code']) ?>"
                        data-type="<?= esc($inv['type_description']) ?>"
                        data-pic="<?= esc($inv['pic']) ?>"
                        data-status="<?= esc($inv['status']) ?>"
                        data-remark="<?= esc($inv['remark']) ?>"
                        data-specific="<?= esc($inv['specific_area']) ?>"
                        data-expired="<?= esc($inv['expired_date']) ?>"
                        title="Edit">
                        <i class="bi bi-pencil-square"></i>
                      </button>

                      <form action="<?= base_url('compliance/inventory/delete/' . $inv['id']) ?>" method="post" class="d-inline form-delete">
                        <?= csrf_field() ?>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete" title="Hapus">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>

                      <?php if (!empty($inv['qr_image'])): ?>
                        <button
                          type="button"
                          class="btn btn-sm btn-outline-secondary btn-show-qr"
                          data-id="<?= $inv['id'] ?>"
                          data-qr="<?= base_url('uploads/qr/' . $inv['qr_image']) ?>"
                          data-item="<?= esc($inv['item_display_name']) ?>"
                          data-no="<?= esc($inv['asset_code']) ?>"
                          title="Lihat QR">
                          <i class="bi bi-qr-code"></i>
                        </button>
                      <?php endif; ?>
                    </div>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="inventory-mobile">
  <?php if (empty($inventories)): ?>
    <div class="card shadow-sm border-0 inventory-card no-lift">
      <div class="card-body text-center text-muted py-4">
        Belum ada data inventory untuk filter ini.
      </div>
    </div>
  <?php endif; ?>

  <?php foreach ($inventories as $inv): ?>
    <?php
    $status = (string)($inv['status'] ?? '');
    $mobileStatusClass = 'bg-secondary';
    $mobileStatusText = 'Tidak Aktif';

    if ($status === 'Good') {
      $mobileStatusClass = 'bg-success';
      $mobileStatusText = 'Baik';
    } elseif ($status === 'Need Repair') {
      $mobileStatusClass = 'bg-warning text-dark';
      $mobileStatusText = 'Perlu Perbaikan';
    }
    ?>

    <div class="card mb-3 shadow-sm inventory-card no-lift" data-inventory-id="<?= $inv['id'] ?>">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="fw-semibold mb-1"><?= esc($inv['item_display_name']) ?></div>
            <div class="text-muted small mb-1"><?= esc($inv['asset_code']) ?></div>
          </div>
          <span class="badge <?= $mobileStatusClass ?>"><?= esc($mobileStatusText) ?></span>
        </div>

        <div class="small text-muted inventory-mobile-meta mt-2">
          <div><strong>Tipe:</strong> <?= esc($inv['type_description'] ?? '-') ?></div>
          <div><strong>Area:</strong> <?= esc($inv['specific_area'] ?? '-') ?></div>
          <div><strong>PIC:</strong> <?= esc($inv['pic'] ?? '-') ?></div>
        </div>

        <div class="d-flex justify-content-between align-items-center gap-2 mt-3">
          <a href="<?= base_url('compliance/inventory/detail/' . $inv['id']) ?>" class="btn btn-outline-primary btn-sm">
            Lihat Detail
          </a>

          <?php if (hasRole(['admin', 'compliance'])): ?>
            <div class="d-flex gap-1 align-items-center">
              <button
                type="button"
                class="btn btn-sm btn-outline-warning btn-edit"
                data-id="<?= $inv['id'] ?>"
                data-category-id="<?= $inv['category_id'] ?>"
                data-item-type-id="<?= $inv['item_type_id'] ?>"
                data-area-id="<?= $inv['area_id'] ?>"
                data-code="<?= esc($inv['asset_code']) ?>"
                data-type="<?= esc($inv['type_description']) ?>"
                data-pic="<?= esc($inv['pic']) ?>"
                data-status="<?= esc($inv['status']) ?>"
                data-remark="<?= esc($inv['remark']) ?>"
                data-specific="<?= esc($inv['specific_area']) ?>"
                data-expired="<?= esc($inv['expired_date']) ?>"
                title="Edit">
                <i class="bi bi-pencil-square"></i>
              </button>

              <?php if (!empty($inv['qr_image'])): ?>
                <button
                  type="button"
                  class="btn btn-sm btn-outline-secondary btn-show-qr"
                  data-id="<?= $inv['id'] ?>"
                  data-qr="<?= base_url('uploads/qr/' . $inv['qr_image']) ?>"
                  data-item="<?= esc($inv['item_display_name']) ?>"
                  data-no="<?= esc($inv['asset_code']) ?>"
                  title="Lihat QR">
                  <i class="bi bi-qr-code"></i>
                </button>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
