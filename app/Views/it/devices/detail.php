<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php helper('device'); ?>
<?php $hw = device_hardware($device); ?>

<div class="container-fluid">

  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><?= esc($device['hostname']) ?></h3>
    </div>

    <div class="card-body">

      <h5>Hardware</h5>

      <ul>
        <li>OS: <?= esc($device['os']) ?> <?= esc($device['os_version']) ?></li>
        <li>CPU: <?= esc($device['cpu_name']) ?></li>
        <li>RAM: <?= esc($device['ram_gb']) ?> GB</li>
        <li>Storage: <?= esc($device['storage_gb']) ?> GB</li>
        <li>IP: <?= esc($device['last_ip']) ?></li>
        <li>Agent: <?= esc($device['agent_version']) ?></li>
      </ul>

      <hr>

      <h5>Inventory</h5>

      <?php if ($asset): ?>

        <!-- MEMORY -->
        <div class="card">
          <div class="card-header"><strong>Memory</strong></div>
          <div class="card-body">

            <?php foreach ($hw['ram_slots'] ?? [] as $r): ?>
              <div>
                <?= $r['size_gb'] ?>GB
                <small class="text-muted">
                  <?= $r['manufacturer'] ?? '' ?>
                  <?= $r['speed'] ?? '' ?>MHz
                </small>
              </div>
            <?php endforeach ?>

            <div class="mt-2">
              <span class="badge bg-info">
                Total <?= device_ram_total($device) ?>GB
              </span>
            </div>

          </div>
        </div>

        <!-- STORAGE -->
        <div class="card mt-3">
          <div class="card-header"><strong>Storage</strong></div>
          <div class="card-body">

            <?php foreach ($hw['disks'] ?? [] as $d): ?>
              <div>
                <?= $d['model'] ?>
                <small class="text-muted"><?= $d['size_gb'] ?>GB</small>
              </div>
            <?php endforeach ?>

            <div class="mt-2">
              <span class="badge bg-primary">
                Total <?= device_disk_total($device) ?>GB
              </span>
            </div>

          </div>
        </div>


        <div class="card mt-3">
          <div class="card-header">
            <strong>Remote Control</strong>
          </div>

          <div class="card-body">

            <button class="btn btn-danger btn-sm remote-btn"
              data-action="shutdown"
              data-id="<?= $device['id'] ?>">
              Shutdown
            </button>

            <button class="btn btn-warning btn-sm remote-btn"
              data-action="restart"
              data-id="<?= $device['id'] ?>">
              Restart
            </button>

            <button class="btn btn-primary btn-sm remote-btn"
              data-action="update"
              data-id="<?= $device['id'] ?>">
              Push Update
            </button>

            <button class="btn btn-secondary btn-sm"
              onclick="window.open('/downloads/logs/<?= $device['device_token'] ?>.log')">
              View Log
            </button>

          </div>
        </div>

        <!-- LINK ASSET -->
        <div class="alert alert-success mt-3">
          Linked to asset:
          <br>
          <b><?= esc($asset['inventory_no']) ?> — <?= esc($asset['asset_name']) ?></b>
          <br>
          <a href="/compliance/inventory/<?= $asset['id'] ?>" class="btn btn-sm btn-primary mt-2">
            Open Inventory
          </a>
        </div>

      <?php else: ?>

        <div class="alert alert-warning">
          Device belum punya asset
        </div>

      <?php endif ?>

    </div>
  </div>

</div>

<?= $this->endSection() ?>