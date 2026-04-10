<?php
$pager = $pager ?? null;
$devicesPerPage = $pager ? $pager->getPerPage() : 20;
$currentPage = $pager ? $pager->getCurrentPage() : 1;
$no = 1 + ($devicesPerPage * ($currentPage - 1));
?>
<div class="table-responsive">
    <table class="table align-middle mb-0 it-table">
        <thead>
            <tr>
                <th width="56" class="text-center">No</th>
                <th width="88" class="text-center">Foto</th>
                <th>No Inventaris</th>
                <th>Nama Asset</th>
                <th class="d-none d-lg-table-cell">Brand</th>
                <th>Status</th>
                <th class="d-none d-md-table-cell">Lokasi</th>
                <th width="190" class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($assets)): ?>
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        Data asset tidak ditemukan.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($assets as $a): ?>
                    <?php
                    $statusRaw = strtolower(trim((string) ($a['status'] ?? '-')));
                    $statusClass = match ($statusRaw) {
                        'baik', 'normal' => 'success',
                        'rusak' => 'danger',
                        'dipakai' => 'primary',
                        default => 'secondary',
                    };
                    ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="text-center">
                            <?php if (!empty($a['photo'])): ?>
                                <img src="<?= base_url('uploads/assets/' . $a['photo']) ?>" class="it-thumb" alt="Foto <?= esc($a['asset_name'] ?? 'asset') ?>">
                            <?php else: ?>
                                <span class="it-thumb-placeholder"><i class="bi bi-image"></i></span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-semibold"><?= esc($a['inventory_no']) ?></td>
                        <td><?= esc($a['asset_name']) ?></td>
                        <td class="d-none d-lg-table-cell"><?= esc($a['brand']) ?></td>
                        <td><span class="badge text-bg-<?= esc($statusClass) ?>"><?= esc(ucfirst($a['status'] ?? '-')) ?></span></td>
                        <td class="d-none d-md-table-cell"><?= esc($a['location']) ?></td>
                        <td class="text-center">
                            <div class="d-flex flex-wrap justify-content-center gap-1">
                                <a href="<?= base_url('it-assets/detail/' . $a['id']) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                                <?php if (session()->get('permission') === 'write' || session()->get('role') === 'admin'): ?>
                                    <a href="<?= base_url('it-assets/edit/' . $a['id']) ?>" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-3 d-flex justify-content-end">
    <ul class="pagination pagination-sm mb-0">
        <?= $pager ? $pager->links('default', 'eams') : '' ?>
    </ul>
</div>