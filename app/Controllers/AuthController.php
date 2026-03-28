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
        // jika sudah login jangan boleh kembali ke halaman login
        if (session()->get('logged_in')) {
            return redirect()->to('/home');
        }

        // cegah browser cache halaman login
        $response = service('response');
        $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->setHeader('Pragma', 'no-cache');

        return view('auth/login');
    }

    public function doLogin()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $user = $this->userModel
            ->where('username', $username)
            ->where('status', 'active')
            ->first();

        if (!$user) {
            return redirect()->back()->with('error', 'Username tidak ditemukan');
        }

        if (!password_verify($password, $user['password'])) {
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

        // cek redirect dari filter (misal scan QR)
        $redirect = session()->get('redirect_after_login');
        session()->remove('redirect_after_login');

        if ($redirect) {
            return redirect()->to($redirect);
        }

        // default
        return redirect()->to('/home');
    }
    // logout
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }
}
