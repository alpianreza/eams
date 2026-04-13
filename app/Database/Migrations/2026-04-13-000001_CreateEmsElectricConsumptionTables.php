<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmsElectricConsumptionTables extends Migration
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
        $this->forge->createTable('ems_electric_consumption_years');

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
            'consumption_kwh' => [
                'type' => 'DECIMAL',
                'constraint' => '14,2',
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
        $this->forge->createTable('ems_electric_consumption_entries');

        $now = date('Y-m-d H:i:s');
        $years = [2026, 2027, 2028, 2029, 2030];
        $rows = [];
        foreach ($years as $year) {
            $rows[] = [
                'report_year' => $year,
                'production_output' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->db->table('ems_electric_consumption_years')->insertBatch($rows);
    }

    public function down()
    {
        $this->forge->dropTable('ems_electric_consumption_entries', true);
        $this->forge->dropTable('ems_electric_consumption_years', true);
    }
}
