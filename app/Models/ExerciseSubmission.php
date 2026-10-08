<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class ExerciseSubmission extends LearningModel
{
    public const STATUSES = ['submitted', 'passed', 'failed', 'error'];

    protected $fillable = ['exercise_id', 'user_id', 'code', 'output', 'status', 'score', 'submitted_at'];

    protected $attributes = ['status' => 'submitted'];

    protected function casts(): array
    {
        return ['exercise_id' => 'integer', 'user_id' => 'integer', 'score' => 'decimal:2', 'submitted_at' => 'datetime'];
    }

    protected function prepareForValidation(): void
    {
        $this->submitted_at ??= now();
    }

    protected function validationRules(): array
    {
        return [
            'exercise_id' => ['required', 'integer', $this->existsIn('exercises')],
            'user_id' => ['required', 'integer', $this->existsIn('users')],
            'code' => ['required', 'string'],
            'output' => ['nullable', 'string'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'score' => ['nullable', 'numeric', 'between:0,100'],
            'submitted_at' => ['required', 'date'],
        ];
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
