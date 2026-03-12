<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class WriteFilter implements FilterInterface
{
  public function before(RequestInterface $request, $arguments = null)
  {
    if (! session()->get('logged_in')) {
      return redirect()->to('/login');
    }

    $role       = session()->get('role');
    $permission = session()->get('permission');

    if ($permission === 'read' && $role !== 'admin') {
      return redirect()->back()
        ->with('error', 'Anda hanya punya akses baca');
    }
  }

  public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
