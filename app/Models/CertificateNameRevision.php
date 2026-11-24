<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class CertificateNameRevision extends Model
{
    public $timestamps = false;

    protected $fillable = ['participant_name', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public static function installed(): bool
    {
        return Schema::hasTable('certificate_name_revisions');
    }
}
