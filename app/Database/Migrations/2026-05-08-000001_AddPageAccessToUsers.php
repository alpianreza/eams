<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPageAccessToUsers extends Migration
{
  public function up()
  {
    if (! $this->db->fieldExists('page_access', 'users')) {
      $this->forge->addColumn('users', [
        'page_access' => [
          'type' => 'TEXT',
          'null' => true,
          'after' => 'permission',
        ],
      ]);
    }
  }

  public function down()
  {
    if ($this->db->fieldExists('page_access', 'users')) {
      $this->forge->dropColumn('users', 'page_access');
    }
  }
}
