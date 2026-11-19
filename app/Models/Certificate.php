<?php

namespace App\Models;

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
        return $this->status === 'active' && $this->order?->status === 'simulation_completed'
            && $this->order->mode === 'simulation' && $this->order->user_id === $this->user_id
            && $this->order->program_key === $this->program_key;
    }
}
