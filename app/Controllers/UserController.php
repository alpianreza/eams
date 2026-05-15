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

  private function ensureAccess()
  {
    if (! hasRole(['admin', 'compliance'])) {
      return redirect()->to('/')->with('error', 'Akses ditolak');
    }

    return null;
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

  private function accessGroups(): array
  {
    return access_menu_groups();
  }

  private function normalizePageAccess($rawValue): string
  {
    return json_encode(
      normalize_page_access($rawValue),
      JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) ?: '[]';
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
    if ($redirect = $this->ensureAccess()) {
      return $redirect;
    }

    $users = $this->db->table('users')
      ->select('id,name,username,photo,role,permission,status,wa_number,page_access,created_at')
      ->orderBy('name', 'ASC')
      ->get()
      ->getResultArray();

    return view('users/index', [
      'users' => $users,
      'roles' => $this->loadRoles(),
      'accessGroups' => $this->accessGroups(),
      'title' => 'Manajemen User',
    ]);
  }

  public function create()
  {
    if ($redirect = $this->ensureAccess()) {
      return $redirect;
    }

    return view('users/create', [
      'title' => 'Tambah User',
      'roles' => $this->loadRoles(),
      'accessGroups' => $this->accessGroups(),
    ]);
  }

  public function store()
  {
    if ($redirect = $this->ensureAccess()) {
      return $redirect;
    }

    $wa = $this->normalizePhone($this->request->getPost('wa_number'));
    $role = $this->normalizeRole($this->request->getPost('role'));
    $pageAccess = normalize_page_access($this->request->getPost('page_access'));

    if ($pageAccess === []) {
      return redirect()->back()->withInput()->with('error', 'Pilih minimal satu halaman untuk user ini.');
    }

    $this->ensureRoleExists($role);

    $this->db->table('users')->insert([
      'name'       => trim((string) $this->request->getPost('name')),
      'username'   => trim((string) $this->request->getPost('username')),
      'password'   => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
      'role'       => $role,
      'permission' => $this->request->getPost('permission'),
      'wa_number'  => $wa,
      'page_access' => $this->normalizePageAccess($pageAccess),
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
    if ($redirect = $this->ensureAccess()) {
      return $redirect;
    }

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
    if ($redirect = $this->ensureAccess()) {
      return $redirect;
    }

    $user = $this->db->table('users')
      ->select('id,name,username,photo,role,permission,status,wa_number,page_access,created_at')
      ->where('id', $id)
      ->get()
      ->getRowArray();

    if (!$user) {
      return redirect()->to('/users')->with('error', 'User tidak ditemukan');
    }

    return view('users/edit', [
      'user'  => $user,
      'roles' => $this->loadRoles(),
      'accessGroups' => $this->accessGroups(),
      'title' => 'Edit User',
    ]);
  }

  public function update($id)
  {
    if ($redirect = $this->ensureAccess()) {
      return $redirect;
    }

    $user = $this->db->table('users')
      ->where('id', $id)
      ->get()
      ->getRowArray();

    if (!$user) {
      return redirect()->to('/users')->with('error', 'User tidak ditemukan');
    }

    $wa = $this->normalizePhone($this->request->getPost('wa_number'));
    $role = $this->normalizeRole($this->request->getPost('role'));
    $pageAccess = normalize_page_access($this->request->getPost('page_access'));

    if ($pageAccess === []) {
      return redirect()->back()->withInput()->with('error', 'Pilih minimal satu halaman untuk user ini.');
    }

    $this->ensureRoleExists($role);

    $data = [
      'name'       => trim((string) $this->request->getPost('name')),
      'username'   => trim((string) $this->request->getPost('username')),
      'role'       => $role,
      'permission' => $this->request->getPost('permission'),
      'status'     => $this->request->getPost('status'),
      'wa_number'  => $wa,
      'page_access' => $this->normalizePageAccess($pageAccess),
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

    if ((int) session()->get('user_id') === (int) $id) {
      $sessionPayload = [
        'name' => $data['name'],
        'role' => $data['role'],
        'permission' => $data['permission'],
        'page_access' => $data['page_access'],
      ];

      if (isset($data['photo'])) {
        $sessionPayload['photo'] = $data['photo'];
      }

      session()->set($sessionPayload);
    }

    audit_log('update_user', 'Mengubah user ' . $data['username']);

    return redirect()->to('users')->with('success', 'User berhasil diupdate');
  }

  public function deactivate($id)
  {
    if ($redirect = $this->ensureAccess()) {
      return $redirect;
    }

    $this->db->table('users')
      ->where('id', $id)
      ->update(['status' => 'inactive']);

    return redirect()->to('users')->with('success', 'User dinonaktifkan');
  }

  public function activate($id)
  {
    if ($redirect = $this->ensureAccess()) {
      return $redirect;
    }

    $this->db->table('users')
      ->where('id', $id)
      ->update(['status' => 'active']);

    return redirect()->to('users')->with('success', 'User diaktifkan');
  }
}
