<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditLogsTable extends Migration
{
    public function up()
    {
        // Check if table already exists
        if ($this->db->tableExists('audit_logs')) {
            // Add new columns if they don't exist
            $fields = $this->db->getFieldNames('audit_logs');
            if (! in_array('ip_address', $fields, true)) {
                $this->forge->addColumn('audit_logs', [
                    'ip_address' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 45,
                        'null'       => true,
                        'after'      => 'description',
                    ],
                ]);
            }
            if (! in_array('user_agent', $fields, true)) {
                $this->forge->addColumn('audit_logs', [
                    'user_agent' => [
                        'type' => 'TEXT',
                        'null' => true,
                        'after' => 'ip_address',
                    ],
                ]);
            }
        } else {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'action' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'description' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'ip_address' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 45,
                    'null'       => true,
                ],
                'user_agent' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type'    => 'DATETIME',
                    'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
                ],
            ]);

            $this->forge->addKey('id', false, true);
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addKey('action');
            $this->forge->addKey('created_at');
            $this->forge->createTable('audit_logs', true);
        }
    }

    public function down()
    {
        // Don't drop the table — data is important
    }
}
