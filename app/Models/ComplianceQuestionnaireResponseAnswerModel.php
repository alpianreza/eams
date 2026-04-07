<?php

namespace App\Models;

use CodeIgniter\Model;

class ComplianceQuestionnaireResponseAnswerModel extends Model
{
  protected $table = 'compliance_questionnaire_response_answers';
  protected $primaryKey = 'id';
  protected $allowedFields = [
    'response_id',
    'question_id',
    'answer_value',
  ];
  protected $useTimestamps = true;
  protected $createdField = 'created_at';
  protected $updatedField = '';
}
