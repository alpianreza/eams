<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ChecklistScheduleSeeder extends Seeder
{
  public function run()
  {
    $data = [
      // FIRE SAFETY
      ['item_type_id' => 1, 'frequency' => 'monthly'], // APAR
      ['item_type_id' => 2, 'frequency' => 'monthly'], // Hydrant
      ['item_type_id' => 3, 'frequency' => 'weekly'],  // Fire Alarm

      // SECURITY / CTPAT
      ['item_type_id' => 13, 'frequency' => 'daily'],   // CCTV

      // HSE
      ['item_type_id' => 10, 'frequency' => 'monthly'], // P3K
    ];

    $this->db->table('checklist_schedules')->insertBatch($data);
  }
}
