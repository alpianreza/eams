<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Config\PdfPermission;

class PdfAccessFilter implements FilterInterface
{
  public function before(RequestInterface $request, $arguments = null)
  {
    $role = session('role');

    if (! in_array($role, PdfPermission::$allowedRoles)) {
      return redirect()->back()
        ->with('error', 'Anda tidak memiliki akses untuk mencetak PDF');
    }
  }

  public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
  {
    // no-op
  }
}
