<?php

namespace App\Database\Migrations;

use App\Libraries\ComplianceQuestionnaireCatalog;
use CodeIgniter\Database\Migration;

class CreateComplianceQuestionnaires extends Migration
{
  public function up()
  {
    $this->forge->addField([
      'id' => [
        'type' => 'INT',
        'unsigned' => true,
        'auto_increment' => true,
      ],
      'slug' => [
        'type' => 'VARCHAR',
        'constraint' => 160,
      ],
      'title' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
      ],
      'subtitle' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
        'null' => true,
      ],
      'description' => [
        'type' => 'TEXT',
        'null' => true,
      ],
      'footer_note' => [
        'type' => 'TEXT',
        'null' => true,
      ],
      'active' => [
        'type' => 'TINYINT',
        'default' => 1,
      ],
      'sort_order' => [
        'type' => 'INT',
        'default' => 0,
      ],
      'created_at' => [
        'type' => 'DATETIME',
        'null' => true,
      ],
      'updated_at' => [
        'type' => 'DATETIME',
        'null' => true,
      ],
    ]);
    $this->forge->addKey('id', true);
    $this->forge->addUniqueKey('slug');
    $this->forge->createTable('compliance_questionnaires', true);

    $this->forge->addField([
      'id' => [
        'type' => 'INT',
        'unsigned' => true,
        'auto_increment' => true,
      ],
      'questionnaire_id' => [
        'type' => 'INT',
        'unsigned' => true,
      ],
      'section_label' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
        'null' => true,
      ],
      'question_code' => [
        'type' => 'VARCHAR',
        'constraint' => 30,
        'null' => true,
      ],
      'sort_order' => [
        'type' => 'INT',
        'default' => 0,
      ],
      'question_text' => [
        'type' => 'TEXT',
      ],
      'answer_type' => [
        'type' => 'ENUM',
        'constraint' => ['radio', 'text', 'textarea', 'date', 'email', 'phone', 'number', 'select'],
        'default' => 'radio',
      ],
      'options_json' => [
        'type' => 'LONGTEXT',
        'null' => true,
      ],
      'placeholder' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
        'null' => true,
      ],
      'help_text' => [
        'type' => 'TEXT',
        'null' => true,
      ],
      'is_required' => [
        'type' => 'TINYINT',
        'default' => 1,
      ],
      'created_at' => [
        'type' => 'DATETIME',
        'null' => true,
      ],
      'updated_at' => [
        'type' => 'DATETIME',
        'null' => true,
      ],
    ]);
    $this->forge->addKey('id', true);
    $this->forge->addKey('questionnaire_id');
    $this->forge->addForeignKey('questionnaire_id', 'compliance_questionnaires', 'id', 'CASCADE', 'CASCADE');
    $this->forge->createTable('compliance_questionnaire_questions', true);

    $this->forge->addField([
      'id' => [
        'type' => 'INT',
        'unsigned' => true,
        'auto_increment' => true,
      ],
      'questionnaire_id' => [
        'type' => 'INT',
        'unsigned' => true,
      ],
      'response_code' => [
        'type' => 'VARCHAR',
        'constraint' => 60,
      ],
      'respondent_name' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
      ],
      'birth_date' => [
        'type' => 'DATE',
        'null' => true,
      ],
      'phone' => [
        'type' => 'VARCHAR',
        'constraint' => 50,
        'null' => true,
      ],
      'email' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
        'null' => true,
      ],
      'submitted_at' => [
        'type' => 'DATETIME',
        'null' => true,
      ],
      'created_by' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
        'null' => true,
      ],
      'created_at' => [
        'type' => 'DATETIME',
        'null' => true,
      ],
    ]);
    $this->forge->addKey('id', true);
    $this->forge->addUniqueKey('response_code');
    $this->forge->addKey('questionnaire_id');
    $this->forge->addForeignKey('questionnaire_id', 'compliance_questionnaires', 'id', 'CASCADE', 'CASCADE');
    $this->forge->createTable('compliance_questionnaire_responses', true);

    $this->forge->addField([
      'id' => [
        'type' => 'INT',
        'unsigned' => true,
        'auto_increment' => true,
      ],
      'response_id' => [
        'type' => 'INT',
        'unsigned' => true,
      ],
      'question_id' => [
        'type' => 'INT',
        'unsigned' => true,
      ],
      'answer_value' => [
        'type' => 'TEXT',
        'null' => true,
      ],
      'created_at' => [
        'type' => 'DATETIME',
        'null' => true,
      ],
    ]);
    $this->forge->addKey('id', true);
    $this->forge->addKey('response_id');
    $this->forge->addKey('question_id');
    $this->forge->addForeignKey('response_id', 'compliance_questionnaire_responses', 'id', 'CASCADE', 'CASCADE');
    $this->forge->addForeignKey('question_id', 'compliance_questionnaire_questions', 'id', 'CASCADE', 'CASCADE');
    $this->forge->createTable('compliance_questionnaire_response_answers', true);

    $this->seedDefaults();
  }

  public function down()
  {
    $this->forge->dropTable('compliance_questionnaire_response_answers', true);
    $this->forge->dropTable('compliance_questionnaire_responses', true);
    $this->forge->dropTable('compliance_questionnaire_questions', true);
    $this->forge->dropTable('compliance_questionnaires', true);
  }

  protected function seedDefaults(): void
  {
    $db = \Config\Database::connect();
    $now = date('Y-m-d H:i:s');

    foreach (ComplianceQuestionnaireCatalog::defaults() as $template) {
      $exists = $db->table('compliance_questionnaires')->where('slug', $template['slug'])->get()->getFirstRow('array');
      if ($exists) {
        continue;
      }

      $db->table('compliance_questionnaires')->insert([
        'slug' => $template['slug'],
        'title' => $template['title'],
        'subtitle' => $template['subtitle'] ?? null,
        'description' => $template['description'] ?? null,
        'footer_note' => $template['footer_note'] ?? null,
        'active' => 1,
        'sort_order' => (int) ($template['sort_order'] ?? 0),
        'created_at' => $now,
        'updated_at' => $now,
      ]);

      $questionnaireId = (int) $db->insertID();
      $rows = [];
      foreach ($template['questions'] as $question) {
        $rows[] = [
          'questionnaire_id' => $questionnaireId,
          'section_label' => $question['section_label'] ?? null,
          'question_code' => $question['question_code'] ?? null,
          'sort_order' => (int) ($question['sort_order'] ?? 0),
          'question_text' => $question['question_text'],
          'answer_type' => $question['answer_type'] ?? 'radio',
          'options_json' => !empty($question['options']) ? json_encode($question['options'], JSON_UNESCAPED_UNICODE) : null,
          'placeholder' => $question['placeholder'] ?? null,
          'help_text' => $question['help_text'] ?? null,
          'is_required' => (int) ($question['is_required'] ?? 1),
          'created_at' => $now,
          'updated_at' => $now,
        ];
      }

      if (!empty($rows)) {
        $db->table('compliance_questionnaire_questions')->insertBatch($rows);
      }
    }
  }
}
