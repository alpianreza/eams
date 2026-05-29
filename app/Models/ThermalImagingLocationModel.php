<?php

namespace App\Models;

use CodeIgniter\Model;

class ThermalImagingLocationModel extends Model
{
  protected $table = 'thermal_imaging_locations';
  protected $primaryKey = 'id';

  protected $allowedFields = [
    'name',
    'section',
    'active',
    'created_by',
  ];

  protected $useTimestamps = true;
}
