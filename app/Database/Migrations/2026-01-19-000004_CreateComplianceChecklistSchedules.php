<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateComplianceChecklistSchedules extends Migration
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
      'frequency' => [
        'type' => 'ENUM',
        'constraint' => ['daily', 'weekly', 'monthly'],
      ],
      'week_day' => [
        'type' => 'TINYINT',
        'null' => true,
        'comment' => '1=Monday ... 7=Sunday',
      ],
      'month_day' => [
        'type' => 'TINYINT',
        'null' => true,
        'comment' => '1–31',
      ],
      'start_date' => [
        'type' => 'DATE',
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
      'inventory_id',
      'compliance_inventory',
      'id',
      'CASCADE',
      'RESTRICT'
    );

    $this->forge->createTable('compliance_checklist_schedules');
  }

  public function down()
  {
    $this->forge->dropTable('compliance_checklist_schedules');
  }
}
