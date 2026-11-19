<?php

namespace App\Models;

use App\Services\MidtransPaymentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $guarded = ['id'];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'order_id' => 'integer', 'completed_at' => 'datetime', 'issued_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CertificateOrder::class, 'order_id');
    }

    public function isDownloadable(): bool
    {
        $order = $this->order;
        if ($this->status !== 'active' || ! $order || $order->user_id !== $this->user_id || $order->program_key !== $this->program_key) {
            return false;
        }

        return ($order->mode === 'simulation' && $order->status === 'simulation_completed')
            || ($order->mode === 'sandbox' && $order->provider === 'midtrans' && $order->status === 'paid'
                && $this->program_key === MidtransPaymentService::PROGRAM
                && $order->amount === 24999 && $order->currency === 'IDR' && $order->verified_at !== null && $order->paid_at !== null);
    }

    public function demoLabel(): string
    {
        return $this->order?->mode === 'sandbox' ? 'SANDBOX / DEMO' : 'SIMULASI / DEMO';
    }
}
