<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div
    x-data="itDeviceDetail({ refreshUrl: '/it/devices/<?= (int) $device['id'] ?>/fragment' })"
    x-init="init()"
    class="it-live-detail"
>
    <div x-ref="content"><?= view('it/devices/_detail_content', compact('device', 'asset', 'assignment', 'hw', 'extra', 'insights', 'commandHistory')) ?></div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/it-suite.css?v=' . filemtime(FCPATH . 'assets/css/it-suite.css')) ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/it-suite-alpine.js?v=' . filemtime(FCPATH . 'js/it-suite-alpine.js')) ?>"></script>
<script src="<?= base_url('js/it-device-live.js?v=' . filemtime(FCPATH . 'js/it-device-live.js')) ?>"></script>
<script src="<?= base_url('js/device-remote.js?v=' . filemtime(FCPATH . 'js/device-remote.js')) ?>"></script>
<?= $this->endSection() ?>
