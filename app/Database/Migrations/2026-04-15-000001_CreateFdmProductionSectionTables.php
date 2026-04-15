<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFdmProductionSectionTables extends Migration
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
        $this->forge->addUniqueKey('report_year', 'fdm_production_section_years_report_year_unique');
        $this->forge->createTable('fdm_production_section_years');

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'year_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'section_key' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'section_label' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'entry_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'retail',
            ],
            'frequency_label' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'Monthly',
            ],
            'logo_path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'display_order' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'monthly_values' => [
                'type' => 'LONGTEXT',
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
        $this->forge->addKey('year_id');
        $this->forge->addUniqueKey(['year_id', 'section_key'], 'fdm_production_section_entries_year_section_unique');
        $this->forge->addForeignKey('year_id', 'fdm_production_section_years', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('fdm_production_section_entries');
    }

    public function down()
    {
        $this->forge->dropTable('fdm_production_section_entries', true);
        $this->forge->dropTable('fdm_production_section_years', true);
    }
}
