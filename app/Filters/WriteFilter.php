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

    $method = strtoupper((string) $request->getMethod());
    if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
      return null;
    }

    $path = '/' . ltrim($request->getUri()->getPath(), '/');
    if ($this->isPublicWritePath($path)) {
      return null;
    }

    if (! session()->get('logged_in')) {
      return null;
    }

    if (isReadOnlyAccess()) {
      $message = 'Akses read only hanya bisa membaca data.';

      if ($this->expectsJson($request)) {
        return service('response')
          ->setStatusCode(403)
          ->setJSON([
            'success' => false,
            'message' => $message,
          ]);
      }

      return redirect()->back()->with('error', $message);
    }

    return null;
  }

  public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}

  private function isPublicWritePath(string $path): bool
  {
    $publicPrefixes = [
      '/login',
      '/api/agent',
      '/kuesioner',
      '/logstores',
    ];

    foreach ($publicPrefixes as $prefix) {
      if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
        return true;
      }
    }

    return false;
  }

  private function expectsJson(RequestInterface $request): bool
  {
    if (method_exists($request, 'isAJAX') && $request->isAJAX()) {
      return true;
    }

    $accept = strtolower((string) $request->getHeaderLine('Accept'));
    $contentType = strtolower((string) $request->getHeaderLine('Content-Type'));

    return str_contains($accept, 'application/json') || str_contains($contentType, 'application/json');
  }
}
