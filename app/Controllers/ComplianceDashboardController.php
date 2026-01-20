<?php

namespace App\Controllers;

use App\Models\ComplianceInventoryModel;
use App\Models\ComplianceChecklistLogModel;

class ComplianceDashboardController extends BaseController
{
  protected $inventoryModel;
  protected $logModel;

  public function __construct()
  {
    $this->inventoryModel = new ComplianceInventoryModel();
    $this->logModel       = new ComplianceChecklistLogModel();
  }

  public function index()
  {
    $category = $this->request->getGet('category');
    $location = $this->request->getGet('location');

    $builder = $this->inventoryModel->where('active', 1);

    if ($category) {
      $builder->where('category', $category);
    }

    if ($location) {
      $builder->where('location', $location);
    }

    $inventories = $builder->findAll();

    $summary = [
      'total_inventory' => count($inventories),
      'ok' => 0,
      'due' => 0,
      'overdue' => 0,
    ];

    foreach ($inventories as $inv) {
      $checklists = $this->logModel
        ->getLastChecksByInventory($inv['id']);

      foreach ($checklists as $c) {
        $result = checklist_status(
          $c['last_check'],
          $c['period']
        );

        if ($result['status'] === 'OK') {
          $summary['ok']++;
        } elseif ($result['status'] === 'DUE') {
          $summary['due']++;
        } else {
          $summary['overdue']++;
        }
      }
    }

    // ambil dropdown data
    $categories = $this->inventoryModel
      ->select('category')
      ->distinct()
      ->findAll();

    $locations = $this->inventoryModel
      ->select('location')
      ->distinct()
      ->findAll();

    return $this->render('compliance/dashboard/index', [
      'title'      => 'Compliance Dashboard',
      'summary'    => $summary,
      'categories' => $categories,
      'locations'  => $locations,
      'selectedCategory' => $category,
      'selectedLocation' => $location,
    ]);
  }


  public function overdue()
  {
    $inventories = $this->inventoryModel->getActive();
    $overdues = [];

    foreach ($inventories as $inv) {
      $checklists = $this->logModel
        ->getLastChecksByInventory($inv['id']);

      foreach ($checklists as $c) {
        $result = checklist_status(
          $c['last_check'],
          $c['period']
        );

        if ($result['status'] === 'OVERDUE') {
          $overdues[] = [
            'inventory_id' => $inv['id'],
            'asset_type'   => $inv['asset_type'],
            'asset_code'   => $inv['asset_code'],
            'location'     => $inv['location'],
            'checklist'    => $c['name'],
            'period'       => $c['period'],
            'last_check'   => $c['last_check'],
          ];
        }
      }
    }

    return $this->render('compliance/dashboard/overdue', [
      'title'    => 'Checklist Overdue',
      'overdues' => $overdues,
    ]);
  }
}
