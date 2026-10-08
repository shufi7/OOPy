<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends LearningModel
{
    protected $fillable = ['chapter_id', 'slug', 'title', 'content', 'material_order'];

    protected $attributes = ['material_order' => 1];

    protected function casts(): array
    {
        return ['chapter_id' => 'integer', 'material_order' => 'integer'];
    }

    protected function validationRules(): array
    {
        return [
            'chapter_id' => ['required', 'integer', $this->existsIn('chapters')],
            'slug' => ['required', 'string', 'alpha_dash:ascii', 'max:191'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'material_order' => ['required', 'integer', 'min:1'],
        ];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    public function userProgress(): HasMany
    {
        return $this->hasMany(UserProgress::class);
    }
}
