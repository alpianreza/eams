<?php

namespace App\Models;

use CodeIgniter\Model;

class AssetItemTypeModel extends Model
{
  protected $table = 'asset_item_types';
  protected $primaryKey = 'id';

  protected $allowedFields = [
    'category_id',
    'name',
    'code',
    'checklist_frequency',
    'active'
  ];
}
