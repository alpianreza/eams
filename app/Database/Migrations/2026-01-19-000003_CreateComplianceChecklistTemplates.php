<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateComplianceChecklistTemplates extends Migration
{
  public function up()
  {
    $this->forge->addField([
      'id' => [
        'type' => 'INT',
        'unsigned' => true,
        'auto_increment' => true,
      ],
      'item_type_id' => [
        'type' => 'INT',
        'unsigned' => true,
      ],
      'code' => [
        'type' => 'VARCHAR',
        'constraint' => 30,
      ],
      'question' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
      ],
      'require_photo' => [
        'type' => 'TINYINT',
        'default' => 0,
      ],
      'active' => [
        'type' => 'TINYINT',
        'default' => 1,
      ],
      'created_at' => [
        'type' => 'DATETIME',
        'null' => true,
      ],
    ]);

    $this->forge->addKey('id', true);
    $this->forge->addForeignKey(
      'item_type_id',
      'asset_item_types',
      'id',
      'CASCADE',
      'RESTRICT'
    );

    $this->forge->createTable('compliance_checklist_templates');
  }

  public function down()
  {
    $this->forge->dropTable('compliance_checklist_templates');
  }
}
