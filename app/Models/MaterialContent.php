<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialContent extends Model
{
    protected $fillable = ['material_id', 'draft_title', 'draft', 'published_title', 'published', 'published_at', 'lock_version', 'updated_by'];

    protected function casts(): array
    {
        return ['draft' => 'array', 'published' => 'array', 'published_at' => 'datetime', 'lock_version' => 'integer'];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
