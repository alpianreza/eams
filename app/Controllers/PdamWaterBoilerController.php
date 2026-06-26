<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\HolidayModel;
use App\Models\PdamWaterBoilerLogModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PdamWaterBoilerController extends BaseController
{
  protected PdamWaterBoilerLogModel $model;
  protected HolidayModel $holidayModel;

  public function __construct()
  {
    $this->model = new PdamWaterBoilerLogModel();
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

    $period = $this->buildMonthlyDataset((string) $year, (string) $month);

    return view('pdam_water_boiler/index', [
      'year' => $year,
      'month' => $month,
      'logs' => $period['logs'],
      'holidayDates' => $period['holidayDates'],
      'monthEntryCount' => count($period['logs']),
      'latestMeter' => $period['latestMeter'],
    ]);
  }

  public function exportExcel()
  {
    helper('checklist');

    if (! hasRole(['admin', 'compliance', 'office'])) {
      return redirect()->to('/unauthorized');
    }

    [$year, $month] = $this->resolveMonthRequest();
    $period = $this->buildMonthlyDataset($year, $month);
    $startDate = $period['startDate'];

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->mergeCells('A1:G1');
    $sheet->setCellValue('A1', 'LAPORAN PENGECEKAN AIR PDAM BOILER');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('A2', 'Bulan : ' . date('F Y', strtotime($startDate)));

    $sheet->setCellValue('A4', 'NO');
    $sheet->setCellValue('B4', 'HARI');
    $sheet->setCellValue('C4', 'TANGGAL');
    $sheet->setCellValue('D4', 'JAM');
    $sheet->setCellValue('E4', 'METERAN AIR');
    $sheet->setCellValue('F4', 'KETERANGAN');
    $sheet->setCellValue('G4', 'STATUS');

    $sheet->getStyle('A4:G4')->getFont()->setBold(true);
    $sheet->getStyle('A4:G4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A4:G4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $dayMap = $this->dayMap();
    $rowNum = 5;
    $daysInMonth = (int) date('t', strtotime($startDate));

    for ($d = 1; $d <= $daysInMonth; $d++) {
      $date = $year . '-' . $month . '-' . sprintf('%02d', $d);
      $dayName = $dayMap[date('l', strtotime($date))] ?? date('l', strtotime($date));
      $row = $period['logs'][$date] ?? null;
      $isOff = is_date_offday($date, $period['holidayDates']);
      $status = $isOff ? 'Libur' : ($row ? 'Terisi' : 'Belum');

      $sheet->setCellValue("A{$rowNum}", $d);
      $sheet->setCellValue("B{$rowNum}", $dayName);
      $sheet->setCellValue("C{$rowNum}", date('d-M-y', strtotime($date)));
      $sheet->setCellValue("D{$rowNum}", $row['log_time'] ?? '');
      $sheet->setCellValue("E{$rowNum}", $row['meter_reading'] ?? '');
      $sheet->setCellValue("F{$rowNum}", $row['note'] ?? '');
      $sheet->setCellValue("G{$rowNum}", $status);

      if ($isOff) {
        $sheet->getStyle("A{$rowNum}:G{$rowNum}")
          ->getFill()->setFillType(Fill::FILL_SOLID)
          ->getStartColor()->setARGB('FFFFCCCC');
      }

      $rowNum++;
    }

    $sheet->getStyle("A4:G" . ($rowNum - 1))
      ->getBorders()
      ->getAllBorders()
      ->setBorderStyle(Border::BORDER_THIN);

    foreach (range('A', 'G') as $column) {
      $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    $filename = "PDAM_Water_Boiler_Report_{$year}_{$month}.xlsx";

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=\"{$filename}\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
  }

  public function exportPdf()
  {
    helper('checklist');

    if (! hasRole(['admin', 'compliance', 'office'])) {
      return redirect()->to('/unauthorized');
    }

    [$year, $month] = $this->resolveMonthRequest();
    $period = $this->buildMonthlyDataset($year, $month);
    $startDate = $period['startDate'];
    $dayMap = $this->dayMap();
    $daysInMonth = (int) date('t', strtotime($startDate));

    $rows = [];
    for ($d = 1; $d <= $daysInMonth; $d++) {
      $date = $year . '-' . $month . '-' . sprintf('%02d', $d);
      $row = $period['logs'][$date] ?? null;
      $isOff = is_date_offday($date, $period['holidayDates']);
      $rows[] = [
        'no' => $d,
        'day_name' => $dayMap[date('l', strtotime($date))] ?? date('l', strtotime($date)),
        'date_label' => date('d M Y', strtotime($date)),
        'time' => $row['log_time'] ?? '',
        'meter' => $row['meter_reading'] ?? '',
        'note' => $row['note'] ?? '',
        'status' => $isOff ? 'Libur' : ($row ? 'Terisi' : 'Belum'),
        'is_offday' => $isOff,
      ];
    }

    $html = view('pdam_water_boiler/export_pdf', [
      'title' => 'Laporan Pengecekan Air PDAM Boiler',
      'periodLabel' => date('F Y', strtotime($startDate)),
      'rows' => $rows,
    ]);

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    return $this->response
      ->setHeader('Content-Type', 'application/pdf')
      ->setHeader('Content-Disposition', 'inline; filename="PDAM_Water_Boiler_Report_' . $year . '_' . $month . '.pdf"')
      ->setBody($dompdf->output());
  }

  public function detail($date)
  {
    helper('checklist');

    if (! hasRole(['admin', 'compliance', 'office'])) {
      return redirect()->to('/unauthorized');
    }

    if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)) {
      return redirect()->to('/pdam-water-boiler')->with('error', 'Tanggal tidak valid.');
    }

    $log = $this->model
      ->where('log_date', $date)
      ->first();

    $holidayDates = holiday_dates_between($date, $date);
    $isSunday = is_weekend_offday($date);
    $isHoliday = in_array($date, $holidayDates, true);

    return view('pdam_water_boiler/detail', [
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

  private function resolveMonthRequest(): array
  {
    $monthpicker = trim((string) ($this->request->getGet('monthpicker') ?? ''));
    $year = trim((string) ($this->request->getGet('year') ?? ''));
    $month = trim((string) ($this->request->getGet('month') ?? ''));

    if ($monthpicker !== '' && preg_match('/^\d{4}-\d{2}$/', $monthpicker)) {
      [$year, $month] = explode('-', $monthpicker);
    }

    if (! preg_match('/^\d{4}$/', $year) || ! preg_match('/^\d{2}$/', $month)) {
      $year = date('Y');
      $month = date('m');
    }

    return [$year, $month];
  }

  private function buildMonthlyDataset(string $year, string $month): array
  {
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

    return [
      'startDate' => $startDate,
      'endDate' => $endDate,
      'logs' => $logs,
      'holidayDates' => array_column($holidays, 'holiday_date'),
      'latestMeter' => $latestMeter,
    ];
  }

  private function dayMap(): array
  {
    return [
      'Sunday' => 'Minggu',
      'Monday' => 'Senin',
      'Tuesday' => 'Selasa',
      'Wednesday' => 'Rabu',
      'Thursday' => 'Kamis',
      'Friday' => 'Jumat',
      'Saturday' => 'Sabtu',
    ];
  }
}
