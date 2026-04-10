<?php

namespace App\Models;

use CodeIgniter\Model;

class EmsWaterConsumptionEntryModel extends Model
{
    protected $table = 'ems_water_consumption_entries';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'report_year',
        'report_month',
        'consumption_m3',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}