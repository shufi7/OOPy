<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\GoogleCertificatePasswordController;
use App\Http\Controllers\Auth\GoogleLoginController;
use App\Models\Certificate;
use App\Models\CertificateNameRevision;
use App\Services\CertificateEligibilityService;
use App\Services\CertificateIdentityService;
use App\Services\CertificateIssuanceService;
use App\Services\CertificatePdfService;
use App\Services\CertificateTranscriptService;
use App\Services\MidtransGateway;
use App\Services\MidtransPaymentService;
use Illuminate\Http\Request;

class CertificationController extends Controller
{
    public function index(
        Request $request,
        CertificateEligibilityService $eligibility,
        CertificateIdentityService $identities,
        CertificateTranscriptService $transcripts,
        CertificatePdfService $pdf,
        MidtransPaymentService $payments,
        MidtransGateway $gateway,
        GoogleCertificatePasswordController $password,
    ) {
        $user = $request->user();
        $certificates = $user ? $this->owned($request)->with('order')->get() : collect();
        $sandboxCertificate = $certificates->firstWhere('program_key', MidtransPaymentService::PROGRAM);
        $legacyCertificate = $certificates->firstWhere('program_key', CertificateIssuanceService::PROGRAM);
        $attempt = $user ? $payments->latest($user) : null;
        $progress = $user ? $eligibility->forUser($user) : null;

        return view('certification.index', [
            'eligibility' => $progress,
            'identity' => $user ? $identities->forUser($user, $attempt, $sandboxCertificate) : null,
            'transcript' => $sandboxCertificate ? $sandboxCertificate->transcript_snapshot : ($progress ? $transcripts->fromEligibility($progress) : null),
            'certificate' => $sandboxCertificate, 'legacyCertificate' => $legacyCertificate,
            'qr' => $sandboxCertificate ? $pdf->qrDataUri($sandboxCertificate) : null,
            'legacyQr' => $legacyCertificate ? $pdf->qrDataUri($legacyCertificate) : null,
            'canCaptureTranscript' => $sandboxCertificate?->isDownloadable()
                && array_key_exists('transcript_snapshot', $sandboxCertificate->getAttributes()) && $sandboxCertificate->transcript_snapshot === null,
            'nameCorrectionAvailable' => $sandboxCertificate && CertificateNameRevision::installed(),
            'googlePasswordAvailable' => $user?->google_id && GoogleLoginController::configured(),
            'googlePasswordReady' => $user && $password->ready($request),
            'payment' => $attempt ? $payments->state($attempt) : null,
            'paymentAvailable' => $payments->installed() && $gateway->configured(),
        ]);
    }

    public function checkout(Request $request, CertificateEligibilityService $eligibility)
    {
        if ($this->owned($request)->where('program_key', CertificateIssuanceService::PROGRAM)->exists()) {
            return redirect()->to(route('certification.index').'#sertifikat');
        }
        if (! $eligibility->forUser($request->user())['eligible']) {
            return redirect()->route('certification.index')->with('status', 'Persyaratan sertifikasi belum terpenuhi. Selesaikan persyaratan yang tercantum di bawah.');
        }

        return redirect()->to(route('certification.index').'#penerbitan');
    }

    public function confirm(Request $request)
    {
        abort(410, 'Penerbitan simulasi langsung sudah ditutup. Gunakan pembayaran Midtrans Sandbox pada halaman sertifikasi.');
    }

    public function certificates()
    {
        return redirect()->to(route('certification.index').'#sertifikat');
    }

    public function show(Request $request, string $publicId)
    {
        $this->owned($request)->where('public_id', $publicId)->firstOrFail();

        return redirect()->to(route('certification.index').'#sertifikat');
    }

    public function download(Request $request, string $publicId, CertificatePdfService $pdf)
    {
        $certificate = $this->owned($request)->with('order')->where('public_id', $publicId)->firstOrFail();
        abort_unless($certificate->isDownloadable(), 409, 'Sertifikat tidak aktif dan tidak dapat diunduh.');

        return response($pdf->generate($certificate), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$certificate->certificate_number.'.pdf"',
        ]);
    }

    public function captureTranscript(Request $request, string $publicId, CertificateTranscriptService $transcripts)
    {
        $transcripts->captureForArchivedCertificate($request->user(), $publicId);

        return redirect()->to(route('certification.index').'#transcript-panel')
            ->with('status', 'Transkrip nilai tersedia. Unduh PDF terbaru untuk mendapatkan kedua halaman.');
    }

    public function verify(string $publicId)
    {
        $certificate = Certificate::with('order')->where('public_id', $publicId)->first();

        return response()->view('certification.verify', ['certificate' => $certificate, 'sandboxMode' => $certificate?->order?->mode === 'sandbox'], $certificate ? 200 : 404)
            ->header('Cache-Control', 'no-store, public')->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }

    private function owned(Request $request)
    {
        return Certificate::where('user_id', $request->user()->id);
    }
}
