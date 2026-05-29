<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateThermalImagingTables extends Migration
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
      'name' => [
        'type' => 'VARCHAR',
        'constraint' => 180,
      ],
      'section' => [
        'type' => 'VARCHAR',
        'constraint' => 180,
        'null' => true,
      ],
      'active' => [
        'type' => 'TINYINT',
        'constraint' => 1,
        'default' => 1,
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
    $this->forge->addKey('active');
    $this->forge->createTable('thermal_imaging_locations', true);

    $this->forge->addField([
      'id' => [
        'type' => 'INT',
        'constraint' => 11,
        'unsigned' => true,
        'auto_increment' => true,
      ],
      'inspection_date' => [
        'type' => 'DATE',
      ],
      'inspector_name' => [
        'type' => 'VARCHAR',
        'constraint' => 120,
      ],
      'facility' => [
        'type' => 'VARCHAR',
        'constraint' => 180,
      ],
      'area_name' => [
        'type' => 'VARCHAR',
        'constraint' => 180,
        'default' => 'Main Building (Sewing Area)',
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
    $this->forge->addKey('inspection_date');
    $this->forge->createTable('thermal_imaging_reports', true);

    $this->forge->addField([
      'id' => [
        'type' => 'INT',
        'constraint' => 11,
        'unsigned' => true,
        'auto_increment' => true,
      ],
      'report_id' => [
        'type' => 'INT',
        'constraint' => 11,
        'unsigned' => true,
      ],
      'location_id' => [
        'type' => 'INT',
        'constraint' => 11,
        'unsigned' => true,
        'null' => true,
      ],
      'location_name' => [
        'type' => 'VARCHAR',
        'constraint' => 180,
      ],
      'celsius' => [
        'type' => 'DECIMAL',
        'constraint' => '6,2',
      ],
      'thermal_image' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
        'null' => true,
      ],
      'findings' => [
        'type' => 'TEXT',
        'null' => true,
      ],
      'recommendation' => [
        'type' => 'TEXT',
        'null' => true,
      ],
      'sort_order' => [
        'type' => 'INT',
        'constraint' => 11,
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
    $this->forge->addKey('report_id');
    $this->forge->addKey('location_id');
    $this->forge->addForeignKey('report_id', 'thermal_imaging_reports', 'id', 'CASCADE', 'CASCADE');
    $this->forge->addForeignKey('location_id', 'thermal_imaging_locations', 'id', 'SET NULL', 'CASCADE');
    $this->forge->createTable('thermal_imaging_report_items', true);
  }

  public function down()
  {
    $this->forge->dropTable('thermal_imaging_report_items', true);
    $this->forge->dropTable('thermal_imaging_reports', true);
    $this->forge->dropTable('thermal_imaging_locations', true);
  }
}
