<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateItDeviceCommands extends Migration
{
  public function up()
  {
    if ($this->db->tableExists('it_device_commands')) {
      return;
    }

    $this->forge->addField([
      'id' => [
        'type' => 'INT',
        'constraint' => 11,
        'unsigned' => true,
        'auto_increment' => true,
      ],
      'device_id' => [
        'type' => 'INT',
        'constraint' => 11,
        'unsigned' => true,
      ],
      'command_id' => [
        'type' => 'VARCHAR',
        'constraint' => 64,
        'null' => true,
      ],
      'command' => [
        'type' => 'VARCHAR',
        'constraint' => 100,
      ],
      'payload_json' => [
        'type' => 'LONGTEXT',
        'null' => true,
      ],
      'status' => [
        'type' => 'VARCHAR',
        'constraint' => 30,
        'default' => 'queued',
      ],
      'result' => [
        'type' => 'TEXT',
        'null' => true,
      ],
      'requested_by' => [
        'type' => 'VARCHAR',
        'constraint' => 120,
        'null' => true,
      ],
      'requested_at' => [
        'type' => 'DATETIME',
        'null' => true,
      ],
      'executed_at' => [
        'type' => 'DATETIME',
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
    $this->forge->addKey('device_id');
    $this->forge->addKey('command_id');
    $this->forge->createTable('it_device_commands', true);
  }

  public function down()
  {
    if ($this->db->tableExists('it_device_commands')) {
      $this->forge->dropTable('it_device_commands', true);
    }
  }
}
