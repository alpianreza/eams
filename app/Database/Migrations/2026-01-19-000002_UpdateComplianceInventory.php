<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateComplianceInventory extends Migration
{
  public function up()
  {
    $this->forge->addColumn('compliance_inventory', [
      'item_type_id' => [
        'type' => 'INT',
        'unsigned' => true,
        'after' => 'category_id',
      ],
    ]);

    $this->db->query(
      'ALTER TABLE compliance_inventory
             ADD CONSTRAINT fk_inventory_item_type
             FOREIGN KEY (item_type_id)
             REFERENCES asset_item_types(id)
             ON DELETE RESTRICT'
    );
  }

  public function down()
  {
    $this->db->query(
      'ALTER TABLE compliance_inventory
             DROP FOREIGN KEY fk_inventory_item_type'
    );

    $this->forge->dropColumn('compliance_inventory', 'item_type_id');
  }
}
