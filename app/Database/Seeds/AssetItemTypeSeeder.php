<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AssetItemTypeSeeder extends Seeder
{
  public function run()
  {
    $db = \Config\Database::connect();

    // Ambil kategori berdasarkan code
    $categories = $db->table('inventory_categories')
      ->whereIn('code', ['FS', 'HSE', 'CTPAT', 'UTL'])
      ->get()
      ->getResultArray();

    $categoryMap = [];
    foreach ($categories as $cat) {
      $categoryMap[$cat['code']] = $cat['id'];
    }

    $data = [
      // 🔥 FIRE SAFETY
      [
        'inventory_category_id' => $categoryMap['FS'],
        'name' => 'Fire Extinguisher',
        'code' => 'FE',
        'active' => 1,
      ],
      [
        'inventory_category_id' => $categoryMap['FS'],
        'name' => 'Hydrant',
        'code' => 'HYD',
        'active' => 1,
      ],
      [
        'inventory_category_id' => $categoryMap['FS'],
        'name' => 'Fire Alarm',
        'code' => 'FA',
        'active' => 1,
      ],
      [
        'inventory_category_id' => $categoryMap['FS'],
        'name' => 'Emergency Light',
        'code' => 'EL',
        'active' => 1,
      ],
      [
        'inventory_category_id' => $categoryMap['FS'],
        'name' => 'Emergency Exit Door',
        'code' => 'EED',
        'active' => 1,
      ],
      [
        'inventory_category_id' => $categoryMap['FS'],
        'name' => 'Heat Detector',
        'code' => 'HD',
        'active' => 1,
      ],
      [
        'inventory_category_id' => $categoryMap['FS'],
        'name' => 'Smoke Detector',
        'code' => 'SD',
        'active' => 1,
      ],
      [
        'inventory_category_id' => $categoryMap['FS'],
        'name' => 'Intrusion Alarm',
        'code' => 'IA',
        'active' => 1,
      ],
      [
        'inventory_category_id' => $categoryMap['FS'],
        'name' => 'Exit Light Sign',
        'code' => 'ELS',
        'active' => 1,
      ],

      // 🦺 HSE
      [
        'inventory_category_id' => $categoryMap['HSE'],
        'name' => 'First Aid Box',
        'code' => 'P3K',
        'active' => 1,
      ],
      [
        'inventory_category_id' => $categoryMap['HSE'],
        'name' => 'Safety Sign',
        'code' => 'SS',
        'active' => 1,
      ],
      [
        'inventory_category_id' => $categoryMap['HSE'],
        'name' => 'Eye Wash Station',
        'code' => 'EWS',
        'active' => 1,
      ],

      // 🔐 CTPAT
      [
        'inventory_category_id' => $categoryMap['CTPAT'],
        'name' => 'CCTV',
        'code' => 'CCTV',
        'active' => 1,
      ],
      [
        'inventory_category_id' => $categoryMap['CTPAT'],
        'name' => 'Access Control',
        'code' => 'ACS',
        'active' => 1,
      ],

      // ⚡ UTILITIES
      [
        'inventory_category_id' => $categoryMap['UTL'],
        'name' => 'Refrigerator',
        'code' => 'REF',
        'active' => 1,
      ],
      [
        'inventory_category_id' => $categoryMap['UTL'],
        'name' => 'Uninterruptible Power Supply',
        'code' => 'UPS',
        'active' => 1,
      ],
      [
        'inventory_category_id' => $categoryMap['UTL'],
        'name' => 'Air Conditioner',
        'code' => 'AC',
        'active' => 1,
      ],
      [
        'inventory_category_id' => $categoryMap['UTL'],
        'name' => 'Generator Set',
        'code' => 'GENSET',
        'active' => 1,
      ],
      [
        'inventory_category_id' => $categoryMap['UTL'],
        'name' => 'Water Heater',
        'code' => 'WH',
        'active' => 1,
      ],
      [
        'inventory_category_id' => $categoryMap['UTL'],
        'name' => 'Pump',
        'code' => 'PUMP',
        'active' => 1,
      ],
      [
        'inventory_category_id' => $categoryMap['UTL'],
        'name' => 'Table',
        'code' => 'TBL',
        'active' => 1,
      ],
      [
        'inventory_category_id' => $categoryMap['UTL'],
        'name' => 'Chair',
        'code' => 'CHR',
        'active' => 1,
      ],
      [
        'inventory_category_id' => $categoryMap['UTL'],
        'name' => 'Desk',
        'code' => 'DSK',
        'active' => 1,
      ],
      [
        'inventory_category_id' => $categoryMap['UTL'],
        'name' => 'Cabinet',
        'code' => 'CBT',
        'active' => 1,
      ],
      [
        'inventory_category_id' => $categoryMap['UTL'],
        'name' => 'Whiteboard',
        'code' => 'WB',
        'active' => 1,
      ],
      [
        'inventory_category_id' => $categoryMap['UTL'],
        'name' => 'Projector',
        'code' => 'PJ',
        'active' => 1,
      ]

    ];


    $db->table('asset_item_types')->insertBatch($data);
  }
}
