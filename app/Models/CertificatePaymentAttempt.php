<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificatePaymentAttempt extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['snap_token', 'redirect_url'];

    protected function casts(): array
    {
        return [
            'certificate_order_id' => 'integer', 'snap_token' => 'encrypted', 'redirect_url' => 'encrypted',
            'request_started_at' => 'datetime', 'expires_at' => 'datetime', 'verified_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CertificateOrder::class, 'certificate_order_id');
    }
}
