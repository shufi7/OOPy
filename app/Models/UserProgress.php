<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class UserProgress extends LearningModel
{
    public const STATUSES = ['not_started', 'in_progress', 'completed'];

    protected $table = 'user_progress';

    protected $fillable = ['user_id', 'material_id', 'status', 'completed_at'];

    protected $attributes = ['status' => 'not_started'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'material_id' => 'integer', 'completed_at' => 'datetime'];
    }

    protected function validationRules(): array
    {
        return [
            'user_id' => ['required', 'integer', $this->existsIn('users')],
            'material_id' => ['required', 'integer', $this->existsIn('materials')],
            'status' => ['required', Rule::in(self::STATUSES)],
            'completed_at' => ['nullable', 'date', Rule::requiredIf($this->status === 'completed'), Rule::prohibitedIf($this->status !== 'completed')],
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
