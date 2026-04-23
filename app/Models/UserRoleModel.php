<?php

namespace App\Models;

use CodeIgniter\Model;

class UserRoleModel extends Model
{
  protected $table      = 'user_roles';
  protected $primaryKey = 'id';
  protected $allowedFields = ['name'];
  protected $useTimestamps = true;
  protected $createdField = 'created_at';
  protected $updatedField = 'updated_at';
}
