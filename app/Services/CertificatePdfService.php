<?php

namespace App\Services;

use App\Models\Certificate;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;

class CertificatePdfService
{
    public function qrDataUri(Certificate $certificate): string
    {
        $writer = new Writer(new GDLibRenderer(400, 4));
        $png = $writer->writeString(route('certificates.verify', $certificate->public_id), 'UTF-8', ErrorCorrectionLevel::M());

        return 'data:image/png;base64,'.base64_encode($png);
    }

    public function generate(Certificate $certificate): string
    {
        $directory = storage_path('framework/cache/certificates');
        File::ensureDirectoryExists($directory);
        $options = new Options([
            'isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false,
            'chroot' => [public_path('images'), $directory], 'tempDir' => $directory,
            'fontDir' => $directory, 'fontCache' => $directory, 'defaultFont' => 'DejaVu Sans',
        ]);
        $pdf = new Dompdf($options);
        $pdf->setPaper('A4', 'landscape');
        $pdf->loadHtml(view('certification.pdf', [
            'certificate' => $certificate, 'qr' => $this->qrDataUri($certificate),
            'logo' => 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/oopy-logo.png'))),
        ])->render(), 'UTF-8');
        $pdf->render();

        return $pdf->output();
    }
}
