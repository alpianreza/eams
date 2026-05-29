<?php

namespace App\Models;

use CodeIgniter\Model;

class ThermalImagingReportModel extends Model
{
  protected $table = 'thermal_imaging_reports';
  protected $primaryKey = 'id';

  protected $allowedFields = [
    'inspection_date',
    'inspector_name',
    'facility',
    'area_name',
    'created_by',
  ];

  protected $useTimestamps = true;
}
