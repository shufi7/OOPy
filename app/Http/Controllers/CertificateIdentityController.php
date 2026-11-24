<?php

namespace App\Http\Controllers;

use App\Services\CertificateIdentityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CertificateIdentityController extends Controller
{
    public function confirm(Request $request, CertificateIdentityService $identities): RedirectResponse
    {
        $data = $request->validate([
            'confirmed_name' => ['required', 'string', 'max:100'],
            'name_confirmed' => ['required', 'accepted'],
        ], ['name_confirmed.accepted' => 'Centang persetujuan bahwa nama pada sertifikat sudah benar.']);
        $identities->confirm($request->user(), $data['confirmed_name']);

        return redirect()->to(route('certification.index').'#nama-sertifikat');
    }

    public function update(Request $request, CertificateIdentityService $identities): RedirectResponse
    {
        try {
            $data = $this->validatedNameChange($request);
            $identities->change($request->user(), $data['participant_name'], $data['password']);
        } catch (ValidationException $exception) {
            throw $exception->errorBag('certificateName');
        }

        return redirect()->to(route('certification.index').'#nama-sertifikat')
            ->with('certificate_status', 'Nama sertifikat berhasil diperbarui. Silakan periksa dan konfirmasi kembali nama Anda.');
    }

    public function correct(Request $request, string $publicId, CertificateIdentityService $identities): RedirectResponse
    {
        $returnUrl = route('certification.index').'#nama-sertifikat';
        try {
            $data = $this->validatedNameChange($request, [
                'name_confirmed' => ['required', 'accepted'],
                'name_revision' => ['required', 'integer', 'min:0'],
            ]);
            $corrected = $identities->correct($request->user(), $publicId, $data['participant_name'], $data['password'], (int) $data['name_revision']);
        } catch (ValidationException $exception) {
            throw $exception->errorBag('issuedCertificateName')->redirectTo($returnUrl);
        }

        return redirect()->to($returnUrl)->with('certificate_status', $corrected
            ? 'Koreksi nama tersimpan. Unduh PDF terbaru untuk menggunakan nama yang sudah diperbarui.'
            : 'Nama pada sertifikat sudah sesuai.');
    }

    private function validatedNameChange(Request $request, array $additionalRules = []): array
    {
        $data = $request->validate([
            'participant_name' => ['required', 'string', 'max:400'],
            'password' => ['required', 'string', 'max:255'],
            ...$additionalRules,
        ], [
            'participant_name.required' => 'Nama lengkap wajib diisi.',
            'password.required' => 'Masukkan password akun OOPy Anda.',
            'name_confirmed.required' => 'Centang konfirmasi bahwa nama pada sertifikat sudah benar.',
            'name_confirmed.accepted' => 'Centang konfirmasi bahwa nama pada sertifikat sudah benar.',
        ]);
        $name = CertificateIdentityService::normalize($data['participant_name']);
        if ($name === null) {
            throw ValidationException::withMessages([
                'participant_name' => 'Gunakan 2–100 karakter. Nama boleh berisi huruf, spasi, apostrof, titik, dan tanda hubung.',
            ]);
        }

        return array_replace($data, ['participant_name' => $name]);
    }
}
