<?php

namespace App\Services;

class QrService
{
  public function generate(int $id, string $assetCode): string
  {
    $detailUrl = base_url('compliance/inventory/detail/' . $id);

    $qrFile = 'qr_inv_' . $id . '.png';
    $qrPath = FCPATH . 'uploads/qr/' . $qrFile;

    $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data='
      . urlencode($detailUrl);

    $qrContent = @file_get_contents($qrApiUrl);
    if (!$qrContent) return '';

    file_put_contents($qrPath, $qrContent);

    $image = imagecreatefrompng($qrPath);

    $black = imagecolorallocate($image, 0, 0, 0);
    $white = imagecolorallocate($image, 255, 255, 255);
    $font = 5;

    $imgW = imagesx($image);
    $imgH = imagesy($image);

    $textW = imagefontwidth($font) * strlen($assetCode);
    $textH = imagefontheight($font);

    $x = (int)floor(($imgW - $textW) / 2);
    $y = (int)floor(($imgH - $textH) / 2);

    imagefilledrectangle($image, $x - 4, $y - 3, $x + $textW + 4, $y + $textH + 3, $white);
    imagestring($image, $font, $x, $y, $assetCode, $black);

    imagepng($image, $qrPath);
    imagedestroy($image);

    return $qrFile;
  }
}
