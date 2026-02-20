<?php

namespace App\Controllers;

use App\Models\UserModel;

class AuthController extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    // tampilkan form login
    public function login()
    {
        return view('auth/login');
    }

    // proses login
    public function doLogin()
    {

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $user = $this->userModel
            ->where('username', $username)
            ->where('status', 'active')
            ->first();

        if (! $user) {
            return redirect()->back()->with('error', 'Username tidak ditemukan');
        }

        if (! password_verify($password, $user['password'])) {
            return redirect()->back()->with('error', 'Password salah');
        }

        session()->set([
            'logged_in' => true,
            'user_id'   => $user['id'],
            'name'      => $user['name'],
            'role'      => $user['role'],
            'photo'     => $user['photo'] ?? null,
            'permission' => $user['permission']
        ]);

        // PRIORITAS 1: Kalau sebelumnya diarahkan filter
        $redirect = session()->get('redirect_after_login');
        session()->remove('redirect_after_login');

        if ($redirect) {
            return redirect()->to($redirect);
        }

        // PRIORITAS 2: Redirect berdasarkan role
        if (in_array($user['role'], ['staff', 'compliance'])) {
            return redirect()->to('/home');
        }

        if (in_array($user['role'], ['admin', 'auditor'])) {
            return redirect()->to('/compliance/dashboard');
        }

        // fallback
        return redirect()->to('/');
    }

    // logout
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }
}
