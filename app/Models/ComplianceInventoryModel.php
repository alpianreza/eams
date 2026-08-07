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
  protected $afterInsert = ['syncPicRelations', 'notifyPicAssignment'];
  protected $afterUpdate = ['syncPicRelations', 'notifyPicAssignment'];
  protected $afterFind = ['hydratePicRelations'];

  public function assignedToUser(int $userId): self
  {
    if ($userId > 0 && $this->db->tableExists('compliance_inventory_pics')) {
      return $this->join('compliance_inventory_pics assigned_pic', 'assigned_pic.inventory_id = compliance_inventory.id')->where('assigned_pic.user_id', $userId)->groupBy('compliance_inventory.id');
    }
    $name = trim((string) session()->get('name'));
    return $name !== '' ? $this->like('compliance_inventory.pic', $name) : $this->where('compliance_inventory.id', 0);
  }

  protected function limitPicUsers(array $event): array
  {
    if (! array_key_exists('pic', $event['data'] ?? [])) return $event;
    $event['data']['pic'] = implode(' - ', array_slice($this->parsePicNames((string) $event['data']['pic']), 0, 2));
    return $event;
  }

  protected function syncPicRelations(array $event): array
  {
    if (! $this->db->tableExists('compliance_inventory_pics') || ! array_key_exists('pic', $event['data'] ?? [])) return $event;
    $rawId = $event['id'] ?? null;
    $inventoryId = is_array($rawId) ? (int) reset($rawId) : (int) $rawId;
    if ($inventoryId < 1) return $event;
    $names = array_slice($this->parsePicNames((string) $event['data']['pic']), 0, 2);
    $this->db->table('compliance_inventory_pics')->where('inventory_id', $inventoryId)->delete();
    foreach ($names as $position => $name) {
      $user = $this->db->table('users')->select('id')->where('name', $name)->where('status', 'active')->get()->getRowArray();
      if ($user) $this->db->table('compliance_inventory_pics')->insert(['inventory_id' => $inventoryId, 'user_id' => (int) $user['id'], 'is_primary' => $position === 0 ? 1 : 0, 'created_at' => date('Y-m-d H:i:s')]);
    }
    return $event;
  }

  protected function hydratePicRelations(array $event): array
  {
    if (! isset($event['data']) || ! is_array($event['data'])) return $event;
    if (array_key_exists('id', $event['data'])) {
      $event['data'] = $this->hydrateRow($event['data']);
      return $event;
    }
    foreach ($event['data'] as &$row) if (is_array($row) && isset($row['id'])) $row = $this->hydrateRow($row);
    unset($row);
    return $event;
  }

  private function hydrateRow(array $row): array
  {
    if (! $this->db->tableExists('compliance_inventory_pics')) return $row;
    $pics = $this->db->table('compliance_inventory_pics cip')->select('u.id, u.name, cip.is_primary')->join('users u', 'u.id = cip.user_id')->where('cip.inventory_id', (int) $row['id'])->orderBy('cip.is_primary', 'DESC')->orderBy('cip.id', 'ASC')->get()->getResultArray();
    if ($pics !== []) {
      $row['pic'] = implode(' - ', array_column($pics, 'name'));
      $row['pic_users'] = $pics;
      $row['pic_user_ids'] = array_map('intval', array_column($pics, 'id'));
    }
    return $row;
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
      (new NotificationService())->sendToUser((int) $user['id'], ['type' => 'assignment', 'title' => 'Penugasan inventory baru', 'message' => 'Anda ditetapkan sebagai PIC untuk ' . $assetCode . '.', 'url' => '/compliance/inventory/detail/' . $inventoryId, 'entity_type' => 'compliance_inventory', 'entity_id' => $inventoryId, 'dedupe_key' => 'inventory_assignment:' . $inventoryId . ':' . (int) $user['id']]);
      cache()->delete('sidebar_notif_' . (int) $user['id']);
    }
    return $event;
  }

  private function parsePicNames(string $value): array
  {
    $names = preg_split('/\s*(?:\r\n|\r|\n|,|\s+-\s+)\s*/', trim($value)) ?: [];
    return array_values(array_unique(array_filter(array_map('trim', $names), static fn($name) => $name !== '')));
  }

  public function getBaseQuery()
  {
    return $this->select(['compliance_inventory.*', 'inventory_categories.name AS category_name', 'asset_item_types.name AS item_display_name', 'areas.name AS area_name'])->join('inventory_categories', 'inventory_categories.id = compliance_inventory.category_id')->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_name', 'left')->join('areas', 'areas.id = compliance_inventory.area_id', 'left')->orderBy('compliance_inventory.id', 'DESC');
  }

  public function getDetail($id) { return $this->getBaseQuery()->where('compliance_inventory.id', $id)->first(); }
}
