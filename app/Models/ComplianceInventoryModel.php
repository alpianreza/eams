<?php

namespace App\Models;

use CodeIgniter\Model;

class ComplianceInventoryModel extends Model
{
  protected $table      = 'compliance_inventory';
  protected $primaryKey = 'id';

  protected $allowedFields = [
    'category_id',
    'area_id',
    'item_type_id',
    'asset_code',
    'type_description',
    'specific_area',
    'pic',
    'status',
    'qty',
    'remark',
    'expired_date',
    'photo',
    'qr_image'
  ];

  protected $useTimestamps = true;

  /**
   * BASE QUERY (UNTUK LIST + FILTER)
   */
  public function getBaseQuery()
  {
    return $this->select([
      'compliance_inventory.*',
      'inventory_categories.name AS category_name',
      'asset_item_types.name AS item_display_name',
      'areas.name AS area_name'
    ])
      ->join(
        'inventory_categories',
        'inventory_categories.id = compliance_inventory.category_id'
      )
      ->join(
        'asset_item_types',
        'asset_item_types.id = compliance_inventory.item_name',
        'left'
      )
      ->join(
        'areas',
        'areas.id = compliance_inventory.area_id',
        'left'
      )
      ->orderBy('compliance_inventory.id', 'DESC');
  }

  /**
   * DETAIL INVENTORY
   */
  public function getDetail($id)
  {
    return $this->getBaseQuery()
      ->where('compliance_inventory.id', $id)
      ->first();
  }
}
