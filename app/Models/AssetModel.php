<?php

namespace App\Models;

use CodeIgniter\Model;

class AssetModel extends Model
{
    protected $table      = 'assets';
    protected $primaryKey = 'id';

    protected $allowedFields = [
    'inventory_no',
    'category_id',
    'asset_name',
    'brand',
    'serial_number',
    'purchase_date',
    'photo',
    'status',
    'location'
];

}
