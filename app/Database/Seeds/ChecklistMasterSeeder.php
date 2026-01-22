<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ChecklistMasterSeeder extends Seeder
{
  public function run()
  {
    $data = [

      /*
            |--------------------------------------------------------------------------
            | FIRE SAFETY - APAR
            |--------------------------------------------------------------------------
            | item_type_id = sesuaikan dengan tabel asset_item_types
            */
      [
        'item_type_id'  => 1, // APAR
        'question'      => 'Tekanan tabung dalam kondisi normal',
        'frequency'     => 'monthly',
        'require_photo' => 1,
        'active'        => 1
      ],
      [
        'item_type_id'  => 1,
        'question'      => 'Tabung dalam kondisi baik (tidak penyok/rusak)',
        'frequency'     => 'monthly',
        'require_photo' => 0,
        'active'        => 1
      ],
      [
        'item_type_id'  => 1,
        'question'      => 'Pin pengaman terpasang',
        'frequency'     => 'monthly',
        'require_photo' => 0,
        'active'        => 1
      ],
      [
        'item_type_id'  => 1,
        'question'      => 'Segel tidak rusak',
        'frequency'     => 'monthly',
        'require_photo' => 0,
        'active'        => 1
      ],
      [
        'item_type_id'  => 1,
        'question'      => 'Nozzle tidak tersumbat',
        'frequency'     => 'monthly',
        'require_photo' => 0,
        'active'        => 1
      ],

      /*
            |--------------------------------------------------------------------------
            | FIRE SAFETY - FIRE ALARM
            |--------------------------------------------------------------------------
            */
      [
        'item_type_id'  => 3, // Fire Alarm
        'question'      => 'Panel menyala normal',
        'frequency'     => 'weekly',
        'require_photo' => 0,
        'active'        => 1
      ],
      [
        'item_type_id'  => 3,
        'question'      => 'Tidak ada alarm error',
        'frequency'     => 'weekly',
        'require_photo' => 0,
        'active'        => 1
      ],
      [
        'item_type_id'  => 3,
        'question'      => 'Manual call point berfungsi',
        'frequency'     => 'weekly',
        'require_photo' => 1,
        'active'        => 1
      ],

      /*
            |--------------------------------------------------------------------------
            | FIRE SAFETY - HYDRANT
            |--------------------------------------------------------------------------
            */
      [
        'item_type_id'  => 2, // Hydrant
        'question'      => 'Box hydrant terkunci dan tidak rusak',
        'frequency'     => 'monthly',
        'require_photo' => 0,
        'active'        => 1
      ],
      [
        'item_type_id'  => 2,
        'question'      => 'Selang dalam kondisi baik',
        'frequency'     => 'monthly',
        'require_photo' => 1,
        'active'        => 1
      ],
      [
        'item_type_id'  => 2,
        'question'      => 'Valve mudah dibuka',
        'frequency'     => 'monthly',
        'require_photo' => 0,
        'active'        => 1
      ],

      /*
            |--------------------------------------------------------------------------
            | HSE - P3K
            |--------------------------------------------------------------------------
            */
      [
        'item_type_id'  => 10, // Kotak P3K
        'question'      => 'Kotak P3K dalam kondisi baik dan mudah dijangkau',
        'frequency'     => 'monthly',
        'require_photo' => 0,
        'active'        => 1
      ],
      [
        'item_type_id'  => 10, // Isi P3K
        'question'      => 'Isi P3K lengkap',
        'frequency'     => 'monthly',
        'require_photo' => 1,
        'active'        => 1
      ],
      [
        'item_type_id'  => 10,
        'question'      => 'Obat belum kadaluarsa',
        'frequency'     => 'monthly',
        'require_photo' => 0,
        'active'        => 1
      ],

    ];

    $this->db->table('checklist_master')->insertBatch($data);
  }
}
