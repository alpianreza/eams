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
  protected $beforeInsert = ['limitPicUsers'];
  protected $beforeUpdate = ['limitPicUsers'];
  protected $afterInsert = ['notifyPicAssignment'];
  protected $afterUpdate = ['notifyPicAssignment'];
  protected $afterFind = ['formatPicSeparator'];

  protected function limitPicUsers(array $event): array
  {
    if (! array_key_exists('pic', $event['data'] ?? [])) return $event;
    $event['data']['pic'] = implode(' - ', array_slice($this->parsePicNames((string) $event['data']['pic']), 0, 2));
    return $event;
  }

  protected function formatPicSeparator(array $event): array
  {
    if (! isset($event['data']) || ! is_array($event['data'])) return $event;
    if (array_key_exists('pic', $event['data'])) {
      $event['data']['pic'] = implode(' - ', array_slice($this->parsePicNames((string) $event['data']['pic']), 0, 2));
      return $event;
    }
    foreach ($event['data'] as &$row) {
      if (is_array($row) && array_key_exists('pic', $row)) $row['pic'] = implode(' - ', array_slice($this->parsePicNames((string) $row['pic']), 0, 2));
    }
    unset($row);
    return $event;
  }

  protected function notifyPicAssignment(array $event): array
  {
    $picNames = $this->parsePicNames((string) ($event['data']['pic'] ?? ''));
    if ($picNames === []) return $event;
    $rawId = $event['id'] ?? null;
    $inventoryId = is_array($rawId) ? (int) reset($rawId) : (int) $rawId;
    if ($inventoryId < 1) return $event;

    $assetCode = trim((string) ($event['data']['asset_code'] ?? '')) ?: ('Inventory #' . $inventoryId);
    $users = (new UserModel())->whereIn('name', $picNames)->where('status', 'active')->findAll();
    foreach ($users as $user) {
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
    }
    return $event;
  }

  private function parsePicNames(string $value): array
  {
    $names = preg_split('/\s*(?:\r\n|\r|\n|,|\s+-\s+)\s*/', trim($value)) ?: [];
    $names = array_values(array_filter(array_map('trim', $names), static fn($name) => $name !== ''));
    return array_values(array_unique($names));
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
