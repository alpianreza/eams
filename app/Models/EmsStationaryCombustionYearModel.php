<?php

namespace App\Models;

use CodeIgniter\Model;

class EmsStationaryCombustionYearModel extends Model
{
    protected $table = 'ems_stationary_combustion_years';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'report_year',
        'production_output',
        'notes',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
