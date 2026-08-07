<?php

namespace App\Models;

use App\Libraries\NotificationService;
use CodeIgniter\Model;

class ComplianceInventoryModel extends Model
{
  protected $table = 'compliance_inventory';
  protected $primaryKey = 'id';
  protected $allowedFields = ['category_id', 'area_id', 'item_type_id', 'asset_code', 'type_description', 'specific_area', 'pic', 'status', 'qty', 'remark', 'expired_date', 'photo', 'qr_image'];
  protected $useTimestamps = true;
  protected $afterInsert = ['notifyPicAssignment'];
  protected $afterUpdate = ['notifyPicAssignment'];

  protected function notifyPicAssignment(array $event): array
  {
    $pic = trim((string) ($event['data']['pic'] ?? ''));
    if ($pic === '') return $event;

    $user = (new UserModel())->where('name', $pic)->where('status', 'active')->first();
    if (! $user) return $event;

    $rawId = $event['id'] ?? null;
    $inventoryId = is_array($rawId) ? (int) reset($rawId) : (int) $rawId;
    if ($inventoryId < 1) return $event;

    $assetCode = trim((string) ($event['data']['asset_code'] ?? '')) ?: ('Inventory #' . $inventoryId);
    (new NotificationService())->sendToUser((int) $user['id'], [
      'type' => 'assignment',
      'title' => 'Penugasan inventory baru',
      'message' => 'Anda ditetapkan sebagai PIC untuk ' . $assetCode . '.',
      'url' => '/compliance/inventory/detail/' . $inventoryId,
      'entity_type' => 'compliance_inventory',
      'entity_id' => $inventoryId,
      'dedupe_key' => 'inventory_assignment:' . $inventoryId . ':' . (int) $user['id'],
    ]);

    cache()->delete('sidebar_notif_' . (int) $user['id']);
    return $event;
  }

  public function getBaseQuery()
  {
    return $this->select(['compliance_inventory.*', 'inventory_categories.name AS category_name', 'asset_item_types.name AS item_display_name', 'areas.name AS area_name'])
      ->join('inventory_categories', 'inventory_categories.id = compliance_inventory.category_id')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_name', 'left')
      ->join('areas', 'areas.id = compliance_inventory.area_id', 'left')
      ->orderBy('compliance_inventory.id', 'DESC');
  }

  public function getDetail($id)
  {
    return $this->getBaseQuery()->where('compliance_inventory.id', $id)->first();
  }
}
