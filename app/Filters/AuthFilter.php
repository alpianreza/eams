<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('access');

        if (! session()->get('logged_in')) {

            // 🔥 KHUSUS AJAX (JANGAN REDIRECT HTML)
            if (service('request')->isAJAX()) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON([
                        'message' => 'Session expired'
                    ]);
            }

            // ✅ REQUEST BIASA (SCAN BARCODE AMAN)
            session()->set('redirect_after_login', current_url());

            return redirect()->to('/login');
        }

        $path = '/' . ltrim($request->getUri()->getPath(), '/');
        $pageKey = resolve_page_key_from_path($path);

        if ($pageKey !== null && ! canAccessPage($pageKey)) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(403)
                    ->setJSON([
                        'message' => 'Akses halaman ditolak',
                    ]);
            }

            return redirect()->to(resolve_default_landing_url())
                ->with('error', 'Halaman ini tidak diizinkan untuk user tersebut.');
        }
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {}
}
