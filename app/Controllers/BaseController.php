<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    protected $request;
    protected $helpers = ['url', 'compliance', 'period', 'role', 'access'];

    protected bool $isWritable = false;
    protected string $role = 'viewer';
    protected int $notifCount = 0;
    protected array $notifications = [];
    protected string $defaultTitle = 'Dashboard';

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        // ==============================
        // ROLE SYSTEM
        // ==============================
        $this->role = session()->get('role') ?? 'viewer';

        $this->isWritable = hasWriteAccess() && in_array($this->role, [
            'admin',
            'compliance'
        ], true);

        // ==============================
        // GLOBAL NOTIFICATION (SIDEBAR)
        // ==============================
        if (session()->get('logged_in')) {

            $inventoryModel = new \App\Models\ComplianceInventoryModel();
            $logModel       = new \App\Models\ChecklistLogModel();

            $userName  = preg_replace('/\s+/', ' ', trim(session('name')));
            $nameParts = explode(' ', $userName);
            $firstTwo  = implode(' ', array_slice($nameParts, 0, 2));

            // Ambil inventory berdasarkan PIC
            $inventories = $inventoryModel
                ->select('compliance_inventory.id, asset_item_types.checklist_frequency')
                ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id')
                ->like('compliance_inventory.pic', $firstTwo)
                ->findAll();

            $pending = 0;
            $late    = 0;

            foreach ($inventories as $inv) {

                $frequency = $inv['checklist_frequency'] ?? null;
                if (!$frequency) continue;

                $periodKey = generate_period_key($frequency);

                $exists = $logModel
                    ->where('inventory_id', $inv['id'])
                    ->where('period_key', $periodKey)
                    ->countAllResults();

                if ($exists == 0) {

                    $pending++;

                    if (is_period_late($frequency, $periodKey)) {
                        $late++;
                    }
                }
            }

            $this->notifCount = $pending + $late;

            if ($pending > 0) {
                $this->notifications[] = [
                    'icon' => 'bi bi-clock text-warning',
                    'text' => $pending . ' periode belum checklist',
                    'url'  => base_url('home'),
                ];
            }

            if ($late > 0) {
                $this->notifications[] = [
                    'icon' => 'bi bi-exclamation-triangle text-danger',
                    'text' => $late . ' periode sudah terlambat',
                    'url'  => base_url('home'),
                ];
            }
        }

        $this->defaultTitle = $this->resolveDefaultTitle();

        // Share ke semua view
        service('renderer')->setVar('defaultTitle', $this->defaultTitle);
        service('renderer')->setVar('notifCount', $this->notifCount);
        service('renderer')->setVar('notifications', $this->notifications);
    }

    protected function render(string $view, array $data = [])
    {
        $data['defaultTitle'] = $data['defaultTitle'] ?? $this->defaultTitle;
        $data['isWritable'] = $this->isWritable;
        $data['role']       = $this->role;
        $data['notifCount'] = $this->notifCount;
        $data['notifications'] = $this->notifications;

        return view($view, $data);
    }

    protected function resolveDefaultTitle(): string
    {
        $router = service('router');
        $controller = $router->controllerName();
        $method = $router->methodName();

        if (!is_string($controller) || $controller === '') {
            $controller = static::class;
        }

        $parts = explode('\\', $controller);
        $short = end($parts) ?: '';
        $short = preg_replace('/Controller$/', '', $short) ?? $short;
        $short = str_replace(['_', '-'], ' ', $short);
        $short = preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', $short) ?? $short;
        $short = preg_replace('/(?<=[A-Za-z])(?=[0-9])|(?<=[0-9])(?=[A-Za-z])/', ' ', $short) ?? $short;
        $short = trim(preg_replace('/\s+/', ' ', $short) ?? $short);

        if ($short === '' || strtolower($short) === 'base') {
            return 'Dashboard';
        }

        $methodText = '';
        if (is_string($method) && $method !== '' && strtolower($method) !== 'index') {
            $methodText = str_replace(['_', '-'], ' ', $method);
            $methodText = preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', $methodText) ?? $methodText;
            $methodText = trim(preg_replace('/\s+/', ' ', $methodText) ?? $methodText);
        }

        return trim($short . ' ' . $methodText);
    }
}
