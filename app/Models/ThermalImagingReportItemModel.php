<?php

namespace App\Models;

use CodeIgniter\Model;

class ThermalImagingReportItemModel extends Model
{
  protected $table = 'thermal_imaging_report_items';
  protected $primaryKey = 'id';

  protected $allowedFields = [
    'report_id',
    'location_id',
    'location_name',
    'celsius',
    'thermal_image',
    'findings',
    'recommendation',
    'sort_order',
  ];

  protected $useTimestamps = true;
}
