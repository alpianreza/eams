<?php

namespace App\Libraries;

use Mpdf\Mpdf;
use Config\Services;

class EamsPdf
{
  protected Mpdf $mpdf;

  public function __construct()
  {
    $this->mpdf = new Mpdf([
      'mode' => 'utf-8',
      'format' => 'A4',
      'margin_top' => 15,
      'margin_bottom' => 15,
      'margin_left' => 10,
      'margin_right' => 10,
    ]);

    $this->mpdf->SetFooter('{PAGENO}');
  }

  /**
   * Entry point export
   */
  public function export(string $type, array $data)
  {
    $html = match ($type) {
      'single'         => view('pdf/single_item', $this->prepareSingle($data)),
      'recap_period'   => view('pdf/recap_periodic', $data),
      'recap_year_item' => view('pdf/recap_item_yearly', $data),
      'attachment'     => view('pdf/attachment_ng', $data),
      default          => throw new \Exception('Jenis laporan tidak dikenali.')
    };

    $this->mpdf->WriteHTML($html);

    return $this->mpdf->Output($data['filename'] ?? 'laporan.pdf', 'I');
  }

  /**
   * Standarisasi simbol status
   */
  private function mapStatus(?string $status): string
  {
    return match ($status) {
      'ok'     => '✓',
      'not_ok' => '✗',
      'na'     => '–',
      default  => '–'
    };
  }

  /**
   * Prepare data khusus single item
   */
  private function prepareSingle(array $data): array
  {
    foreach ($data['questions'] as &$q) {
      $q['status_symbol'] = $this->mapStatus($q['status'] ?? null);
    }

    return $data;
  }
}
