<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateChecklistSchedules extends Migration
{
  public function up()
  {
    $this->forge->addField([
      'id' => [
        'type'           => 'INT',
        'unsigned'       => true,
        'auto_increment' => true,
      ],
      'item_type_id' => [
        'type'     => 'INT',
        'unsigned' => true,
      ],
      'frequency' => [
        'type'       => 'ENUM',
        'constraint' => ['daily', 'weekly', 'monthly'],
      ],
      'active' => [
        'type'    => 'TINYINT',
        'default' => 1,
      ],
    ]);

    $this->forge->addKey('id', true);
    $this->forge->addForeignKey(
      'item_type_id',
      'asset_item_types',
      'id',
      'CASCADE',
      'CASCADE'
    );

    $this->forge->createTable('checklist_schedules');
  }

  public function down()
  {
    $this->forge->dropTable('checklist_schedules');
  }
}
