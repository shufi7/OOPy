<?php

namespace App\Models;

use App\Services\MidtransPaymentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Certificate extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['transcript_snapshot', 'latestNameRevision'];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'order_id' => 'integer', 'completed_at' => 'datetime', 'issued_at' => 'datetime',
            'transcript_snapshot' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CertificateOrder::class, 'order_id');
    }

    public function nameRevisions(): HasMany
    {
        return $this->hasMany(CertificateNameRevision::class);
    }

    public function latestNameRevision(): HasOne
    {
        return $this->nameRevisions()->one()->latestOfMany();
    }

    public function currentNameRevision(): ?CertificateNameRevision
    {
        if (! $this->relationLoaded('latestNameRevision')) {
            $this->setRelation('latestNameRevision', CertificateNameRevision::installed() ? $this->latestNameRevision()->first() : null);
        }

        return $this->getRelation('latestNameRevision');
    }

    public function currentParticipantName(): string
    {
        return $this->currentNameRevision()?->participant_name ?? $this->participant_name;
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
