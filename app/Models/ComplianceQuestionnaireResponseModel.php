<?php

namespace App\Models;

use CodeIgniter\Model;

class ComplianceQuestionnaireResponseModel extends Model
{
  protected $table = 'compliance_questionnaire_responses';
  protected $primaryKey = 'id';
  protected $allowedFields = [
    'questionnaire_id',
    'response_code',
    'respondent_name',
    'birth_date',
    'phone',
    'email',
    'submitted_at',
    'created_by',
  ];
  protected $useTimestamps = true;
  protected $createdField = 'created_at';
  protected $updatedField = '';
}
