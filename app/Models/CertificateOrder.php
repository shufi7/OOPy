<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CertificateOrder extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'amount' => 'integer', 'confirmed_at' => 'datetime',
            'eligible_completed_at' => 'datetime', 'paid_at' => 'datetime', 'verified_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class, 'order_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(CertificatePaymentAttempt::class);
    }
}
