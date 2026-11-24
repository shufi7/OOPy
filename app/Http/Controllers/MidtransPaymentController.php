<?php

namespace App\Http\Controllers;

use App\Models\CertificatePaymentAttempt;
use App\Services\MidtransGateway;
use App\Services\MidtransPaymentService;
use Illuminate\Http\Request;

class MidtransPaymentController extends Controller
{
    public function store(Request $request, MidtransPaymentService $payments)
    {
        $data = $request->validate([
            'sandbox_confirmed' => ['required', 'accepted'],
            'confirmed_name' => ['nullable', 'string', 'max:255'],
            ...$this->prohibitedFields(),
        ]);
        $attempt = $payments->start($request->user(), $data['confirmed_name'] ?? null);
        $state = $payments->state($attempt);

        return response()->json(['payment' => $state, 'snap_token' => $state['ready'] ? null : $attempt->snap_token,
            'redirect_url' => $state['ready'] ? null : $attempt->redirect_url]);
    }

    public function status(Request $request, string $publicId, MidtransPaymentService $payments)
    {
        $request->validate($this->prohibitedFields());
        abort_unless($payments->installed(), 503, 'Struktur pembayaran Sandbox belum dipasang.');
        $attempt = CertificatePaymentAttempt::where('public_id', $publicId)->whereHas('order', fn ($query) => $query
            ->where('user_id', $request->user()->id)->where('program_key', MidtransPaymentService::PROGRAM)
            ->where('provider', 'midtrans')->where('mode', 'sandbox'))->firstOrFail();

        return response()->json(['payment' => $payments->state($payments->synchronize($attempt))]);
    }

    public function notification(Request $request, MidtransGateway $gateway, MidtransPaymentService $payments)
    {
        $request->headers->set('Accept', 'application/json');
        abort_unless($request->isJson(), 415, 'JSON diperlukan.');
        abort_if(strlen($request->getContent()) > 65536, 413);
        $payload = $request->validate([
            'order_id' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9._~-]+$/D'],
            'status_code' => ['required', 'string', 'regex:/^\d{3}$/D'],
            'gross_amount' => ['required', 'string', 'max:24', 'regex:/^\d+(?:\.\d{1,2})?$/D'],
            'currency' => ['required', 'string', 'size:3'],
            'signature_key' => ['required', 'string', 'regex:/^[a-fA-F0-9]{128}$/D'],
            'merchant_id' => ['required', 'string', 'max:100'],
            'transaction_status' => ['required', 'string', 'max:32'],
        ]);
        abort_unless($gateway->authentic($payload), 403, 'Notifikasi tidak valid.');
        abort_unless($payments->installed(), 503, 'Struktur pembayaran belum tersedia.');
        $attempt = CertificatePaymentAttempt::where('order_number', $payload['order_id'])->firstOrFail();
        abort_unless($payments->correctAmount($payload['gross_amount']) && $payload['currency'] === 'IDR', 409, 'Detail pembayaran tidak sesuai.');
        $payments->synchronize($attempt, $payload);

        return response()->json(['received' => true]);
    }

    private function prohibitedFields(): array
    {
        return array_fill_keys(['amount', 'gross_amount', 'currency', 'user_id', 'order_id', 'order_number',
            'status', 'transaction_status', 'payment_status', 'payment_method', 'mode', 'provider', 'program_key'], ['prohibited']);
    }
}
