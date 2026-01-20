<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateChecklistLogs extends Migration
{
  public function up()
  {
    $this->forge->addField([
      'id' => [
        'type'           => 'INT',
        'unsigned'       => true,
        'auto_increment' => true,
      ],
      'inventory_id' => [
        'type'     => 'INT',
        'unsigned' => true,
      ],
      'item_type_id' => [
        'type'     => 'INT',
        'unsigned' => true,
      ],
      'checklist_template_id' => [
        'type'     => 'INT',
        'unsigned' => true,
      ],
      'check_date' => [
        'type' => 'DATE',
      ],
      'period_key' => [
        'type'       => 'VARCHAR',
        'constraint' => 10,
      ],
      'status' => [
        'type'       => 'ENUM',
        'constraint' => ['ok', 'ng', 'na'],
        'default'    => 'ok',
      ],
      'remark' => [
        'type' => 'TEXT',
        'null' => true,
      ],
      'photo' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
        'null' => true,
      ],
      'checked_by' => [
        'type' => 'VARCHAR',
        'constraint' => 100,
        'null' => true,
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
      'CASCADE'
    );

    $this->forge->addForeignKey(
      'item_type_id',
      'asset_item_types',
      'id',
      'CASCADE',
      'CASCADE'
    );

    $this->forge->addForeignKey(
      'checklist_template_id',
      'checklist_templates',
      'id',
      'CASCADE',
      'CASCADE'
    );

    $this->forge->createTable('checklist_logs');
  }

  public function down()
  {
    $this->forge->dropTable('checklist_logs');
  }
}
