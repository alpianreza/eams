<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmsWaterConsumptionTables extends Migration
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
            'report_year' => [
                'type' => 'INT',
                'constraint' => 4,
            ],
            'production_output' => [
                'type' => 'DECIMAL',
                'constraint' => '18,2',
                'null' => true,
            ],
            'notes' => [
                'type' => 'TEXT',
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
        $this->forge->addUniqueKey('report_year');
        $this->forge->createTable('ems_water_consumption_years');

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'report_year' => [
                'type' => 'INT',
                'constraint' => 4,
            ],
            'report_month' => [
                'type' => 'INT',
                'constraint' => 2,
            ],
            'consumption_m3' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
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
        $this->forge->addUniqueKey(['report_year', 'report_month']);
        $this->forge->createTable('ems_water_consumption_entries');

        $now = date('Y-m-d H:i:s');

        $years = [2025, 2026, 2027, 2028, 2029];
        $yearRows = [];
        foreach ($years as $year) {
            $yearRows[] = [
                'report_year' => $year,
                'production_output' => $year === 2025 ? 4350778.00 : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        $this->db->table('ems_water_consumption_years')->insertBatch($yearRows);

        $seed2025 = [
            1 => 1532.00,
            2 => 1583.00,
            3 => 1352.00,
            4 => 911.00,
            5 => 1513.00,
            6 => 1558.00,
            7 => 1424.00,
            8 => 1349.00,
            9 => 1289.00,
            10 => 1341.00,
            11 => 1510.00,
            12 => 1560.00,
        ];

        $entryRows = [];
        foreach ($seed2025 as $month => $value) {
            $entryRows[] = [
                'report_year' => 2025,
                'report_month' => $month,
                'consumption_m3' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->db->table('ems_water_consumption_entries')->insertBatch($entryRows);
    }

    public function down()
    {
        $this->forge->dropTable('ems_water_consumption_entries', true);
        $this->forge->dropTable('ems_water_consumption_years', true);
    }
}