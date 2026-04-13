<?php

namespace App\Models;

use CodeIgniter\Model;

class EmsElectricConsumptionEntryModel extends Model
{
    protected $table = 'ems_electric_consumption_entries';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'report_year',
        'report_month',
        'consumption_kwh',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
