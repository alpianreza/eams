<?php

namespace App\Controllers;

use Config\Database;
use App\Models\UserRoleModel;

class UserController extends BaseController
{
  protected $db;
  protected UserRoleModel $roleModel;

  public function __construct()
  {
    $this->db = Database::connect();
    $this->roleModel = new UserRoleModel();
  }

  private function normalizePhone($value): ?string
  {
    $digits = preg_replace('/\D+/', '', (string) $value);

    if (!$digits) {
      return null;
    }

    if (str_starts_with($digits, '0')) {
      $digits = '62' . substr($digits, 1);
    } elseif (str_starts_with($digits, '8')) {
      $digits = '62' . $digits;
    }

    return $digits;
  }

  private function normalizeRole($value, ?string $default = 'staff'): string
  {
    $role = strtolower(trim((string) $value));
    $role = preg_replace('/[^a-z0-9]+/', '_', $role) ?? '';
    $role = trim($role, '_');

    if ($role !== '') {
      return $role;
    }

    return $default ?? '';
  }

  private function displayRoleLabel(string $role): string
  {
    $role = str_replace(['_', '-'], ' ', trim($role));

    return $role !== '' ? ucwords($role) : '-';
  }

  private function loadRoles(): array
  {
    $defaults = ['admin', 'security', 'staff', 'compliance', 'auditor', 'office'];

    foreach ($defaults as $role) {
      $this->ensureRoleExists($role);
    }

    $roles = $this->roleModel
      ->orderBy('name', 'ASC')
      ->findAll();

    return array_map(static function (array $row): array {
      $name = (string) ($row['name'] ?? '');
      return [
        'name'  => $name,
        'label' => ucwords(str_replace(['_', '-'], ' ', $name)),
      ];
    }, $roles);
  }

  private function ensureRoleExists(string $role): void
  {
    $role = $this->normalizeRole($role);
    if ($role === '') {
      return;
    }

    $exists = $this->roleModel->where('name', $role)->first();
    if ($exists) {
      return;
    }

    $this->roleModel->insert([
      'name' => $role,
    ]);
  }

  public function index()
  {
    $users = $this->db->table('users')
      ->select('id,name,username,photo,role,permission,status,wa_number,created_at')
      ->orderBy('name', 'ASC')
      ->get()
      ->getResultArray();

    return view('users/index', [
      'users' => $users,
      'roles' => $this->loadRoles(),
      'title' => 'Manajemen User',
    ]);
  }

  public function create()
  {
    return view('users/create', [
      'title' => 'Tambah User',
      'roles' => $this->loadRoles(),
    ]);
  }

  public function store()
  {
    $wa = $this->normalizePhone($this->request->getPost('wa_number'));
    $role = $this->normalizeRole($this->request->getPost('role'));

    $this->ensureRoleExists($role);

    $this->db->table('users')->insert([
      'name'       => trim((string) $this->request->getPost('name')),
      'username'   => trim((string) $this->request->getPost('username')),
      'password'   => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
      'role'       => $role,
      'permission' => $this->request->getPost('permission'),
      'wa_number'  => $wa,
      'status'     => 'active',
      'created_at' => date('Y-m-d H:i:s'),
    ]);

    audit_log(
      'create_user',
      'Membuat user ' . $this->request->getPost('username')
    );

    return redirect()->to('users')->with('success', 'User berhasil ditambahkan');
  }

  public function storeRole()
  {
    $rawRole = trim((string) $this->request->getPost('name'));
    if ($rawRole === '') {
      return redirect()->back()->with('error', 'Nama role wajib diisi.');
    }

    $role = $this->normalizeRole($rawRole, null);

    if ($role === '') {
      return redirect()->back()->with('error', 'Nama role wajib diisi.');
    }

    $exists = $this->roleModel->where('name', $role)->first();
    if ($exists) {
      return redirect()->back()->with('warning', 'Role sudah ada.');
    }

    $this->roleModel->insert([
      'name' => $role,
    ]);

    audit_log('create_role', 'Menambahkan role ' . $role);

    return redirect()->to('users')->with('success', 'Role berhasil ditambahkan');
  }

  public function edit($id)
  {
    $user = $this->db->table('users')
      ->select('id,name,username,photo,role,permission,status,wa_number,created_at')
      ->where('id', $id)
      ->get()
      ->getRowArray();

    if (!$user) {
      return redirect()->to('/users')->with('error', 'User tidak ditemukan');
    }

    return view('users/edit', [
      'user'  => $user,
      'roles' => $this->loadRoles(),
      'title' => 'Edit User',
    ]);
  }

  public function update($id)
  {
    $user = $this->db->table('users')
      ->where('id', $id)
      ->get()
      ->getRowArray();

    if (!$user) {
      return redirect()->to('/users')->with('error', 'User tidak ditemukan');
    }

    $wa = $this->normalizePhone($this->request->getPost('wa_number'));
    $role = $this->normalizeRole($this->request->getPost('role'));
    $this->ensureRoleExists($role);

    $data = [
      'name'       => trim((string) $this->request->getPost('name')),
      'username'   => trim((string) $this->request->getPost('username')),
      'role'       => $role,
      'permission' => $this->request->getPost('permission'),
      'status'     => $this->request->getPost('status'),
      'wa_number'  => $wa,
    ];

    if ($this->request->getPost('password')) {
      $data['password'] = password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT);
    }

    $file = $this->request->getFile('photo');

    if ($file && $file->isValid() && !$file->hasMoved()) {
      if ($file->getSize() > 2 * 1024 * 1024) {
        return redirect()->back()->with('error', 'Maksimal ukuran foto 2MB');
      }

      $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
      if (!in_array($file->getMimeType(), $allowedTypes, true)) {
        return redirect()->back()->with('error', 'Format foto tidak valid');
      }

      $newName = $file->getRandomName();
      $file->move(FCPATH . 'uploads/users/', $newName);

      if (!empty($user['photo']) && file_exists(FCPATH . 'uploads/users/' . $user['photo'])) {
        unlink(FCPATH . 'uploads/users/' . $user['photo']);
      }

      $data['photo'] = $newName;
    }

    $this->db->table('users')
      ->where('id', $id)
      ->update($data);

    audit_log('update_user', 'Mengubah user ' . $data['username']);

    return redirect()->to('users')->with('success', 'User berhasil diupdate');
  }

  public function deactivate($id)
  {
    $this->db->table('users')
      ->where('id', $id)
      ->update(['status' => 'inactive']);

    return redirect()->to('users')->with('success', 'User dinonaktifkan');
  }

  public function activate($id)
  {
    $this->db->table('users')
      ->where('id', $id)
      ->update(['status' => 'active']);

    return redirect()->to('users')->with('success', 'User diaktifkan');
  }
}
