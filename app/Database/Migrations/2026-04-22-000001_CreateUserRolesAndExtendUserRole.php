<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserRolesAndExtendUserRole extends Migration
{
  public function up()
  {
    $this->db->query("ALTER TABLE users MODIFY role VARCHAR(50) NOT NULL DEFAULT 'staff'");

    $this->forge->addField([
      'id' => [
        'type'           => 'INT',
        'constraint'     => 11,
        'unsigned'       => true,
        'auto_increment' => true,
      ],
      'name' => [
        'type'       => 'VARCHAR',
        'constraint' => 50,
        'null'       => false,
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
    $this->forge->addUniqueKey('name');
    $this->forge->createTable('user_roles', true);

    $defaults = ['admin', 'staff', 'compliance', 'auditor', 'office'];
    foreach ($defaults as $role) {
      $this->db->table('user_roles')->ignore(true)->insert([
        'name'       => $role,
        'created_at' => date('Y-m-d H:i:s'),
      ]);
    }

    $existingRoles = $this->db->query("SELECT DISTINCT role FROM users WHERE role IS NOT NULL AND role <> ''")->getResultArray();

    foreach ($existingRoles as $row) {
      $role = trim((string) ($row['role'] ?? ''));
      if ($role === '') {
        continue;
      }

      $this->db->table('user_roles')->ignore(true)->insert([
        'name'       => $role,
        'created_at' => date('Y-m-d H:i:s'),
      ]);
    }
  }

  public function down()
  {
    $this->forge->dropTable('user_roles', true);
    $this->db->query("ALTER TABLE users MODIFY role ENUM('admin','staff','compliance','auditor','office') NOT NULL DEFAULT 'staff'");
  }
}
