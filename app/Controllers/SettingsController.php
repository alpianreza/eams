<?php

namespace App\Controllers;

use Config\Database;

class SettingsController extends BaseController
{
  protected $db;

  public function __construct()
  {
    $this->db = Database::connect();
  }

  // halaman settings
  public function index()
  {
    return view('settings/index', [
      'title' => 'Pengaturan Akun'
    ]);
  }

  // proses ganti password
  public function changePassword()
  {
    $userId = session()->get('user_id');

    $oldPassword = $this->request->getPost('old_password');
    $newPassword = $this->request->getPost('new_password');
    $confirm     = $this->request->getPost('confirm_password');

    // ambil user
    $user = $this->db->table('users')
      ->where('id', $userId)
      ->get()
      ->getRowArray();

    // 1️⃣ cek password lama
    if (!password_verify($oldPassword, $user['password'])) {
      return redirect()->back()
        ->with('error', 'Password lama salah');
    }

    // 2️⃣ cek konfirmasi
    if ($newPassword !== $confirm) {
      return redirect()->back()
        ->with('error', 'Konfirmasi password tidak cocok');
    }

    // 3️⃣ update password
    $this->db->table('users')
      ->where('id', $userId)
      ->update([
        'password' => password_hash($newPassword, PASSWORD_DEFAULT)
      ]);

    // 4️⃣ audit log (kalau kamu pakai)
    if (function_exists('audit_log')) {
      audit_log('change_password', 'User mengganti password sendiri');
    }

    return redirect()->back()
      ->with('success', 'Password berhasil diganti');
  }
}
