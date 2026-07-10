<?php

namespace App\Controllers;

use App\Models\IpalModel;
use App\Models\HolidayModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;


class IpalController extends BaseController
{
  protected $model;
  protected $holidayModel;

  public function __construct()
  {
    $this->model = new IpalModel();
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

    $dataList = $this->model
      ->where('log_date >=', $startDate)
      ->where('log_date <=', $endDate)
      ->findAll();

    $logs = [];
    foreach ($dataList as $row) {
      $logs[$row['log_date']] = $row;
    }

    $holidays = $this->holidayModel
      ->where('holiday_date >=', $startDate)
      ->where('holiday_date <=', $endDate)
      ->findAll();

    $holidayDates = array_column($holidays, 'holiday_date');

    return view('ipal/index', [
      'year' => $year,
      'month' => $month,
      'logs' => $logs,
      'holidayDates' => $holidayDates
    ]);
  }

  public function save()
  {
    $date = $this->request->getPost('date');

    $data = [
      'log_date'     => $date,
      'start_meter'  => $this->request->getPost('start'),
      'stop_meter'   => $this->request->getPost('stop'),
      'pemakaian'    => $this->request->getPost('pemakaian'),
      'ket'          => $this->request->getPost('ket'),
      'created_by'   => session()->get('user_id')
    ];

    $existing = $this->model->where('log_date', $date)->first();

    if ($existing) {
      $this->model->update($existing['id'], $data);
    } else {
      $this->model->insert($data);
    }

    helper('audit');
    audit_log('ipal_save', 'Data IPAL disimpan untuk tanggal: ' . $date);

    return $this->response->setJSON(['status' => 'ok']);
  }

  public function export()
  {
    helper('checklist');

    $year  = $this->request->getGet('year');
    $month = $this->request->getGet('month');

    $startDate = "$year-$month-01";
    $endDate = date("Y-m-t", strtotime($startDate));

    $dataList = $this->model
      ->where('log_date >=', $startDate)
      ->where('log_date <=', $endDate)
      ->findAll();

    $logs = [];
    foreach ($dataList as $row) {
      $logs[$row['log_date']] = $row;
    }

    $holidayDates = holiday_dates_between($startDate, $endDate);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // ================= HEADER =================

    $sheet->mergeCells('A1:G1');
    $sheet->setCellValue('A1', 'LAPORAN LIMBAH DOMESTIK (IPAL)');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('A2', 'Bulan : ' . date('F Y', strtotime($startDate)));

    // ================= HEADER TABLE =================

    $sheet->setCellValue('A4', 'NO');
    $sheet->setCellValue('B4', 'HARI');
    $sheet->setCellValue('C4', 'TANGGAL');
    $sheet->setCellValue('D4', 'START');
    $sheet->setCellValue('E4', 'STOP');
    $sheet->setCellValue('F4', 'PEMAKAIAN (M³)');
    $sheet->setCellValue('G4', 'KET');

    $sheet->getStyle('A4:G4')->getFont()->setBold(true);
    $sheet->getStyle('A4:G4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $rowNum = 5;

    $dayMap = [
      'Sunday' => 'Minggu',
      'Monday' => 'Senin',
      'Tuesday' => 'Selasa',
      'Wednesday' => 'Rabu',
      'Thursday' => 'Kamis',
      'Friday' => 'Jumat',
      'Saturday' => 'Sabtu'
    ];

    $days = date('t', strtotime($startDate));

    for ($d = 1; $d <= $days; $d++) {

      $date = "$year-$month-" . sprintf('%02d', $d);
      $dayName = $dayMap[date('l', strtotime($date))];

      $row = $logs[$date] ?? null;

      $sheet->setCellValue("A$rowNum", $d);
      $sheet->setCellValue("B$rowNum", $dayName);
      $sheet->setCellValue("C$rowNum", date('d-M-y', strtotime($date)));
      $sheet->setCellValue("D$rowNum", $row['start_meter'] ?? '');
      $sheet->setCellValue("E$rowNum", $row['stop_meter'] ?? '');
      $sheet->setCellValue("F$rowNum", $row['pemakaian'] ?? '');
      $sheet->setCellValue("G$rowNum", $row['ket'] ?? '');

      // Hari libur merah
      if (is_date_offday($date, $holidayDates)) {
        $sheet->getStyle("A$rowNum:G$rowNum")
          ->getFill()->setFillType(Fill::FILL_SOLID)
          ->getStartColor()->setARGB('FFFFCCCC');
      }

      $rowNum++;
    }

    // BORDER

    $sheet->getStyle("A4:G$rowNum")
      ->getBorders()
      ->getAllBorders()
      ->setBorderStyle(Border::BORDER_THIN);

    foreach (range('A', 'G') as $col) {
      $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $filename = "IPAL_Report_{$year}_{$month}.xlsx";

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=\"$filename\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
  }
}
