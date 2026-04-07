<?php

namespace App\Models;

use CodeIgniter\Model;

class ComplianceQuestionnaireQuestionModel extends Model
{
  protected $table = 'compliance_questionnaire_questions';
  protected $primaryKey = 'id';
  protected $allowedFields = [
    'questionnaire_id',
    'section_label',
    'question_code',
    'sort_order',
    'question_text',
    'answer_type',
    'options_json',
    'scale_low_label',
    'scale_high_label',
    'placeholder',
    'help_text',
    'is_required',
  ];
  protected $useTimestamps = true;
  protected $createdField = 'created_at';
  protected $updatedField = 'updated_at';
}
