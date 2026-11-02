<?php

namespace App\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrCodeService
{
    /**
     * Hasilkan QR Code dalam format SVG Data URI (base64)
     * yang kompatibel dengan DomPDF, printer browser, maupun tampilan web publik.
     */
    public function generateSvgDataUri(string $data): string
    {
        $options = new QROptions([
            'quietzoneSize' => 2,
        ]);

        return (new QRCode($options))->render($data);
    }
}
