<?php

namespace App\Models;

use CodeIgniter\Model;

class FdmProductionSectionYearModel extends Model
{
    protected $table = 'fdm_production_section_years';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'report_year',
    ];
    protected $useTimestamps = true;
}
