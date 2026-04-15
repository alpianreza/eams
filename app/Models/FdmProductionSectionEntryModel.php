<?php

namespace App\Models;

use CodeIgniter\Model;

class FdmProductionSectionEntryModel extends Model
{
    protected $table = 'fdm_production_section_entries';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'year_id',
        'section_key',
        'section_label',
        'entry_type',
        'frequency_label',
        'logo_path',
        'display_order',
        'monthly_values',
    ];
    protected $useTimestamps = true;
}
