<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateComplianceChecklistLogs extends Migration
{
  public function up()
  {
    $this->forge->addField([
      'id' => [
        'type' => 'INT',
        'unsigned' => true,
        'auto_increment' => true,
      ],
      'inventory_id' => [
        'type' => 'INT',
        'unsigned' => true,
      ],
      'schedule_id' => [
        'type' => 'INT',
        'unsigned' => true,
      ],
      'template_id' => [
        'type' => 'INT',
        'unsigned' => true,
      ],
      'inspection_date' => [
        'type' => 'DATE',
      ],
      'result' => [
        'type' => 'ENUM',
        'constraint' => ['ok', 'ng', 'na'],
      ],
      'note' => [
        'type' => 'TEXT',
        'null' => true,
      ],
      'photo' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
        'null' => true,
      ],
      'checked_by' => [
        'type' => 'INT',
        'unsigned' => true,
      ],
      'created_at' => [
        'type' => 'DATETIME',
        'null' => true,
      ],
    ]);

    $this->forge->addKey('id', true);

    $this->forge->addForeignKey(
      'inventory_id',
      'compliance_inventory',
      'id',
      'CASCADE',
      'RESTRICT'
    );

    $this->forge->addForeignKey(
      'schedule_id',
      'compliance_checklist_schedules',
      'id',
      'CASCADE',
      'RESTRICT'
    );

    $this->forge->addForeignKey(
      'template_id',
      'compliance_checklist_templates',
      'id',
      'CASCADE',
      'RESTRICT'
    );

    $this->forge->createTable('compliance_checklist_logs');
  }

  public function down()
  {
    $this->forge->dropTable('compliance_checklist_logs');
  }
}
