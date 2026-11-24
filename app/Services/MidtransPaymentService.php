<?php

namespace App\Services;

use App\Models\CertificateIdentity;
use App\Models\CertificateOrder;
use App\Models\CertificatePaymentAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MidtransPaymentService
{
    public const PROGRAM = 'oopy-python-oop-midtrans-sandbox-v1';

    public function __construct(
        private MidtransGateway $gateway,
        private CertificateEligibilityService $eligibility,
        private CertificateIdentityService $identities,
        private CertificateTranscriptService $transcripts,
    ) {}

    public function installed(): bool
    {
        return Schema::hasTable('certificate_payment_attempts') && Schema::hasColumn('certificate_orders', 'provider');
    }

    public function start(User $user, ?string $displayedName = null): CertificatePaymentAttempt
    {
        $this->gateway->assertSandbox();
        abort_unless($this->installed(), 503, 'Struktur pembayaran Sandbox belum dipasang. Hubungi pengelola OOPy.');
        $previous = $this->latest($user);
        if ($previous && $previous->expires_at->isPast() && ! $previous->canBeReplaced()) {
            $this->synchronize($previous);
        }
        [$attempt, $create] = DB::transaction(function () use ($user, $displayedName) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $order = CertificateOrder::where('user_id', $user->id)->where('program_key', self::PROGRAM)->lockForUpdate()->first();
            if ($order && in_array($order->status, ['paid', 'refunded'], true)) {
                abort_unless($order->certificate?->isDownloadable(), 409, 'Sertifikat Sandbox tidak aktif. Pembayaran ulang tidak tersedia.');

                return [$order->attempts()->where('order_number', $order->paid_provider_order)->firstOrFail(), false];
            }
            if ($order) {
                $this->assertOrder($order);
            }
            $attempt = $order?->attempts()->latest('id')->lockForUpdate()->first();
            if (! $attempt || $attempt->canBeReplaced()) {
                $eligible = $this->eligibility->forUser($user);
                if (! $eligible['eligible']) {
                    throw ValidationException::withMessages(['eligibility' => 'Persyaratan akademik sertifikasi belum terpenuhi.']);
                }
                abort_unless($this->identities->installed(), 503, 'Konfirmasi nama sertifikat belum tersedia.');
                $identity = CertificateIdentity::find($user->id);
                if (! $identity?->confirmed_at || ($displayedName !== null && $displayedName !== $identity->participant_name)) {
                    throw ValidationException::withMessages(['confirmed_name' => 'Periksa dan konfirmasi nama sertifikat sebelum memulai pembayaran.']);
                }
                $order ??= new CertificateOrder(['user_id' => $user->id, 'program_key' => self::PROGRAM,
                    'order_number' => 'OOPY-SBX-'.Str::upper(bin2hex(random_bytes(12))), 'amount' => CertificateIssuanceService::AMOUNT,
                    'currency' => 'IDR', 'mode' => 'sandbox', 'provider' => 'midtrans', 'payment_method' => 'qris', 'status' => 'pending']);
                $order->fill(['participant_name' => $identity->participant_name,
                    'eligible_completed_at' => $order->eligible_completed_at ?? $eligible['completed_at']])->save();
                $attempt = $order->attempts()->create(['public_id' => (string) Str::uuid(),
                    'order_number' => 'SBX-'.Str::upper(bin2hex(random_bytes(16))), 'expires_at' => now()->addDay(),
                    'participant_name' => $identity->participant_name,
                    'academic_baseline' => $this->transcripts->fromEligibility($eligible) + ['captured_at_checkout' => true]]);
            }
            if ($displayedName !== null && $attempt->participant_name !== null && $displayedName !== $attempt->participant_name) {
                throw ValidationException::withMessages(['confirmed_name' => 'Nama pada halaman sudah berubah. Muat ulang halaman sebelum melanjutkan pembayaran.']);
            }
            if ($attempt->snap_token) {
                return [$attempt, false];
            }
            abort_if($attempt->expires_at->isPast() || in_array($attempt->status, ['pending', 'capture'], true), 409,
                'Referensi pembayaran masih aktif di Midtrans. Gunakan Cek Status Pembayaran.');
            abort_if($attempt->request_started_at?->gt(now()->subSeconds(30)), 409, 'Transaksi sedang disiapkan. Tunggu sebentar, lalu cek kembali.');
            $attempt->update(['status' => 'creating', 'request_started_at' => now()]);

            return [$attempt, true];
        }, 5);
        if (! $create) {
            return $attempt;
        }
        try {
            $snap = $this->gateway->create($attempt, $user);
        } catch (HttpException $exception) {
            CertificatePaymentAttempt::whereKey($attempt->id)->where('status', 'creating')
                ->update(['status' => 'api_error', 'request_started_at' => null]);
            throw $exception;
        }

        return DB::transaction(function () use ($user, $attempt, $snap) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $attempt = CertificatePaymentAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            $attempt->fill(['snap_token' => $snap['token'], 'redirect_url' => $snap['redirect_url'], 'request_started_at' => null]);
            if ($attempt->status === 'creating') {
                $attempt->status = 'snap_ready';
            }
            $attempt->save();

            return $attempt;
        }, 5);
    }

    public function latest(User $user): ?CertificatePaymentAttempt
    {
        if (! $this->installed()) {
            return null;
        }

        return CertificatePaymentAttempt::whereHas('order', fn ($query) => $query->where('user_id', $user->id)
            ->where('program_key', self::PROGRAM)->where('provider', 'midtrans')->where('mode', 'sandbox'))->latest('id')->first();
    }

    public function synchronize(CertificatePaymentAttempt $attempt, ?array $notification = null): CertificatePaymentAttempt
    {
        $this->assertOrder($attempt->order);
        $data = $this->gateway->status($attempt->order_number);
        if ($data === null) {
            CertificatePaymentAttempt::whereKey($attempt->id)->where('expires_at', '<=', now())
                ->whereIn('status', ['snap_ready', 'api_error', 'creating'])
                ->where(fn ($query) => $query->whereNull('request_started_at')->orWhere('request_started_at', '<=', now()->subSeconds(30)))
                ->update(['status' => 'expired_unselected', 'request_started_at' => null]);

            return $attempt->fresh();
        }
        $this->validateStatus($attempt, $data, $notification);

        return DB::transaction(function () use ($attempt, $data) {
            User::whereKey($attempt->order->user_id)->lockForUpdate()->firstOrFail();
            $order = CertificateOrder::whereKey($attempt->certificate_order_id)->lockForUpdate()->firstOrFail();
            $attempt = CertificatePaymentAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            $this->assertOrder($order);
            $this->validateStatus($attempt, $data);
            $status = $data['transaction_status'];
            $refund = in_array($status, ['refund', 'partial_refund'], true);
            $success = $data['status_code'] === '200' && (($status === 'settlement' && in_array($data['fraud_status'] ?? 'accept', ['accept'], true))
                || ($status === 'capture' && $data['payment_type'] === 'credit_card' && ($data['fraud_status'] ?? null) === 'accept'));
            if ($order->status === 'refunded' || (in_array($attempt->status, ['settlement', 'capture'], true)
                && $order->status === 'paid' && ! $refund && ! in_array($status, ['settlement', 'capture'], true))
                || ($attempt->canBeReplaced() && ! $success && ! $refund && in_array($status, ['pending', 'capture', 'settlement'], true))) {
                return $attempt;
            }
            $attempt->fill(['status' => $status, 'provider_transaction_id' => $data['transaction_id'],
                'merchant_id' => $data['merchant_id'], 'payment_method' => $data['payment_type'],
                'fraud_status' => $data['fraud_status'] ?? null, 'verified_at' => now(), 'request_started_at' => null])->save();
            if ($refund && $order->paid_provider_order === $attempt->order_number) {
                $order->update(['status' => 'refunded', 'verified_at' => now()]);
                $order->certificate()->update(['status' => 'revoked']);
            } elseif ($success && ($order->paid_provider_order === null || $order->paid_provider_order === $attempt->order_number)) {
                $order->fill(['status' => 'paid', 'paid_provider_order' => $attempt->order_number, 'paid_at' => $order->paid_at ?? now(),
                    'verified_at' => now(), 'confirmed_at' => $order->confirmed_at ?? now(), 'payment_method' => $data['payment_type']]);
                if ($attempt->participant_name !== null || $order->participant_name !== null) {
                    // A late settlement must use the name locked to its own provider reference.
                    $order->participant_name = $attempt->participant_name;
                }
                $order->save();
                app(CertificateIssuanceService::class)->issueSandbox($order);
            }

            return $attempt;
        }, 5);
    }

    public function state(CertificatePaymentAttempt $attempt): array
    {
        $attempt->load('order.certificate');
        $order = $attempt->order;
        $ready = $order->certificate?->isDownloadable() ?? false;
        $label = match (true) {
            $order->status === 'refunded' => 'Pembayaran dikembalikan (Sandbox)',
            $order->status === 'paid' => 'Pembayaran Berhasil (Sandbox)',
            in_array($attempt->status, ['expire', 'expired_unselected'], true) => 'Pembayaran Kedaluwarsa',
            in_array($attempt->status, ['deny', 'cancel', 'failure', 'api_error'], true) => 'Pembayaran Gagal',
            in_array($attempt->status, ['pending', 'capture'], true) => 'Menunggu Pembayaran',
            default => 'Belum Dibayar',
        };

        $message = match (true) {
            $ready => 'Sertifikat Sandbox siap diunduh.',
            $order->status === 'refunded' => 'Pembayaran Sandbox dikembalikan dan sertifikat dibatalkan. Hubungi pengelola OOPy untuk informasi lebih lanjut.',
            $order->status === 'paid' => 'Pembayaran sudah terverifikasi, tetapi sertifikat tidak aktif. Hubungi pengelola OOPy.',
            in_array($attempt->status, ['expire', 'expired_unselected'], true) => 'Transaksi kedaluwarsa. Gunakan Coba Lagi untuk membuka transaksi Sandbox baru.',
            in_array($attempt->status, ['deny', 'cancel', 'failure', 'api_error'], true) => 'Pembayaran belum berhasil. Gunakan Coba Lagi; server akan memeriksa referensi yang aman untuk dilanjutkan.',
            $attempt->status === 'pending' => 'Pembayaran masih menunggu. Selesaikan di Snap Sandbox, lalu cek status kembali. Sertifikat belum diterbitkan.',
            default => 'Pilih metode pembayaran di Snap Sandbox, lalu cek status kembali. Sertifikat hanya terbit setelah konfirmasi server Midtrans.',
        };

        return ['id' => $attempt->public_id, 'status' => $order->status === 'paid' ? 'paid' : $attempt->status,
            'label' => $label, 'ready' => $ready, 'status_url' => route('midtrans.status', $attempt->public_id),
            'message' => $message, 'name_locked' => in_array($order->status, ['paid', 'refunded'], true) || ! $attempt->canBeReplaced()];
    }

    public function assertOrder(CertificateOrder $order): void
    {
        abort_unless($order->program_key === self::PROGRAM && $order->provider === 'midtrans' && $order->mode === 'sandbox'
            && $order->amount === CertificateIssuanceService::AMOUNT && $order->currency === 'IDR', 409, 'Order Sandbox tidak sesuai.');
    }

    public function correctAmount(mixed $value): bool
    {
        return (is_string($value) || is_int($value)) && preg_match('/^24999(?:\.0{1,2})?$/D', (string) $value) === 1;
    }

    private function validateStatus(CertificatePaymentAttempt $attempt, array $data, ?array $notification = null): void
    {
        $valid = ($data['order_id'] ?? null) === $attempt->order_number && $this->correctAmount($data['gross_amount'] ?? null)
            && ($data['currency'] ?? null) === 'IDR' && is_string($data['merchant_id'] ?? null) && strlen($data['merchant_id']) <= 100
            && $data['merchant_id'] !== '' && is_string($data['transaction_id'] ?? null) && strlen($data['transaction_id']) <= 100
            && $data['transaction_id'] !== '' && is_string($data['payment_type'] ?? null) && $data['payment_type'] !== '' && strlen($data['payment_type']) <= 32
            && in_array($data['transaction_status'] ?? null, ['pending', 'settlement', 'capture', 'deny', 'cancel', 'expire', 'failure', 'refund', 'partial_refund'], true)
            && in_array($data['fraud_status'] ?? 'accept', ['accept', 'challenge', 'deny'], true)
            && is_string($data['status_code'] ?? null) && preg_match('/^\d{3}$/D', $data['status_code']);
        if ($valid) {
            $expectedMerchant = config('midtrans.merchant_id') ?: $attempt->merchant_id;
            $valid = (! $expectedMerchant || $expectedMerchant === $data['merchant_id'])
                && (! $notification || $notification['merchant_id'] === $data['merchant_id']);
            if ($attempt->order->paid_provider_order === $attempt->order_number && $attempt->provider_transaction_id !== null) {
                $valid = $valid && $attempt->provider_transaction_id === $data['transaction_id'];
            }
        }
        abort_unless($valid, 409, 'Detail pembayaran tidak sesuai dengan order Sandbox. Sertifikat tidak diterbitkan.');
    }
}
