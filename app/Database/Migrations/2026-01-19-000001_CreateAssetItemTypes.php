<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAssetItemTypes extends Migration
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
      'inventory_category_id' => [
        'type' => 'INT',
        'constraint' => 11,
        'unsigned' => true,
      ],
      'name' => [
        'type' => 'VARCHAR',
        'constraint' => 100,
      ],
      'code' => [
        'type' => 'VARCHAR',
        'constraint' => 20,
      ],
      'active' => [
        'type' => 'TINYINT',
        'constraint' => 1,
        'default' => 1,
      ],
      'created_at' => [
        'type' => 'DATETIME',
        'null' => true,
      ],
    ]);

    $this->forge->addKey('id', true);

    // BUAT TABEL DULU
    $this->forge->createTable('asset_item_types', true);

    // TAMBAH FK MANUAL (PALING AMAN)
    $this->db->query("
            ALTER TABLE asset_item_types
            ADD CONSTRAINT fk_item_type_inventory_category
            FOREIGN KEY (inventory_category_id)
            REFERENCES inventory_categories(id)
            ON DELETE CASCADE
            ON UPDATE CASCADE
        ");
  }

  public function down()
  {
    $this->forge->dropTable('asset_item_types');
  }
}
