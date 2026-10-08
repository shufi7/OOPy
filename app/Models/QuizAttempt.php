<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

class QuizAttempt extends LearningModel
{
    public const STATUSES = ['in_progress', 'completed', 'expired'];

    protected $fillable = ['quiz_id', 'user_id', 'status', 'score', 'started_at', 'completed_at'];

    protected $attributes = ['status' => 'in_progress'];

    protected function casts(): array
    {
        return ['quiz_id' => 'integer', 'user_id' => 'integer', 'score' => 'decimal:2', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    protected function prepareForValidation(): void
    {
        $this->started_at ??= now();
    }

    protected function validationRules(): array
    {
        return [
            'quiz_id' => ['required', 'integer', $this->existsIn('quizzes')],
            'user_id' => ['required', 'integer', $this->existsIn('users')],
            'status' => ['required', Rule::in(self::STATUSES)],
            'score' => ['nullable', 'numeric', 'between:0,100'],
            'started_at' => ['required', 'date'],
            'completed_at' => ['nullable', 'date', 'after_or_equal:started_at', Rule::requiredIf($this->status !== 'in_progress'), Rule::prohibitedIf($this->status === 'in_progress')],
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class, 'attempt_id');
    }
}
