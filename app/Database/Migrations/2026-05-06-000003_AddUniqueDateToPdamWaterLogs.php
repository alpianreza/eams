<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniqueDateToPdamWaterLogs extends Migration
{
  public function up()
  {
    $this->forge->addUniqueKey('log_date', 'uniq_pdam_water_log_date');
    $this->forge->processIndexes('pdam_water_logs');
  }

  public function down()
  {
    $this->forge->dropKey('pdam_water_logs', 'uniq_pdam_water_log_date', true);
  }
}
