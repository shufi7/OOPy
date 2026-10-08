<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends LearningModel
{
    protected $fillable = ['material_id', 'exercise_key', 'title', 'instruction', 'starter_code', 'expected_output'];

    protected function casts(): array
    {
        return ['material_id' => 'integer'];
    }

    protected function validationRules(): array
    {
        return [
            'material_id' => ['required', 'integer', $this->existsIn('materials')],
            'exercise_key' => ['required', 'string', 'alpha_dash:ascii', 'max:191'],
            'title' => ['required', 'string', 'max:255'],
            'instruction' => ['required', 'string'],
            'starter_code' => ['required', 'string'],
            'expected_output' => ['nullable', 'string'],
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ExerciseSubmission::class);
    }
}
