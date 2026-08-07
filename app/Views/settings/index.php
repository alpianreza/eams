<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $settings = $settings ?? []; $canManage = hasRole(['admin', 'compliance']); ?>
<div class="container-fluid px-0">
  <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4"><div><span class="text-uppercase small fw-semibold text-primary">System preferences</span><h3 class="mb-1">Pengaturan EAMS</h3><p class="text-muted mb-0">Kelola identitas perusahaan, kanal notifikasi, dan keamanan akun.</p></div></div>
  <?php if ($message = session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc($message) ?></div><?php endif; ?>
  <?php if ($message = session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc($message) ?></div><?php endif; ?>

  <?php if ($canManage): ?>
  <div class="card border-0 shadow-sm mb-4"><div class="card-header bg-transparent border-0 pt-4 px-4"><h5 class="mb-1"><i class="bi bi-buildings me-2"></i>Identitas perusahaan</h5><p class="text-muted small mb-0">Data ini tersedia untuk header, footer, dan penandatangan dokumen.</p></div><div class="card-body p-4">
    <form method="post" action="<?= base_url('settings/change-password') ?>" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="settings_action" value="company">
      <div class="row g-3"><div class="col-md-6"><label class="form-label">Nama perusahaan</label><input name="company_name" class="form-control" value="<?= esc($settings['company_name'] ?? '') ?>" required></div>
      <div class="col-md-6"><label class="form-label">Logo</label><input type="file" name="company_logo" class="form-control" accept="image/png,image/jpeg,image/webp"><div class="form-text">Maksimal 2 MB.</div></div>
      <div class="col-12"><label class="form-label">Alamat</label><textarea name="company_address" class="form-control" rows="2"><?= esc($settings['company_address'] ?? '') ?></textarea></div>
      <div class="col-12"><label class="form-label">Footer dokumen</label><input name="document_footer" class="form-control" value="<?= esc($settings['document_footer'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">Nama penandatangan</label><input name="document_signatory_name" class="form-control" value="<?= esc($settings['document_signatory_name'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">Jabatan penandatangan</label><input name="document_signatory_title" class="form-control" value="<?= esc($settings['document_signatory_title'] ?? '') ?>"></div></div>
      <button class="btn btn-primary mt-3"><i class="bi bi-save me-1"></i>Simpan identitas</button>
    </form>
  </div></div>

  <div class="card border-0 shadow-sm mb-4"><div class="card-header bg-transparent border-0 pt-4 px-4"><h5 class="mb-1"><i class="bi bi-bell me-2"></i>Kanal notifikasi</h5><p class="text-muted small mb-0">Notifikasi dalam aplikasi selalu aktif. Email memakai konfigurasi Email CodeIgniter; WhatsApp memakai webhook provider Anda.</p></div><div class="card-body p-4">
    <form method="post" action="<?= base_url('settings/change-password') ?>"><?= csrf_field() ?><input type="hidden" name="settings_action" value="notifications">
      <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="notification_email_enabled" id="emailEnabled" value="1" <?= ($settings['notification_email_enabled'] ?? '0') === '1' ? 'checked' : '' ?>><label class="form-check-label" for="emailEnabled">Kirim juga melalui email</label></div>
      <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="notification_whatsapp_enabled" id="waEnabled" value="1" <?= ($settings['notification_whatsapp_enabled'] ?? '0') === '1' ? 'checked' : '' ?>><label class="form-check-label" for="waEnabled">Kirim juga melalui WhatsApp</label></div>
      <div class="row g-3"><div class="col-md-7"><label class="form-label">Webhook WhatsApp provider</label><input type="url" name="notification_whatsapp_webhook" class="form-control" placeholder="https://provider.example/messages" value="<?= esc($settings['notification_whatsapp_webhook'] ?? '') ?>"></div><div class="col-md-5"><label class="form-label">Bearer token</label><input type="password" name="notification_whatsapp_token" class="form-control" placeholder="Kosongkan untuk mempertahankan token"></div></div>
      <button class="btn btn-primary mt-3"><i class="bi bi-send-check me-1"></i>Simpan kanal</button>
    </form>
  </div></div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm"><div class="card-header bg-transparent border-0 pt-4 px-4"><h5 class="mb-1"><i class="bi bi-shield-lock me-2"></i>Ganti password</h5></div><div class="card-body p-4"><form method="post" action="<?= base_url('settings/change-password') ?>"><?= csrf_field() ?><input type="hidden" name="settings_action" value="password"><div class="row g-3"><div class="col-md-4"><label class="form-label">Password lama</label><input type="password" name="old_password" class="form-control" autocomplete="current-password" required></div><div class="col-md-4"><label class="form-label">Password baru</label><input type="password" name="new_password" class="form-control" minlength="8" autocomplete="new-password" required></div><div class="col-md-4"><label class="form-label">Konfirmasi</label><input type="password" name="confirm_password" class="form-control" minlength="8" autocomplete="new-password" required></div></div><button class="btn btn-outline-primary mt-3">Perbarui password</button></form></div></div>
</div>
<?= $this->endSection() ?>
