<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePatrolLayoutAndPhotoTables extends Migration
{
  public function up()
  {
    $this->forge->addField([
      'id' => [
        'type'           => 'INT',
        'constraint'     => 11,
        'unsigned'       => true,
        'auto_increment' => true,
      ],
      'name' => [
        'type'       => 'VARCHAR',
        'constraint' => 100,
      ],
      'image_path' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
        'null' => true,
      ],
      'active' => [
        'type'       => 'TINYINT',
        'constraint' => 1,
        'default'    => 1,
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
    $this->forge->createTable('patrol_layouts', true);

    $now = date('Y-m-d H:i:s');
    $this->db->table('patrol_layouts')->insert([
      'name'       => 'Layout Utama',
      'image_path' => null,
      'active'     => 1,
      'created_at' => $now,
    ]);

    $this->forge->addField([
      'id' => [
        'type'           => 'INT',
        'constraint'     => 11,
        'unsigned'       => true,
        'auto_increment' => true,
      ],
      'log_id' => [
        'type'       => 'INT',
        'constraint' => 11,
        'unsigned'   => true,
      ],
      'photo_path' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
      ],
      'sort_order' => [
        'type'       => 'INT',
        'constraint' => 11,
        'default'    => 1,
      ],
      'created_at' => [
        'type' => 'DATETIME',
        'null' => true,
      ],
    ]);
    $this->forge->addKey('id', true);
    $this->forge->addKey('log_id');
    $this->forge->createTable('patrol_log_photos', true);
  }

  public function down()
  {
    $this->forge->dropTable('patrol_log_photos', true);
    $this->forge->dropTable('patrol_layouts', true);
  }
}
