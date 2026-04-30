<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPatrolLayoutTransformFields extends Migration
{
  public function up()
  {
    $fields = [
      'image_scale' => [
        'type' => 'DECIMAL',
        'constraint' => '8,4',
        'default' => 1.0000,
        'after' => 'image_path',
      ],
      'image_offset_x' => [
        'type' => 'DECIMAL',
        'constraint' => '8,2',
        'default' => 0,
        'after' => 'image_scale',
      ],
      'image_offset_y' => [
        'type' => 'DECIMAL',
        'constraint' => '8,2',
        'default' => 0,
        'after' => 'image_offset_x',
      ],
    ];

    $this->forge->addColumn('patrol_layouts', $fields);
  }

  public function down()
  {
    $this->forge->dropColumn('patrol_layouts', ['image_scale', 'image_offset_x', 'image_offset_y']);
  }
}
