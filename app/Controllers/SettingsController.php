<?php

namespace App\Controllers;

use App\Models\AppSettingModel;
use App\Models\NotificationModel;
use Config\Database;

class SettingsController extends BaseController
{
  protected $db;
  public function __construct() { $this->db = Database::connect(); }
  public function index()
  {
    $section = (string) ($this->request->getGet('section') ?? 'user'); if (! in_array($section, ['user', 'company'], true) || ($section === 'company' && ! hasRole(['admin', 'compliance']))) $section = 'user';
    $settings = $this->db->tableExists('app_settings') ? (new AppSettingModel())->allAsMap(true) : []; $user = $this->db->table('users')->where('id', (int) session()->get('user_id'))->get()->getRowArray();
    return view('settings/index', ['title' => $section === 'company' ? 'Pengaturan Perusahaan' : 'Pengaturan User', 'settings' => $settings, 'currentUser' => $user ?? [], 'section' => $section]);
  }
  public function changePassword()
  {
    $action = (string) $this->request->getPost('settings_action');
    if ($action === 'company') return $this->saveCompanySettings(); if ($action === 'notifications') return $this->saveNotificationSettings(); if ($action === 'contact') return $this->saveNotificationContact(); if ($action === 'mark_notifications_read') return $this->markNotificationsRead();
    $userId = session()->get('user_id'); $old = $this->request->getPost('old_password'); $new = $this->request->getPost('new_password'); $confirm = $this->request->getPost('confirm_password'); $user = $this->db->table('users')->where('id', $userId)->get()->getRowArray();
    if (! $user || ! password_verify((string) $old, $user['password'])) return redirect()->to('settings?section=user')->with('error', 'Password lama salah'); if ($new !== $confirm) return redirect()->to('settings?section=user')->with('error', 'Konfirmasi password tidak cocok'); if (strlen((string) $new) < 8) return redirect()->to('settings?section=user')->with('error', 'Password baru minimal 8 karakter');
    $this->db->table('users')->where('id', $userId)->update(['password' => password_hash((string) $new, PASSWORD_DEFAULT)]); return redirect()->to('settings?section=user')->with('success', 'Password berhasil diganti');
  }
  private function markNotificationsRead() { if ($this->db->tableExists('notifications')) (new NotificationModel())->where('user_id', (int) session()->get('user_id'))->where('read_at', null)->set(['read_at' => date('Y-m-d H:i:s')])->update(); return redirect()->to('home?view=notifications')->with('success', 'Semua notifikasi ditandai dibaca.'); }
  private function ensureAdmin(): bool { return hasRole(['admin', 'compliance']); }
  private function saveNotificationContact() { $email = trim((string) $this->request->getPost('email')); if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) return redirect()->to('settings?section=user')->with('error', 'Alamat email tidak valid.'); $digits = preg_replace('/\D+/', '', (string) $this->request->getPost('wa_number')); if ($digits !== '' && str_starts_with($digits, '0')) $digits = '62' . substr($digits, 1); elseif ($digits !== '' && str_starts_with($digits, '8')) $digits = '62' . $digits; $this->db->table('users')->where('id', (int) session()->get('user_id'))->update(['email' => $email ?: null, 'wa_number' => $digits ?: null]); return redirect()->to('settings?section=user')->with('success', 'Kontak notifikasi berhasil disimpan.'); }
  private function saveCompanySettings() { if (! $this->ensureAdmin()) return redirect()->to('settings?section=user')->with('error', 'Akses ditolak.'); if (! $this->db->tableExists('app_settings')) return redirect()->to('settings?section=company')->with('error', 'Jalankan migrasi database.'); $model = new AppSettingModel(); $uid = (int) session()->get('user_id'); foreach (['company_name','company_address','document_footer','document_signatory_name','document_signatory_title'] as $key) $model->put($key, trim((string) $this->request->getPost($key)), false, $uid); $logo = $this->request->getFile('company_logo'); if ($logo && $logo->isValid() && ! $logo->hasMoved()) { if ($logo->getSize() > 2097152 || ! in_array($logo->getMimeType(), ['image/jpeg','image/png','image/webp'], true)) return redirect()->to('settings?section=company')->with('error', 'Logo harus JPG, PNG, atau WEBP dan maksimal 2 MB.'); $dir = FCPATH . 'uploads/company'; if (! is_dir($dir)) mkdir($dir, 0775, true); $name = $logo->getRandomName(); $logo->move($dir, $name); $model->put('company_logo', 'uploads/company/' . $name, false, $uid); } return redirect()->to('settings?section=company')->with('success', 'Identitas perusahaan berhasil disimpan.'); }
  private function saveNotificationSettings() { if (! $this->ensureAdmin()) return redirect()->to('settings?section=user')->with('error', 'Akses ditolak.'); $model = new AppSettingModel(); $uid = (int) session()->get('user_id'); $model->put('notification_email_enabled', $this->request->getPost('notification_email_enabled') ? '1' : '0', false, $uid); $model->put('notification_whatsapp_enabled', $this->request->getPost('notification_whatsapp_enabled') ? '1' : '0', false, $uid); $model->put('notification_whatsapp_webhook', trim((string) $this->request->getPost('notification_whatsapp_webhook')), false, $uid); $token = trim((string) $this->request->getPost('notification_whatsapp_token')); if ($token !== '') $model->put('notification_whatsapp_token', $token, true, $uid); return redirect()->to('settings?section=company')->with('success', 'Pengaturan notifikasi berhasil disimpan.'); }
}
