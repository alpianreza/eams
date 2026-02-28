<?php helper(['os_lifecycle', 'device']); ?>

<div class="table-responsive">

  <table class="table table-sm table-bordered table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th>Hostname</th>
        <th class="d-none d-md-table-cell">User</th>
        <th class="d-none d-lg-table-cell">OS</th>
        <th class="d-none d-xl-table-cell">CPU</th>
        <th>RAM</th>
        <th>Storage</th>
        <th class="d-none d-lg-table-cell">Agent</th>
        <th>Last Seen</th>
        <th>Risk</th>
        <th class="d-none d-lg-table-cell">Lifecycle</th>
        <th>Status</th>
      </tr>
    </thead>

    <tbody>

      <?php foreach ($devices as $d): ?>

        <?php
        $online = device_is_online($d);

        /* ===== RISK (helper) ===== */
        $score = device_risk_score($d);
        [$label, $badge] = device_risk_label($score);

        /* ===== EXTRA JSON ===== */
        $extra = json_decode($d['cpu'] ?? '{}', true) ?? [];
        $release = $extra['os_release'] ?? null;

        /* ===== LIFECYCLE ===== */
        $lifecycle = function_exists('windows_lifecycle')
          ? windows_lifecycle($release)
          : ['status' => 'unknown', 'color' => 'secondary'];

        $recommend = function_exists('windows_upgrade_recommendation')
          ? windows_upgrade_recommendation($release)
          : null;
        ?>

        <tr class="device-row" data-id="<?= $d['id'] ?>">

          <!-- HOSTNAME -->
          <td>
            <a href="/it/devices/<?= $d['id'] ?>" class="fw-semibold">
              <?= esc($d['hostname'] ?? '-') ?>
            </a>
            <div class="small text-muted d-md-none">
              <?= esc($d['device_user'] ?? '') ?>
            </div>
          </td>

          <!-- USER -->
          <td class="d-none d-md-table-cell"><?= esc($d['device_user'] ?? '-') ?></td>

          <!-- OS -->
          <td class="d-none d-lg-table-cell"><?= esc($d['os'] ?? '-') ?></td>

          <!-- CPU -->
          <td class="d-none d-xl-table-cell text-truncate" style="max-width:200px">
            <?= esc($d['cpu_name'] ?? '-') ?>
          </td>

          <!-- RAM -->
          <td><?= esc($d['ram_gb'] ?? 0) ?> GB</td>

          <!-- STORAGE -->
          <td><?= esc($d['storage_gb'] ?? 0) ?> GB</td>

          <!-- AGENT -->
          <td class="d-none d-lg-table-cell"><?= esc($d['agent_version'] ?? '-') ?></td>

          <!-- LAST SEEN -->
          <td>
            <?php if (!empty($d['last_seen'])): ?>
              <?= date('d M H:i', strtotime($d['last_seen'])) ?>
            <?php else: ?>
              -
            <?php endif ?>
          </td>

          <!-- RISK -->
          <td>
            <span class="badge bg-<?= $badge ?>">
              <?= $label ?> (<?= $score ?>)
            </span>
          </td>

          <!-- LIFECYCLE -->
          <td class="d-none d-lg-table-cell">
            <span class="badge bg-<?= $lifecycle['color'] ?>">
              <?= strtoupper($lifecycle['status']) ?>
            </span>

            <?php if ($recommend): ?>
              <div class="small text-muted"><?= esc($recommend) ?></div>
            <?php endif ?>
          </td>

          <!-- STATUS -->
          <td>
            <?php if ($online): ?>
              <span class="badge bg-success">Online</span>
            <?php else: ?>
              <span class="badge bg-secondary">Offline</span>
            <?php endif ?>
          </td>

        </tr>

      <?php endforeach ?>

    </tbody>
  </table>

</div>

<ul class="pagination pagination-sm mb-0">
  <?= $pager->links('default', 'eams') ?>
</ul>