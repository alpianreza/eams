<?php

namespace App\Controllers;

use App\Models\UserModel;

class AuthController extends BaseController
{
    protected $userModel;

    /**
     * Maksimum percobaan login per menit, per alamat IP.
     */
    private const LOGIN_MAX_ATTEMPTS = 5;

    /**
     * Hash dummy untuk menyamakan waktu eksekusi ketika username tidak
     * ditemukan, agar tidak bisa dipakai menebak username yang valid.
     */
    private const DUMMY_HASH = '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';

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
        // ==============================
        // RATE LIMIT (anti brute force)
        // ==============================
        $throttler = service('throttler');
        $bucket    = 'login_' . $this->request->getIPAddress();

        if ($throttler->check($bucket, self::LOGIN_MAX_ATTEMPTS, MINUTE) === false) {
            return redirect()->back()->with(
                'error',
                'Terlalu banyak percobaan login. Coba lagi dalam ' . $throttler->getTokenTime() . ' detik.'
            );
        }

        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        // Pesan seragam supaya tidak bocor mana username yang valid.
        $genericError = 'Username atau password salah.';

        $user = $this->userModel
            ->where('username', $username)
            ->where('status', 'active')
            ->first();

        if (! $user) {
            // Tetap jalankan verifikasi agar durasi respons konsisten.
            password_verify($password, self::DUMMY_HASH);

            return redirect()->back()->with('error', $genericError);
        }

        if (! password_verify($password, $user['password'])) {
            return redirect()->back()->with('error', $genericError);
        }

        // ==============================
        // LOGIN BERHASIL
        // ==============================
        // Cegah session fixation: ganti session ID sebelum menaruh data user.
        session()->regenerate(true);

        session()->set([
            'logged_in'   => true,
            'user_id'     => $user['id'],
            'name'        => $user['name'],
            'role'        => $user['role'],
            'photo'       => $user['photo'] ?? null,
            'permission'  => $user['permission'],
            'page_access' => $user['page_access'] ?? null,
        ]);

        // Reset hitungan percobaan login untuk IP ini.
        cache()->delete($bucket);

        // cek redirect dari filter (misal scan QR)
        $redirect = session()->get('redirect_after_login');
        session()->remove('redirect_after_login');

        if ($redirect) {
            return redirect()->to($redirect);
        }

        // default
        return redirect()->to(resolve_default_landing_url());
    }

    // logout
    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login');
    }
}
