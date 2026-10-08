<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

class Quiz extends LearningModel
{
    public const TYPES = ['chapter_quiz', 'final_exam'];

    protected $fillable = ['chapter_id', 'slug', 'title', 'type', 'passing_score', 'duration_seconds'];

    protected $attributes = ['passing_score' => 80];

    protected function casts(): array
    {
        return ['chapter_id' => 'integer', 'passing_score' => 'decimal:2', 'duration_seconds' => 'integer'];
    }

    protected function validationRules(): array
    {
        return [
            'chapter_id' => ['required', 'integer', $this->existsIn('chapters')],
            'slug' => ['required', 'string', 'alpha_dash:ascii', 'max:191'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(self::TYPES)],
            'passing_score' => ['required', 'numeric', 'between:0,100'],
            'duration_seconds' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('question_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
