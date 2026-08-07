<?php

namespace App\Controllers;

use App\Models\AppSettingModel;
use Config\Database;

class SettingsController extends BaseController
{
  protected $db;
  public function __construct() { $this->db = Database::connect(); }

  public function index()
  {
    $settings = $this->db->tableExists('app_settings') ? (new AppSettingModel())->allAsMap(true) : [];
    return view('settings/index', ['title' => 'Pengaturan EAMS', 'settings' => $settings]);
  }

  public function changePassword()
  {
    $action = (string) $this->request->getPost('settings_action');
    if ($action === 'company') return $this->saveCompanySettings();
    if ($action === 'notifications') return $this->saveNotificationSettings();

    $userId = session()->get('user_id');
    $oldPassword = $this->request->getPost('old_password');
    $newPassword = $this->request->getPost('new_password');
    $confirm = $this->request->getPost('confirm_password');
    $user = $this->db->table('users')->where('id', $userId)->get()->getRowArray();
    if (! $user || ! password_verify((string) $oldPassword, $user['password'])) return redirect()->back()->with('error', 'Password lama salah');
    if ($newPassword !== $confirm) return redirect()->back()->with('error', 'Konfirmasi password tidak cocok');
    if (strlen((string) $newPassword) < 8) return redirect()->back()->with('error', 'Password baru minimal 8 karakter');
    $this->db->table('users')->where('id', $userId)->update(['password' => password_hash((string) $newPassword, PASSWORD_DEFAULT)]);
    if (function_exists('audit_log')) audit_log('change_password', 'User mengganti password sendiri');
    return redirect()->back()->with('success', 'Password berhasil diganti');
  }

  private function ensureAdmin()
  {
    return hasRole(['admin', 'compliance']);
  }

  private function saveCompanySettings()
  {
    if (! $this->ensureAdmin()) return redirect()->back()->with('error', 'Hanya admin/compliance yang dapat mengubah identitas perusahaan.');
    if (! $this->db->tableExists('app_settings')) return redirect()->back()->with('error', 'Jalankan migrasi database terlebih dahulu.');
    $model = new AppSettingModel();
    $userId = (int) session()->get('user_id');
    foreach (['company_name', 'company_address', 'document_footer', 'document_signatory_name', 'document_signatory_title'] as $key) {
      $model->put($key, trim((string) $this->request->getPost($key)), false, $userId);
    }

    $logo = $this->request->getFile('company_logo');
    if ($logo && $logo->isValid() && ! $logo->hasMoved()) {
      if ($logo->getSize() > 2 * 1024 * 1024 || ! in_array($logo->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true)) return redirect()->back()->with('error', 'Logo harus JPG, PNG, atau WEBP dan maksimal 2 MB.');
      $directory = FCPATH . 'uploads/company';
      if (! is_dir($directory)) mkdir($directory, 0775, true);
      $name = $logo->getRandomName();
      $logo->move($directory, $name);
      $model->put('company_logo', 'uploads/company/' . $name, false, $userId);
    }
    if (function_exists('audit_log')) audit_log('update_company_settings', 'Memperbarui identitas perusahaan');
    return redirect()->back()->with('success', 'Identitas perusahaan berhasil disimpan.');
  }

  private function saveNotificationSettings()
  {
    if (! $this->ensureAdmin()) return redirect()->back()->with('error', 'Hanya admin/compliance yang dapat mengubah kanal notifikasi.');
    if (! $this->db->tableExists('app_settings')) return redirect()->back()->with('error', 'Jalankan migrasi database terlebih dahulu.');
    $model = new AppSettingModel();
    $userId = (int) session()->get('user_id');
    $model->put('notification_email_enabled', $this->request->getPost('notification_email_enabled') ? '1' : '0', false, $userId);
    $model->put('notification_whatsapp_enabled', $this->request->getPost('notification_whatsapp_enabled') ? '1' : '0', false, $userId);
    $model->put('notification_whatsapp_webhook', trim((string) $this->request->getPost('notification_whatsapp_webhook')), false, $userId);
    $token = trim((string) $this->request->getPost('notification_whatsapp_token'));
    if ($token !== '') $model->put('notification_whatsapp_token', $token, true, $userId);
    if (function_exists('audit_log')) audit_log('update_notification_settings', 'Memperbarui kanal notifikasi');
    return redirect()->back()->with('success', 'Pengaturan notifikasi berhasil disimpan.');
  }
}
