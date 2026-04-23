<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePatrolModuleTables extends Migration
{
  public function up()
  {
    $this->forge->addField([
      'id' => [
        'type'           => 'INT',
        'constraint'     => 11,
        'unsigned'       => true,
        'auto_increment' => true,
      ],
      'name' => [
        'type'       => 'VARCHAR',
        'constraint' => 100,
      ],
      'slug' => [
        'type'       => 'VARCHAR',
        'constraint' => 100,
      ],
      'description' => [
        'type' => 'TEXT',
        'null' => true,
      ],
      'sort_order' => [
        'type'       => 'INT',
        'constraint' => 11,
        'default'    => 0,
      ],
      'active' => [
        'type'       => 'TINYINT',
        'constraint' => 1,
        'default'    => 1,
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
    $this->forge->addUniqueKey('slug');
    $this->forge->createTable('patrol_routes', true);

    $this->forge->addField([
      'id' => [
        'type'           => 'INT',
        'constraint'     => 11,
        'unsigned'       => true,
        'auto_increment' => true,
      ],
      'code' => [
        'type'       => 'VARCHAR',
        'constraint' => 50,
      ],
      'name' => [
        'type'       => 'VARCHAR',
        'constraint' => 120,
      ],
      'area' => [
        'type'       => 'VARCHAR',
        'constraint' => 120,
        'null'       => true,
      ],
      'barcode_value' => [
        'type'       => 'VARCHAR',
        'constraint' => 120,
        'null'       => true,
      ],
      'lat' => [
        'type'       => 'DECIMAL',
        'constraint' => '10,7',
        'null'       => true,
      ],
      'lng' => [
        'type'       => 'DECIMAL',
        'constraint' => '10,7',
        'null'       => true,
      ],
      'radius_m' => [
        'type'       => 'INT',
        'constraint' => 11,
        'default'    => 10,
      ],
      'map_x' => [
        'type'       => 'DECIMAL',
        'constraint' => '5,2',
        'null'       => true,
      ],
      'map_y' => [
        'type'       => 'DECIMAL',
        'constraint' => '5,2',
        'null'       => true,
      ],
      'active' => [
        'type'       => 'TINYINT',
        'constraint' => 1,
        'default'    => 1,
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
    $this->forge->addUniqueKey('code');
    $this->forge->createTable('patrol_checkpoints', true);

    $this->forge->addField([
      'id' => [
        'type'           => 'INT',
        'constraint'     => 11,
        'unsigned'       => true,
        'auto_increment' => true,
      ],
      'route_id' => [
        'type'       => 'INT',
        'constraint' => 11,
        'unsigned'   => true,
      ],
      'checkpoint_id' => [
        'type'       => 'INT',
        'constraint' => 11,
        'unsigned'   => true,
      ],
      'route_order' => [
        'type'       => 'INT',
        'constraint' => 11,
        'default'    => 1,
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
    $this->forge->addKey(['route_id', 'checkpoint_id']);
    $this->forge->createTable('patrol_route_checkpoints', true);

    $this->forge->addField([
      'id' => [
        'type'           => 'INT',
        'constraint'     => 11,
        'unsigned'       => true,
        'auto_increment' => true,
      ],
      'route_id' => [
        'type'       => 'INT',
        'constraint' => 11,
        'unsigned'   => true,
      ],
      'patrol_date' => [
        'type' => 'DATE',
      ],
      'started_by' => [
        'type'       => 'INT',
        'constraint' => 11,
        'unsigned'   => true,
      ],
      'started_at' => [
        'type' => 'DATETIME',
        'null' => true,
      ],
      'ended_at' => [
        'type' => 'DATETIME',
        'null' => true,
      ],
      'status' => [
        'type'       => 'VARCHAR',
        'constraint' => 20,
        'default'    => 'active',
      ],
      'total_checkpoints' => [
        'type'       => 'INT',
        'constraint' => 11,
        'default'    => 0,
      ],
      'checked_count' => [
        'type'       => 'INT',
        'constraint' => 11,
        'default'    => 0,
      ],
      'issue_count' => [
        'type'       => 'INT',
        'constraint' => 11,
        'default'    => 0,
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
    $this->forge->addKey(['route_id', 'patrol_date']);
    $this->forge->createTable('patrol_sessions', true);

    $this->forge->addField([
      'id' => [
        'type'           => 'INT',
        'constraint'     => 11,
        'unsigned'       => true,
        'auto_increment' => true,
      ],
      'session_id' => [
        'type'       => 'INT',
        'constraint' => 11,
        'unsigned'   => true,
      ],
      'route_id' => [
        'type'       => 'INT',
        'constraint' => 11,
        'unsigned'   => true,
      ],
      'checkpoint_id' => [
        'type'       => 'INT',
        'constraint' => 11,
        'unsigned'   => true,
      ],
      'checked_by' => [
        'type'       => 'INT',
        'constraint' => 11,
        'unsigned'   => true,
      ],
      'barcode_value' => [
        'type'       => 'VARCHAR',
        'constraint' => 120,
        'null'       => true,
      ],
      'status' => [
        'type'       => 'VARCHAR',
        'constraint' => 20,
        'default'    => 'ok',
      ],
      'note' => [
        'type' => 'TEXT',
        'null' => true,
      ],
      'latitude' => [
        'type'       => 'DECIMAL',
        'constraint' => '10,7',
        'null'       => true,
      ],
      'longitude' => [
        'type'       => 'DECIMAL',
        'constraint' => '10,7',
        'null'       => true,
      ],
      'distance_m' => [
        'type'       => 'DECIMAL',
        'constraint' => '6,2',
        'null'       => true,
      ],
      'photo_path' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
        'null' => true,
      ],
      'checked_at' => [
        'type' => 'DATETIME',
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
    $this->forge->addKey(['session_id', 'checkpoint_id']);
    $this->forge->createTable('patrol_logs', true);

    $now = date('Y-m-d H:i:s');

    $routes = [
      [
        'name'        => 'Rute 1-2-3-4',
        'slug'        => 'forward',
        'description' => 'Urutan patroli dari CP1 ke CP4',
        'sort_order'  => 1,
      ],
      [
        'name'        => 'Rute 4-3-2-1',
        'slug'        => 'reverse',
        'description' => 'Urutan patroli dari CP4 ke CP1',
        'sort_order'  => 2,
      ],
    ];

    foreach ($routes as $route) {
      $this->db->table('patrol_routes')->ignore(true)->insert([
        'name'        => $route['name'],
        'slug'        => $route['slug'],
        'description' => $route['description'],
        'sort_order'  => $route['sort_order'],
        'active'      => 1,
        'created_at'  => $now,
      ]);
    }

    $checkpoints = [
      [
        'code'          => 'CP1',
        'name'          => 'Pos Bambu',
        'area'          => 'Pos Bambu',
        'barcode_value'  => 'PATROL-CP1',
        'lat'           => -6.906502,
        'lng'           => 106.778173,
        'radius_m'      => 10,
        'map_x'         => 27,
        'map_y'         => 8,
      ],
      [
        'code'          => 'CP2',
        'name'          => 'Area B3',
        'area'          => 'Area B3',
        'barcode_value'  => 'PATROL-CP2',
        'lat'           => -6.906928,
        'lng'           => 106.777749,
        'radius_m'      => 10,
        'map_x'         => 12,
        'map_y'         => 41,
      ],
      [
        'code'          => 'CP3',
        'name'          => 'Gudang D',
        'area'          => 'Gudang D',
        'barcode_value'  => 'PATROL-CP3',
        'lat'           => -6.907559,
        'lng'           => 106.777985,
        'radius_m'      => 10,
        'map_x'         => 22,
        'map_y'         => 90,
      ],
      [
        'code'          => 'CP4',
        'name'          => 'Mushola',
        'area'          => 'Mushola',
        'barcode_value'  => 'PATROL-CP4',
        'lat'           => -6.907509,
        'lng'           => 106.778660,
        'radius_m'      => 10,
        'map_x'         => 55,
        'map_y'         => 88,
      ],
    ];

    foreach ($checkpoints as $checkpoint) {
      $this->db->table('patrol_checkpoints')->ignore(true)->insert([
        'code'         => $checkpoint['code'],
        'name'         => $checkpoint['name'],
        'area'         => $checkpoint['area'],
        'barcode_value' => $checkpoint['barcode_value'],
        'lat'          => $checkpoint['lat'],
        'lng'          => $checkpoint['lng'],
        'radius_m'     => $checkpoint['radius_m'],
        'map_x'        => $checkpoint['map_x'],
        'map_y'        => $checkpoint['map_y'],
        'active'       => 1,
        'created_at'   => $now,
      ]);
    }

    $routeRows = $this->db->table('patrol_routes')->orderBy('sort_order', 'ASC')->get()->getResultArray();
    $checkpointRows = $this->db->table('patrol_checkpoints')->orderBy('code', 'ASC')->get()->getResultArray();
    $checkpointMap = [];
    foreach ($checkpointRows as $row) {
      $checkpointMap[(string) $row['code']] = (int) $row['id'];
    }

    foreach ($routeRows as $route) {
      $routeId = (int) $route['id'];
      if ($route['slug'] === 'forward') {
        $ordered = ['CP1', 'CP2', 'CP3', 'CP4'];
      } else {
        $ordered = ['CP4', 'CP3', 'CP2', 'CP1'];
      }

      foreach ($ordered as $index => $code) {
        $checkpointId = $checkpointMap[$code] ?? null;
        if (!$checkpointId) {
          continue;
        }

        $this->db->table('patrol_route_checkpoints')->ignore(true)->insert([
          'route_id'      => $routeId,
          'checkpoint_id' => $checkpointId,
          'route_order'   => $index + 1,
          'created_at'    => $now,
        ]);
      }
    }
  }

  public function down()
  {
    $this->forge->dropTable('patrol_logs', true);
    $this->forge->dropTable('patrol_sessions', true);
    $this->forge->dropTable('patrol_route_checkpoints', true);
    $this->forge->dropTable('patrol_checkpoints', true);
    $this->forge->dropTable('patrol_routes', true);
  }
}
