<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateIdentity extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = ['user_id', 'participant_name', 'confirmed_at'];

    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime'];
    }
}
