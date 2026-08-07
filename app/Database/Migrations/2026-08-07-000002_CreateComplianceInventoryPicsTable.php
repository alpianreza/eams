<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateComplianceInventoryPicsTable extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('compliance_inventory_pics')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'inventory_id' => ['type' => 'INT'],
                'user_id' => ['type' => 'INT'],
                'is_primary' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'created_at' => ['type' => 'DATETIME', 'default' => new RawSql('CURRENT_TIMESTAMP')],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['inventory_id', 'user_id']);
            $this->forge->addKey(['user_id', 'inventory_id']);
            $this->forge->createTable('compliance_inventory_pics', true);
        }

        if (! $this->db->tableExists('compliance_inventory') || ! $this->db->tableExists('users')) return;
        $inventories = $this->db->table('compliance_inventory')->select('id, pic')->where("TRIM(COALESCE(pic, '')) <> ''", null, false)->get()->getResultArray();
        foreach ($inventories as $inventory) {
            $names = preg_split('/\s*(?:\r\n|\r|\n|,|\s+-\s+)\s*/', trim((string) ($inventory['pic'] ?? ''))) ?: [];
            $names = array_slice(array_values(array_unique(array_filter(array_map('trim', $names)))), 0, 2);
            foreach ($names as $position => $name) {
                $user = $this->db->table('users')->select('id')->where('name', $name)->get()->getRowArray();
                if (! $user) continue;
                $exists = $this->db->table('compliance_inventory_pics')->where('inventory_id', (int) $inventory['id'])->where('user_id', (int) $user['id'])->countAllResults();
                if ($exists === 0) $this->db->table('compliance_inventory_pics')->insert(['inventory_id' => (int) $inventory['id'], 'user_id' => (int) $user['id'], 'is_primary' => $position === 0 ? 1 : 0, 'created_at' => date('Y-m-d H:i:s')]);
            }
        }
    }

    public function down()
    {
        // Relasi PIC dipertahankan agar assignment tidak hilang saat rollback.
    }
}
