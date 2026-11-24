<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificatePaymentAttempt extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['snap_token', 'redirect_url', 'academic_baseline'];

    protected function casts(): array
    {
        return [
            'certificate_order_id' => 'integer', 'snap_token' => 'encrypted', 'redirect_url' => 'encrypted',
            'academic_baseline' => 'array',
            'request_started_at' => 'datetime', 'expires_at' => 'datetime', 'verified_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CertificateOrder::class, 'certificate_order_id');
    }

    public function canBeReplaced(): bool
    {
        return $this->status === 'expired_unselected'
            || ($this->verified_at && in_array($this->status, ['deny', 'cancel', 'expire', 'failure'], true));
    }
}
