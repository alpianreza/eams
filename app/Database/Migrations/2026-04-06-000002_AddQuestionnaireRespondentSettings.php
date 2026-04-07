<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddQuestionnaireRespondentSettings extends Migration
{
  public function up()
  {
    if (!$this->db->tableExists('compliance_questionnaires')) {
      return;
    }

    $fields = $this->db->getFieldNames('compliance_questionnaires');
    $columns = [];

    if (!in_array('collect_name', $fields, true)) {
      $columns['collect_name'] = [
        'type' => 'TINYINT',
        'default' => 1,
        'after' => 'footer_note',
      ];
    }

    if (!in_array('collect_phone', $fields, true)) {
      $columns['collect_phone'] = [
        'type' => 'TINYINT',
        'default' => 1,
        'after' => isset($columns['collect_name']) ? 'collect_name' : 'footer_note',
      ];
    }

    if (!in_array('collect_email', $fields, true)) {
      $columns['collect_email'] = [
        'type' => 'TINYINT',
        'default' => 1,
        'after' => isset($columns['collect_phone']) ? 'collect_phone' : (in_array('collect_phone', $fields, true) ? 'collect_phone' : (isset($columns['collect_name']) ? 'collect_name' : 'footer_note')),
      ];
    }

    if (!empty($columns)) {
      $this->forge->addColumn('compliance_questionnaires', $columns);
    }
  }

  public function down()
  {
    if (!$this->db->tableExists('compliance_questionnaires')) {
      return;
    }

    $fields = $this->db->getFieldNames('compliance_questionnaires');

    foreach (['collect_name', 'collect_phone', 'collect_email'] as $column) {
      if (in_array($column, $fields, true)) {
        $this->forge->dropColumn('compliance_questionnaires', $column);
      }
    }
  }
}
