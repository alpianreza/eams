<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class WriteFilter implements FilterInterface
{
  public function before(RequestInterface $request, $arguments = null)
  {
    helper('access');

    if (! session()->get('logged_in')) {
      return redirect()->to('/login');
    }

    if (! hasWriteAccess()) {
      return redirect()->back()
        ->with('error', 'Anda hanya punya akses baca');
    }
  }

  public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
