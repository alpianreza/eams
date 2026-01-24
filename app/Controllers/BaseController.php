<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    protected $request;
    protected $helpers = ['url', 'compliance'];

    protected bool $isWritable = false;
    protected string $role = 'viewer';

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        // Ambil role dari session
        $this->role = session()->get('role') ?? 'viewer';

        // Role yang boleh nulis
        $this->isWritable = in_array($this->role, [
            'admin',
            'compliance'
        ]);
    }

    protected function render(string $view, array $data = [])
    {
        $data['isWritable'] = $this->isWritable;
        $data['role']       = $this->role;

        return view($view, $data);
    }
}
