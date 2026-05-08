<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\HolidayModel;
use App\Models\PdamWaterLogModel;

class PdamWaterController extends BaseController
{
  protected PdamWaterLogModel $model;
  protected HolidayModel $holidayModel;

  public function __construct()
  {
    $this->model = new PdamWaterLogModel();
    $this->holidayModel = new HolidayModel();
  }

  public function index()
  {
    helper('checklist');

    if (! hasRole(['admin', 'compliance', 'office'])) {
      return redirect()->to('/unauthorized');
    }

    $monthpicker = (string) ($this->request->getGet('monthpicker') ?? '');
    if ($monthpicker && preg_match('/^\d{4}-\d{2}$/', $monthpicker)) {
      [$year, $month] = explode('-', $monthpicker);
    } else {
      $year = date('Y');
      $month = date('m');
    }

    $startDate = $year . '-' . $month . '-01';
    $endDate = date('Y-m-t', strtotime($startDate));

    $rows = $this->model
      ->where('log_date >=', $startDate)
      ->where('log_date <=', $endDate)
      ->orderBy('log_date', 'ASC')
      ->findAll();

    $logs = [];
    $latestMeter = null;

    foreach ($rows as $row) {
      $date = (string) ($row['log_date'] ?? '');
      if ($date === '') {
        continue;
      }

      $logs[$date] = [
        'id' => (int) ($row['id'] ?? 0),
        'log_time' => substr((string) ($row['log_time'] ?? ''), 0, 5),
        'meter_reading' => $row['meter_reading'] !== null ? (float) $row['meter_reading'] : null,
        'note' => (string) ($row['note'] ?? ''),
      ];

      if ($logs[$date]['meter_reading'] !== null) {
        $latestMeter = $logs[$date]['meter_reading'];
      }
    }

    $holidays = $this->holidayModel
      ->where('holiday_date >=', $startDate)
      ->where('holiday_date <=', $endDate)
      ->findAll();

    $holidayDates = array_column($holidays, 'holiday_date');

    return view('pdam_water/index', [
      'year' => $year,
      'month' => $month,
      'logs' => $logs,
      'holidayDates' => $holidayDates,
      'monthEntryCount' => count($logs),
      'latestMeter' => $latestMeter,
    ]);
  }

  public function detail($date)
  {
    helper('checklist');

    if (! hasRole(['admin', 'compliance', 'office'])) {
      return redirect()->to('/unauthorized');
    }

    if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)) {
      return redirect()->to('/pdam-water')->with('error', 'Tanggal tidak valid.');
    }

    $log = $this->model
      ->where('log_date', $date)
      ->first();

    $holidayDates = holiday_dates_between($date, $date);
    $isSunday = is_weekend_offday($date);
    $isHoliday = in_array($date, $holidayDates, true);

    return view('pdam_water/detail', [
      'date' => $date,
      'log' => $log,
      'isSunday' => $isSunday,
      'isHoliday' => $isHoliday,
    ]);
  }

  public function save()
  {
    if (! hasRole(['admin', 'compliance', 'office'])) {
      return $this->response->setStatusCode(403)->setJSON(['status' => 'error']);
    }

    $date = trim((string) $this->request->getPost('date'));
    $time = trim((string) $this->request->getPost('time'));
    $meterReading = $this->request->getPost('meter_reading');
    $note = $this->request->getPost('note');

    if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
      return $this->response->setStatusCode(422)->setJSON(['status' => 'error']);
    }

    $data = [
      'log_date' => $date,
      'log_time' => $time !== '' ? $time : null,
      'meter_reading' => $meterReading !== null && $meterReading !== '' ? (float) $meterReading : 0,
      'note' => $note,
      'created_by' => session()->get('user_id'),
    ];

    $existing = $this->model->where('log_date', $date)->first();

    if ($existing) {
      $this->model->update((int) $existing['id'], $data);
      $id = (int) $existing['id'];
    } else {
      $id = $this->model->insert($data);
    }

    return $this->response->setJSON([
      'status' => 'success',
      'id' => $id,
    ]);
  }

  public function delete()
  {
    if (! hasRole(['admin', 'compliance', 'office'])) {
      return $this->response->setStatusCode(403)->setJSON(['status' => 'error']);
    }

    $date = trim((string) $this->request->getPost('date'));
    if ($date !== '') {
      $existing = $this->model->where('log_date', $date)->first();
      if ($existing) {
        $this->model->delete((int) $existing['id']);
      }
    }

    return $this->response->setJSON(['status' => 'deleted']);
  }
}
