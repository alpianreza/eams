<?php

namespace App\Libraries;

use App\Models\AppSettingModel;
use Mpdf\Mpdf;

class EamsPdf
{
  protected Mpdf $mpdf;

  public function __construct()
  {
    $this->mpdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_top' => 15, 'margin_bottom' => 15, 'margin_left' => 10, 'margin_right' => 10]);
  }

  public function export(string $type, array $data)
  {
    $data = $this->applyCompanySettings($data);
    $landscape = in_array($type, ['daily', 'daily_toilet', 'batch_form'], true);
    $this->mpdf = new Mpdf([
      'mode' => 'utf-8',
      'format' => $landscape ? 'A4-L' : 'A4',
      'margin_top' => $type === 'batch_form' ? 7 : ($landscape ? 10 : 15),
      'margin_bottom' => $type === 'batch_form' ? 7 : ($landscape ? 10 : 15),
      'margin_left' => $type === 'batch_form' ? 6 : ($landscape ? 8 : 10),
      'margin_right' => $type === 'batch_form' ? 6 : ($landscape ? 8 : 10),
    ]);

    $footer = trim((string) ($data['companySettings']['document_footer'] ?? ''));
    $this->mpdf->SetFooter(($footer !== '' ? $footer . ' | ' : '') . '{PAGENO}');

    $html = match ($type) {
      'single' => view('pdf/single_item', $this->prepareSingle($data)),
      'daily' => view('pdf/recap_daily', $data),
      'daily_toilet' => view('pdf/recap_daily_toilet', $data),
      'weekly' => view('pdf/recap_weekly', $data),
      'recap_period' => view('pdf/recap_periodic', $data),
      'recap_year_item' => view('pdf/recap_item_yearly', $data),
      'batch_form' => view('pdf/batch_form', $data),
      'questionnaire_response' => view('pdf/questionnaire_response', $data),
      'attachment' => view('pdf/attachment_ng', $data),
      default => throw new \Exception('Jenis laporan tidak dikenali.'),
    };

    $html = $this->replaceLegacyBranding($html, $data['companySettings']);
    $this->mpdf->WriteHTML($html);
    while (ob_get_level()) ob_end_clean();
    return $this->mpdf->Output($data['filename'] ?? 'laporan.pdf', 'I');
  }

  private function applyCompanySettings(array $data): array
  {
    $settings = db_connect()->tableExists('app_settings') ? (new AppSettingModel())->allAsMap() : [];
    $name = trim((string) ($settings['company_name'] ?? '')) ?: 'PT. YOUNGHYUN STAR';
    $address = trim((string) ($settings['company_address'] ?? ''));
    $logoRelative = trim((string) ($settings['company_logo'] ?? 'assets/images/company/logo.png'));
    $logoPath = FCPATH . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $logoRelative), DIRECTORY_SEPARATOR);
    if (! is_file($logoPath)) $logoPath = FCPATH . 'assets/images/company/logo.png';

    $company = [
      'name' => $name,
      'address' => array_values(array_filter(preg_split('/\r\n|\r|\n/', $address) ?: [])),
      'logo_path' => str_replace('\\', '/', $logoPath),
      'document_footer' => (string) ($settings['document_footer'] ?? ''),
      'signatory_name' => (string) ($settings['document_signatory_name'] ?? ''),
      'signatory_title' => (string) ($settings['document_signatory_title'] ?? ''),
    ];
    $data['companySettings'] = $company;
    if (isset($data['layout']) && is_array($data['layout'])) {
      $data['layout']['company'] = ['name' => $company['name'], 'address' => $company['address'], 'logo_path' => $company['logo_path']];
      if ($company['signatory_name'] !== '') $data['layout']['configuredSignatory'] = ['name' => $company['signatory_name'], 'title' => $company['signatory_title']];
    }
    return $data;
  }

  private function replaceLegacyBranding(string $html, array $company): string
  {
    $safeName = htmlspecialchars((string) ($company['name'] ?? 'EAMS'), ENT_QUOTES, 'UTF-8');
    $html = str_replace(['PT. YOUNGHYUN STAR', 'PT YOUNGHYUN STAR', 'PT. YoungHyun Star', 'PT YoungHyun Star'], $safeName, $html);
    $legacyLogo = str_replace('\\', '/', FCPATH . 'assets/images/company/logo.png');
    $newLogo = (string) ($company['logo_path'] ?? $legacyLogo);
    return str_replace([$legacyLogo, str_replace('/', '\\', $legacyLogo)], $newLogo, $html);
  }

  private function mapStatus(?string $status): string
  {
    return match ($status) { 'ok' => '&#10003;', 'not_ok' => '&#10007;', 'na' => '-', default => '-' };
  }

  private function prepareSingle(array $data): array
  {
    foreach ($data['questions'] as &$question) $question['status_symbol'] = $this->mapStatus($question['status'] ?? null);
    unset($question);
    return $data;
  }
}
