<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateNotificationAndBrandingInfrastructure extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('app_settings')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'setting_key' => ['type' => 'VARCHAR', 'constraint' => 120],
                'setting_value' => ['type' => 'TEXT', 'null' => true],
                'is_secret' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'default' => new RawSql('CURRENT_TIMESTAMP')],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('setting_key');
            $this->forge->createTable('app_settings', true);
        }

        if (! $this->db->tableExists('notifications')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'user_id' => ['type' => 'INT', 'unsigned' => true],
                'actor_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'type' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'info'],
                'title' => ['type' => 'VARCHAR', 'constraint' => 180],
                'message' => ['type' => 'TEXT'],
                'url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
                'entity_type' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
                'entity_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
                'dedupe_key' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
                'read_at' => ['type' => 'DATETIME', 'null' => true],
                'email_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'skipped'],
                'whatsapp_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'skipped'],
                'created_at' => ['type' => 'DATETIME', 'default' => new RawSql('CURRENT_TIMESTAMP')],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['user_id', 'read_at']);
            $this->forge->addKey('created_at');
            $this->forge->addUniqueKey('dedupe_key');
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('actor_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('notifications', true);
        }

        if ($this->db->tableExists('users')) {
            $fields = $this->db->getFieldNames('users');
            if (! in_array('email', $fields, true)) {
                $this->forge->addColumn('users', [
                    'email' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true, 'after' => 'username'],
                ]);
            }
        }

        $defaults = [
            'company_name' => 'PT YOUNGHYUN STAR',
            'company_address' => '',
            'company_logo' => 'assets/images/company/logo.png',
            'document_footer' => 'Dokumen dibuat melalui EAMS',
            'document_signatory_name' => '',
            'document_signatory_title' => '',
            'notification_email_enabled' => '0',
            'notification_whatsapp_enabled' => '0',
            'notification_whatsapp_webhook' => '',
            'notification_whatsapp_token' => '',
        ];

        foreach ($defaults as $key => $value) {
            $exists = $this->db->table('app_settings')->where('setting_key', $key)->countAllResults();
            if ($exists === 0) {
                $this->db->table('app_settings')->insert([
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'is_secret' => $key === 'notification_whatsapp_token' ? 1 : 0,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function down()
    {
        // Data notifikasi dan branding dipertahankan agar aman saat rollback.
    }
}
