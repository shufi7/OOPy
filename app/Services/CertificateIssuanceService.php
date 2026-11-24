<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CertificateIssuanceService
{
    public const PROGRAM = 'oopy-python-oop-v1';

    public const COURSE = 'Pemrograman Berorientasi Objek dengan Python';

    public const AMOUNT = 24999;

    public const METHODS = [
        'e_wallet' => 'E-Wallet', 'virtual_account' => 'Virtual Account',
        'qr_payment' => 'QR Payment', 'card' => 'Kartu Debit/Kredit',
    ];

    public function __construct(
        private CertificateEligibilityService $eligibility,
        private CertificateTranscriptService $transcripts,
        private CertificateIdentityService $identities,
    ) {}

    public function issue(User $user, string $method): Certificate
    {
        abort_unless(array_key_exists($method, self::METHODS), 422);

        return DB::transaction(function () use ($user, $method) {
            // The same account lock is used by official quiz submission and account deletion.
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $existing = Certificate::where('user_id', $user->id)->where('program_key', self::PROGRAM)->first();
            if ($existing) {
                abort_unless($existing->isDownloadable(), 409, 'Sertifikat tidak aktif. Hubungi pengelola OOPy.');

                return $existing;
            }
            $eligibility = $this->eligibility->forUser($user);
            if (! $eligibility['eligible']) {
                throw ValidationException::withMessages(['eligibility' => 'Persyaratan sertifikasi belum terpenuhi. Periksa kembali progres belajar Anda.']);
            }
            $order = CertificateOrder::firstOrNew(['user_id' => $user->id, 'program_key' => self::PROGRAM]);
            $order->fill([
                'order_number' => $order->order_number ?? 'SIM-'.Str::upper(Str::random(24)),
                'amount' => self::AMOUNT, 'currency' => 'IDR', 'payment_method' => $method,
                'mode' => 'simulation', 'status' => 'simulation_completed', 'confirmed_at' => now(),
            ])->save();

            return Certificate::create([
                'user_id' => $user->id, 'program_key' => self::PROGRAM,
                'public_id' => (string) Str::uuid(),
                'certificate_number' => 'OOPY-SIM-'.Str::upper(bin2hex(random_bytes(12))),
                'participant_name' => $user->name, 'course_title' => self::COURSE,
                'completed_at' => $eligibility['completed_at'], 'issued_at' => now(),
                'order_id' => $order->id, 'status' => 'active',
            ]);
        }, 5);
    }

    public function issueSandbox(CertificateOrder $order): Certificate
    {
        return DB::transaction(function () use ($order) {
            $user = User::whereKey($order->user_id)->lockForUpdate()->firstOrFail();
            $order = CertificateOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            app(MidtransPaymentService::class)->assertOrder($order);
            abort_unless(config('midtrans.is_production') === false && $order->status === 'paid' && $order->verified_at
                && $order->paid_at && $order->eligible_completed_at && $order->paid_provider_order, 409, 'Pembayaran Sandbox belum terverifikasi.');
            $attempt = $order->attempts()->where('order_number', $order->paid_provider_order)->whereNotNull('verified_at')
                ->whereIn('status', ['settlement', 'capture'])->first();
            abort_unless($attempt, 409, 'Bukti status pembayaran belum tersedia.');

            $existing = $order->certificate;
            if ($existing) {
                return $existing;
            }
            abort_unless($this->identities->installed(), 503, 'Struktur transkrip sertifikat belum tersedia.');
            $eligibility = $this->eligibility->forUser($user);
            $transcript = $eligibility['eligible']
                ? $this->transcripts->fromEligibility($eligibility)
                : $attempt->academic_baseline;

            return Certificate::create([
                'user_id' => $user->id, 'program_key' => MidtransPaymentService::PROGRAM,
                'public_id' => (string) Str::uuid(), 'certificate_number' => 'OOPY-SBX-'.Str::upper(bin2hex(random_bytes(12))),
                'participant_name' => $order->participant_name ?? $user->name, 'course_title' => self::COURSE,
                'completed_at' => $order->eligible_completed_at,
                'transcript_snapshot' => $transcript,
                'issued_at' => now(), 'order_id' => $order->id, 'status' => 'active',
            ]);
        }, 5);
    }
}
