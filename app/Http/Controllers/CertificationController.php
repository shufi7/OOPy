<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Services\CertificateEligibilityService;
use App\Services\CertificateIssuanceService;
use App\Services\CertificatePdfService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CertificationController extends Controller
{
    public function index(Request $request, CertificateEligibilityService $eligibility, CertificatePdfService $pdf)
    {
        $certificate = $request->user() ? $this->owned($request)->with('order')
            ->where('program_key', CertificateIssuanceService::PROGRAM)->first() : null;

        return view('certification.index', [
            'eligibility' => $request->user() ? $eligibility->forUser($request->user()) : null,
            'certificate' => $certificate,
            'qr' => $certificate ? $pdf->qrDataUri($certificate) : null,
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

    public function confirm(Request $request, CertificateIssuanceService $issuance)
    {
        $data = $request->validate([
            'payment_method' => ['required', Rule::in(array_keys(CertificateIssuanceService::METHODS))],
            'simulation_confirmed' => ['required', 'accepted'],
            'amount' => ['prohibited'], 'user_id' => ['prohibited'], 'status' => ['prohibited'],
            'payment_status' => ['prohibited'], 'mode' => ['prohibited'], 'program_key' => ['prohibited'],
        ]);
        $issuance->issue($request->user(), $data['payment_method']);

        return redirect()->to(route('certification.index').'#sertifikat')
            ->with('status', 'Simulasi Selesai. Sertifikat simulasi Anda siap diunduh. Tidak ada pembayaran nyata.');
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

    public function verify(string $publicId)
    {
        $certificate = Certificate::with('order')->where('public_id', $publicId)->first();

        return response()->view('certification.verify', ['certificate' => $certificate], $certificate ? 200 : 404)
            ->header('Cache-Control', 'no-store, public')->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }

    private function owned(Request $request)
    {
        return Certificate::where('user_id', $request->user()->id);
    }
}
