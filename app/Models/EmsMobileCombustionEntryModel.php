<?php

namespace App\Models;

use CodeIgniter\Model;

class EmsMobileCombustionEntryModel extends Model
{
    protected $table = 'ems_mobile_combustion_entries';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'report_year',
        'section_key',
        'report_month',
        'consumption_amount',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
