<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    protected $request;
    protected $helpers = ['url', 'compliance', 'period'];

    protected bool $isWritable = false;
    protected string $role = 'viewer';
    protected int $notifCount = 0;

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

        $this->isWritable = in_array($this->role, [
            'admin',
            'compliance'
        ]);

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
        }

        // Share ke semua view
        service('renderer')->setVar('notifCount', $this->notifCount);
    }

    protected function render(string $view, array $data = [])
    {
        $data['isWritable'] = $this->isWritable;
        $data['role']       = $this->role;
        $data['notifCount'] = $this->notifCount;

        return view($view, $data);
    }
}
