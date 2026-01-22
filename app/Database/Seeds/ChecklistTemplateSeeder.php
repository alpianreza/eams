<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ChecklistTemplateSeeder extends Seeder
{
  public function run()
  {
    $data = [

      // ================= APAR =================
      ['item_type_id' => 1, 'question' => 'Pressure normal', 'require_photo' => 1],
      ['item_type_id' => 1, 'question' => 'Seal tidak rusak', 'require_photo' => 0],
      ['item_type_id' => 1, 'question' => 'Pin pengaman tersedia', 'require_photo' => 0],
      ['item_type_id' => 1, 'question' => 'Selang tidak bocor', 'require_photo' => 0],
      ['item_type_id' => 1, 'question' => 'Tabung tidak berkarat', 'require_photo' => 0],

      // ================= HYDRANT =================
      ['item_type_id' => 2, 'question' => 'Box hydrant terkunci & tidak rusak', 'require_photo' => 0],
      ['item_type_id' => 2, 'question' => 'Selang tersedia dan layak pakai', 'require_photo' => 0],
      ['item_type_id' => 2, 'question' => 'Valve dapat dibuka', 'require_photo' => 1],

      // ================= FIRE ALARM =================
      ['item_type_id' => 3, 'question' => 'Panel alarm menyala normal', 'require_photo' => 0],
      ['item_type_id' => 3, 'question' => 'Tidak ada alarm error', 'require_photo' => 0],
      ['item_type_id' => 3, 'question' => 'Manual call point berfungsi', 'require_photo' => 1],

      // ================= CCTV =================
      ['item_type_id' => 13, 'question' => 'Kamera menyala', 'require_photo' => 0],
      ['item_type_id' => 13, 'question' => 'Gambar jelas', 'require_photo' => 0],
      ['item_type_id' => 13, 'question' => 'Rekaman tersimpan', 'require_photo' => 0],

      // ================= P3K =================
      ['item_type_id' => 10, 'question' => 'Kotak P3K tersedia', 'require_photo' => 0],
      ['item_type_id' => 10, 'question' => 'Isi P3K lengkap', 'require_photo' => 1],
      ['item_type_id' => 10, 'question' => 'Obat belum expired', 'require_photo' => 1],
    ];

    $this->db->table('checklist_templates')->insertBatch($data);
  }
}
