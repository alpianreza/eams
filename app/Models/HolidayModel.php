<?php

namespace App\Models;

use CodeIgniter\Model;

class HolidayModel extends Model
{
  protected $table = 'holidays';
  protected $primaryKey = 'id';

  protected $allowedFields = [
    'holiday_date',
    'description'
  ];
}
