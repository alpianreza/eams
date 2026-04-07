<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddQuestionnaireScaleFields extends Migration
{
  public function up()
  {
    $this->forge->addColumn('compliance_questionnaire_questions', [
      'scale_low_label' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
        'null' => true,
        'after' => 'options_json',
      ],
      'scale_high_label' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
        'null' => true,
        'after' => 'scale_low_label',
      ],
    ]);

    $this->db->query(
      "ALTER TABLE compliance_questionnaire_questions 
       MODIFY answer_type ENUM('radio','text','textarea','date','email','phone','number','select','scale_5','scale_10') NOT NULL DEFAULT 'radio'"
    );
  }

  public function down()
  {
    $this->db->query(
      "ALTER TABLE compliance_questionnaire_questions 
       MODIFY answer_type ENUM('radio','text','textarea','date','email','phone','number','select') NOT NULL DEFAULT 'radio'"
    );

    $this->forge->dropColumn('compliance_questionnaire_questions', ['scale_low_label', 'scale_high_label']);
  }
}
