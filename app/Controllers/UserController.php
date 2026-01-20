<?php

namespace App\Controllers;

use Config\Database;

class UserController extends BaseController
{
  protected $db;

  public function __construct()
  {
    $this->db = Database::connect();
  }

  // LIST USER
  public function index()
  {
    $users = $this->db->table('users')->get()->getResultArray();

    return view('users/index', [
      'users' => $users,
      'title' => 'Manajemen User'
    ]);
  }

  // FORM TAMBAH
  public function create()
  {
    return view('users/create', [
      'title' => 'Tambah User'
    ]);
  }

  // SIMPAN USER
  public function store()
  {
    $this->db->table('users')->insert([
      'name'       => $this->request->getPost('name'),
      'username'   => $this->request->getPost('username'),
      'password'   => password_hash(
        $this->request->getPost('password'),
        PASSWORD_DEFAULT
      ),
      'role'       => $this->request->getPost('role'),
      'permission' => $this->request->getPost('permission'),
      'status'     => 'active',
      'created_at' => date('Y-m-d H:i:s')
    ]);

    audit_log(
      'create_user',
      'Membuat user ' . $this->request->getPost('username')
    );


    return redirect()->to('users')
      ->with('success', 'User berhasil ditambahkan');
  }

  // FORM EDIT
  public function edit($id)
  {
    $user = $this->db->table('users')
      ->where('id', $id)
      ->get()
      ->getRowArray();

    return view('users/edit', [
      'user'  => $user,
      'title' => 'Edit User'
    ]);
  }

  // UPDATE USER
  public function update($id)
  {
    $data = [
      'name'       => $this->request->getPost('name'),
      'username'   => $this->request->getPost('username'),
      'role'       => $this->request->getPost('role'),
      'permission' => $this->request->getPost('permission'),
      'status'     => $this->request->getPost('status')
    ];

    // password optional
    if ($this->request->getPost('password')) {
      $data['password'] = password_hash(
        $this->request->getPost('password'),
        PASSWORD_DEFAULT
      );
    }

    $this->db->table('users')
      ->where('id', $id)
      ->update($data);

    return redirect()->to('users')
      ->with('success', 'User berhasil diupdate');
  }

  // NONAKTIFKAN USER
  public function deactivate($id)
  {
    $this->db->table('users')
      ->where('id', $id)
      ->update(['status' => 'inactive']);

    return redirect()->to('users')
      ->with('success', 'User dinonaktifkan');
  }
}
