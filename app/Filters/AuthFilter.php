<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
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
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {}
}
