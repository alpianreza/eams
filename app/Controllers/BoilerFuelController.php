<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BoilerFuelModel;
use App\Models\HolidayModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class BoilerFuelController extends BaseController
{
  protected $model;
  protected $holidayModel;

  public function __construct()
  {
    $this->model = new BoilerFuelModel();
    $this->holidayModel = new HolidayModel();
  }

  public function index()
  {
    helper('checklist');

    $monthpicker = $this->request->getGet('monthpicker');

    if ($monthpicker) {
      [$year, $month] = explode('-', $monthpicker);
    } else {
      $year  = date('Y');
      $month = date('m');
    }

    $startDate = "$year-$month-01";
    $endDate   = date("Y-m-t", strtotime($startDate));

    // SUM per tanggal
    $builder = $this->model->builder();
    $builder->select('log_date, SUM(polybag) as total_polybag, SUM(kg) as total_kg');
    $builder->where('log_date >=', $startDate);
    $builder->where('log_date <=', $endDate);
    $builder->groupBy('log_date');

    $results = $builder->get()->getResultArray();

    $logs = [];
    foreach ($results as $row) {
      $logs[$row['log_date']] = $row;
    }

    // holiday
    $holidays = $this->holidayModel
      ->where('holiday_date >=', $startDate)
      ->where('holiday_date <=', $endDate)
      ->findAll();

    $holidayDates = array_column($holidays, 'holiday_date');

    return view('boiler/index', [
      'year'         => $year,
      'month'        => $month,
      'logs'         => $logs,
      'holidayDates' => $holidayDates
    ]);
  }

  public function detail($date)
  {
    helper('checklist');

    $logs = $this->model
      ->where('log_date', $date)
      ->orderBy('log_time', 'ASC')
      ->findAll();

    // cek holiday
    $holidayDates = holiday_dates_between($date, $date);
    $isSunday = is_weekend_offday($date);
    $isHoliday = in_array($date, $holidayDates, true);

    return view('boiler/detail', [
      'date'      => $date,
      'logs'      => $logs,
      'isSunday'  => $isSunday,
      'isHoliday' => $isHoliday
    ]);
  }

  public function save()
  {
    $id       = $this->request->getPost('id');
    $date     = $this->request->getPost('date');
    $time     = $this->request->getPost('time');
    $polybag  = $this->request->getPost('polybag') ?? 0;
    $kg       = $this->request->getPost('kg') ?? 0;
    $note     = $this->request->getPost('note');

    $data = [
      'log_date'   => $date,
      'log_time'   => $time,
      'polybag'    => $polybag,
      'kg'         => $kg,
      'note'       => $note,
      'created_by' => session()->get('user_id')
    ];

    if ($id) {
      $this->model->update($id, $data);
    } else {
      $id = $this->model->insert($data);
    }

    helper('audit');
    audit_log('boiler_fuel_save', 'Data bahan bakar boiler disimpan untuk tanggal: ' . $date);

    return $this->response->setJSON([
      'status' => 'success',
      'id'     => $id
    ]);
  }

  public function delete()
  {
    $id = $this->request->getPost('id');

    if ($id) {
      $this->model->delete($id);
    }

    helper('audit');
    audit_log('boiler_fuel_delete', 'Data bahan bakar boiler dihapus ID: ' . ($id ?? '-'));

    return $this->response->setJSON(['status' => 'deleted']);
  }

  public function export()
  {
    helper('checklist');

    $year  = $this->request->getGet('year');
    $month = $this->request->getGet('month');

    $startDate = "$year-$month-01";
    $endDate   = date("Y-m-t", strtotime($startDate));

    $builder = $this->model->builder();
    $builder->select('log_date, SUM(polybag) as total_polybag, SUM(kg) as total_kg');
    $builder->where('log_date >=', $startDate);
    $builder->where('log_date <=', $endDate);
    $builder->groupBy('log_date');

    $results = $builder->get()->getResultArray();

    $logs = [];
    foreach ($results as $row) {
      $logs[$row['log_date']] = $row;
    }

    $holidayDates = holiday_dates_between($startDate, $endDate);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // =========================
    // HEADER
    // =========================
    $sheet->mergeCells('A1:F1');
    $sheet->setCellValue('A1', 'LAPORAN PEMAKAIAN BAHAN BAKAR BOILER');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('A2', 'Bulan : ' . date('F Y', strtotime($startDate)));

    // =========================
    // HEADER TABLE
    // =========================
    $sheet->setCellValue('A4', 'NO');
    $sheet->setCellValue('B4', 'HARI');
    $sheet->setCellValue('C4', 'TANGGAL');
    $sheet->mergeCells('D4:E4');
    $sheet->setCellValue('D4', 'PEMAKAIAN BAHAN BAKAR');
    $sheet->setCellValue('F4', 'KETERANGAN');

    $sheet->setCellValue('D5', 'POLYBAG');
    $sheet->setCellValue('E5', 'KG');

    $sheet->mergeCells('A4:A5');
    $sheet->mergeCells('B4:B5');
    $sheet->mergeCells('C4:C5');
    $sheet->mergeCells('F4:F5');

    $sheet->getStyle('A4:F5')->getFont()->setBold(true);
    $sheet->getStyle('A4:F5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A4:F5')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $rowNum = 6;
    $totalPoly = 0;
    $totalKg   = 0;

    $daysInMonth = date('t', strtotime($startDate));

    $dayMap = [
      'Sunday' => 'Minggu',
      'Monday' => 'Senin',
      'Tuesday' => 'Selasa',
      'Wednesday' => 'Rabu',
      'Thursday' => 'Kamis',
      'Friday' => 'Jumat',
      'Saturday' => 'Sabtu'
    ];

    for ($d = 1; $d <= $daysInMonth; $d++) {

      $date = "$year-$month-" . sprintf('%02d', $d);
      $dayName = $dayMap[date('l', strtotime($date))];

      $poly = $logs[$date]['total_polybag'] ?? '';
      $kg   = $logs[$date]['total_kg'] ?? '';

      if ($poly !== '') $totalPoly += $poly;
      if ($kg !== '')   $totalKg += $kg;

      $sheet->setCellValue("A$rowNum", $d);
      $sheet->setCellValue("B$rowNum", $dayName);
      $sheet->setCellValue("C$rowNum", date('d-M-y', strtotime($date)));
      $sheet->setCellValue("D$rowNum", $poly);
      $sheet->setCellValue("E$rowNum", $kg);

      // Blok merah hari libur
      if (is_date_offday($date, $holidayDates)) {
        $sheet->getStyle("A$rowNum:F$rowNum")->getFill()
          ->setFillType(Fill::FILL_SOLID)
          ->getStartColor()->setARGB('FFDD9999');
      }

      $rowNum++;
    }

    // TOTAL
    $sheet->setCellValue("C$rowNum", 'Total');
    $sheet->setCellValue("D$rowNum", $totalPoly);
    $sheet->setCellValue("E$rowNum", $totalKg);
    $sheet->getStyle("C$rowNum:E$rowNum")->getFont()->setBold(true);

    // BORDER
    $sheet->getStyle("A4:F$rowNum")->getBorders()->getAllBorders()
      ->setBorderStyle(Border::BORDER_THIN);

    // AUTO WIDTH
    foreach (range('A', 'F') as $col) {
      $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $filename = "Laporan_Boiler_{$year}_{$month}.xlsx";

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=\"$filename\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
  }
}
