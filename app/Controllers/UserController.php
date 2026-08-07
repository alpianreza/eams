<?php

namespace App\Controllers;

use App\Models\UserRoleModel;
use Config\Database;

class UserController extends BaseController
{
  protected $db; protected UserRoleModel $roleModel;
  public function __construct() { $this->db = Database::connect(); $this->roleModel = new UserRoleModel(); }
  private function ensureAccess() { return hasRole(['admin', 'compliance']) ? null : redirect()->to('/')->with('error', 'Akses ditolak'); }
  private function normalizePhone($value): ?string { $digits = preg_replace('/\D+/', '', (string) $value); if (! $digits) return null; if (str_starts_with($digits, '0')) $digits = '62' . substr($digits, 1); elseif (str_starts_with($digits, '8')) $digits = '62' . $digits; return $digits; }
  private function normalizeEmail($value): ?string { $email = strtolower(trim((string) $value)); return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null; }
  private function normalizeRole($value, ?string $default = 'staff'): string { $role = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower(trim((string) $value))) ?? '', '_'); return $role !== '' ? $role : ($default ?? ''); }
  private function accessGroups(): array { return access_menu_groups(); }
  private function normalizePageAccess($raw): string { return json_encode(normalize_page_access($raw), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]'; }
  private function ensureRoleExists(string $role): void { $role = $this->normalizeRole($role); if ($role !== '' && ! $this->roleModel->where('name', $role)->first()) $this->roleModel->insert(['name' => $role]); }
  private function loadRoles(): array { foreach (['admin','security','staff','compliance','auditor','office'] as $role) $this->ensureRoleExists($role); return array_map(static fn(array $row) => ['name' => (string) $row['name'], 'label' => ucwords(str_replace(['_','-'], ' ', (string) $row['name']))], $this->roleModel->orderBy('name', 'ASC')->findAll()); }

  public function index() { if ($redirect = $this->ensureAccess()) return $redirect; $users = $this->db->table('users')->select('id,name,username,email,photo,role,permission,status,wa_number,page_access,created_at')->orderBy('name', 'ASC')->get()->getResultArray(); return view('users/index', ['users'=>$users,'roles'=>$this->loadRoles(),'accessGroups'=>$this->accessGroups(),'title'=>'Manajemen User']); }
  public function create() { if ($redirect = $this->ensureAccess()) return $redirect; return view('users/create', ['title'=>'Tambah User','roles'=>$this->loadRoles(),'accessGroups'=>$this->accessGroups()]); }
  public function store()
  {
    if ($redirect = $this->ensureAccess()) return $redirect;
    $rawEmail = trim((string) $this->request->getPost('email')); $email = $this->normalizeEmail($rawEmail); if ($rawEmail !== '' && $email === null) return redirect()->back()->withInput()->with('error', 'Email tidak valid.');
    $role = $this->normalizeRole($this->request->getPost('role')); $pageAccess = normalize_page_access($this->request->getPost('page_access')); if ($pageAccess === []) return redirect()->back()->withInput()->with('error', 'Pilih minimal satu halaman untuk user ini.'); $this->ensureRoleExists($role);
    $this->db->table('users')->insert(['name'=>trim((string)$this->request->getPost('name')),'username'=>trim((string)$this->request->getPost('username')),'email'=>$email,'password'=>password_hash((string)$this->request->getPost('password'), PASSWORD_DEFAULT),'role'=>$role,'permission'=>$this->request->getPost('permission'),'wa_number'=>$this->normalizePhone($this->request->getPost('wa_number')),'page_access'=>$this->normalizePageAccess($pageAccess),'status'=>'active','created_at'=>date('Y-m-d H:i:s')]);
    audit_log('create_user', 'Membuat user ' . $this->request->getPost('username')); return redirect()->to('users')->with('success', 'User berhasil ditambahkan');
  }
  public function storeRole() { if ($redirect = $this->ensureAccess()) return $redirect; $role = $this->normalizeRole($this->request->getPost('name'), null); if ($role === '') return redirect()->back()->with('error','Nama role wajib diisi.'); if ($this->roleModel->where('name',$role)->first()) return redirect()->back()->with('warning','Role sudah ada.'); $this->roleModel->insert(['name'=>$role]); audit_log('create_role','Menambahkan role '.$role); return redirect()->to('users')->with('success','Role berhasil ditambahkan'); }
  public function edit($id) { if ($redirect = $this->ensureAccess()) return $redirect; $user = $this->db->table('users')->select('id,name,username,email,photo,role,permission,status,wa_number,page_access,created_at')->where('id',$id)->get()->getRowArray(); if (!$user) return redirect()->to('/users')->with('error','User tidak ditemukan'); return view('users/edit',['user'=>$user,'roles'=>$this->loadRoles(),'accessGroups'=>$this->accessGroups(),'title'=>'Edit User']); }
  public function update($id)
  {
    if ($redirect = $this->ensureAccess()) return $redirect; $user = $this->db->table('users')->where('id',$id)->get()->getRowArray(); if (!$user) return redirect()->to('/users')->with('error','User tidak ditemukan');
    $rawEmail = trim((string)$this->request->getPost('email')); $email = $this->normalizeEmail($rawEmail); if ($rawEmail !== '' && $email === null) return redirect()->back()->withInput()->with('error','Email tidak valid.');
    $role = $this->normalizeRole($this->request->getPost('role')); $pageAccess = normalize_page_access($this->request->getPost('page_access')); if ($pageAccess === []) return redirect()->back()->withInput()->with('error','Pilih minimal satu halaman untuk user ini.'); $this->ensureRoleExists($role);
    $data = ['name'=>trim((string)$this->request->getPost('name')),'username'=>trim((string)$this->request->getPost('username')),'email'=>$email,'role'=>$role,'permission'=>$this->request->getPost('permission'),'status'=>$this->request->getPost('status'),'wa_number'=>$this->normalizePhone($this->request->getPost('wa_number')),'page_access'=>$this->normalizePageAccess($pageAccess)]; if ($this->request->getPost('password')) $data['password'] = password_hash((string)$this->request->getPost('password'), PASSWORD_DEFAULT);
    $file = $this->request->getFile('photo'); if ($file && $file->isValid() && !$file->hasMoved()) { if ($file->getSize() > 2097152 || !in_array($file->getMimeType(),['image/jpeg','image/png','image/webp'],true)) return redirect()->back()->with('error','Foto harus JPG, PNG, atau WEBP maksimal 2MB'); $dir=FCPATH.'uploads/users/'; if(!is_dir($dir)) mkdir($dir,0775,true); $newName=$file->getRandomName(); $file->move($dir,$newName); if(!empty($user['photo'])&&is_file($dir.$user['photo'])) unlink($dir.$user['photo']); $data['photo']=$newName; }
    $this->db->table('users')->where('id',$id)->update($data); if((int)session()->get('user_id')===(int)$id){$sessionData=['name'=>$data['name'],'role'=>$data['role'],'permission'=>$data['permission'],'page_access'=>$data['page_access']];if(isset($data['photo']))$sessionData['photo']=$data['photo'];session()->set($sessionData);} audit_log('update_user','Mengubah user '.$data['username']); return redirect()->to('users')->with('success','User berhasil diupdate');
  }
  public function deactivate($id) { if ($redirect=$this->ensureAccess()) return $redirect; $this->db->table('users')->where('id',$id)->update(['status'=>'inactive']); return redirect()->to('users')->with('success','User dinonaktifkan'); }
  public function activate($id) { if ($redirect=$this->ensureAccess()) return $redirect; $this->db->table('users')->where('id',$id)->update(['status'=>'active']); return redirect()->to('users')->with('success','User diaktifkan'); }
}
