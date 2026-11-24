<?php

namespace App\Services;

use App\Models\CertificatePaymentAttempt;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MidtransGateway
{
    public const SNAP_URL = 'https://app.sandbox.midtrans.com/snap/v1/transactions';

    public const STATUS_URL = 'https://api.sandbox.midtrans.com/v2/';

    public const SCRIPT_URL = 'https://app.sandbox.midtrans.com/snap/snap.js';

    public function configured(): bool
    {
        return config('midtrans.is_production') === false
            && is_string(config('midtrans.server_key')) && str_starts_with(config('midtrans.server_key'), 'SB-Mid-server-')
            && is_string(config('midtrans.client_key')) && trim(config('midtrans.client_key')) !== '';
    }

    public function assertSandbox(): void
    {
        abort_unless($this->configured(), 503, 'Pembayaran Sandbox belum tersedia. Periksa konfigurasi Sandbox; Production tidak didukung.');
    }

    public function create(CertificatePaymentAttempt $attempt, User $user): array
    {
        $this->assertSandbox();
        try {
            $response = $this->client()->post(self::SNAP_URL, [
                'transaction_details' => ['order_id' => $attempt->order_number, 'gross_amount' => CertificateIssuanceService::AMOUNT],
                'item_details' => [['id' => MidtransPaymentService::PROGRAM, 'name' => 'Sertifikat Penyelesaian OOPy', 'price' => CertificateIssuanceService::AMOUNT, 'quantity' => 1]],
                'customer_details' => ['first_name' => $attempt->participant_name ?? mb_substr($user->name, 0, 255), 'email' => $user->email],
                // Snap presents QRIS through GoPay; forcing QR mode happens in Snap.js.
                'enabled_payments' => ['gopay'],
                'expiry' => ['start_time' => $attempt->created_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s O'), 'unit' => 'hours', 'duration' => 24],
                'callbacks' => ['finish' => route('certification.index')],
            ]);
        } catch (ConnectionException|RequestException) {
            throw new HttpException(503, 'Midtrans Sandbox belum merespons. Coba lagi; referensi order tetap sama.');
        }
        if (! $response->successful()) {
            throw new HttpException(503, 'Transaksi Sandbox belum dapat dibuka. Periksa aktivasi GoPay/QRIS dan konfigurasi merchant Sandbox.');
        }
        $data = $response->json();
        abort_unless(is_array($data) && is_string($data['token'] ?? null) && preg_match('/^[a-zA-Z0-9-]{10,200}$/D', $data['token'])
            && $this->safeRedirect($data['redirect_url'] ?? null), 502, 'Respons transaksi Sandbox tidak valid.');

        return ['token' => $data['token'], 'redirect_url' => $data['redirect_url']];
    }

    public function status(string $orderNumber): ?array
    {
        $this->assertSandbox();
        try {
            $response = $this->client()->connectTimeout(3)->timeout(5)->retry(2, 150, fn ($exception) => $exception instanceof ConnectionException
                || ($exception instanceof RequestException && $exception->response->serverError()), throw: false)
                ->get(self::STATUS_URL.rawurlencode($orderNumber).'/status');
        } catch (ConnectionException|RequestException) {
            throw new HttpException(503, 'Status pembayaran Sandbox belum dapat diperiksa. Silakan coba lagi.');
        }
        // Snap has no Core transaction until the customer selects a payment method.
        if ($response->status() === 404 || ($response->successful() && (string) $response->json('status_code') === '404')) {
            return null;
        }
        abort_unless($response->successful() && is_array($response->json()), 503, 'Status pembayaran Sandbox belum tersedia.');

        return $response->json();
    }

    public function authentic(array $payload): bool
    {
        $this->assertSandbox();
        $secret = config('midtrans.signature_secret_key');
        abort_unless(is_string($secret) && $secret !== '', 503, 'Signature Secret Key Sandbox belum dikonfigurasi.');
        $expected = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].$secret);

        return hash_equals($expected, strtolower($payload['signature_key']));
    }

    public function safeRedirect(mixed $url): bool
    {
        return is_string($url) && preg_match('~^https://app\.sandbox\.midtrans\.com/snap/(?:vtweb|v[1-9][0-9]*/(?:vtweb|redirection))/[a-zA-Z0-9-]+$~D', $url) === 1;
    }

    private function client()
    {
        return Http::acceptJson()->asJson()->withBasicAuth(config('midtrans.server_key'), '')
            ->connectTimeout(4)->timeout(9)->withOptions(['allow_redirects' => false]);
    }
}
