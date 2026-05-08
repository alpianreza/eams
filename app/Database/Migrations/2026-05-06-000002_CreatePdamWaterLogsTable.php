<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePdamWaterLogsTable extends Migration
{
  public function up()
  {
    $this->forge->addField([
      'id' => [
        'type' => 'INT',
        'constraint' => 11,
        'unsigned' => true,
        'auto_increment' => true,
      ],
      'log_date' => [
        'type' => 'DATE',
      ],
      'log_time' => [
        'type' => 'TIME',
        'null' => true,
      ],
      'meter_reading' => [
        'type' => 'DECIMAL',
        'constraint' => '12,2',
        'default' => 0,
      ],
      'note' => [
        'type' => 'TEXT',
        'null' => true,
      ],
      'created_by' => [
        'type' => 'INT',
        'constraint' => 11,
        'null' => true,
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
    $this->forge->addKey('log_date');
    $this->forge->createTable('pdam_water_logs', true);
  }

  public function down()
  {
    $this->forge->dropTable('pdam_water_logs', true);
  }
}
