<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class QuizAnswer extends LearningModel
{
    // quiz_id is derived from the attempt, never trusted from mass-assigned input.
    protected $fillable = ['attempt_id', 'question_id', 'answer_text', 'is_correct'];

    protected function casts(): array
    {
        return ['attempt_id' => 'integer', 'question_id' => 'integer', 'quiz_id' => 'integer', 'is_correct' => 'boolean'];
    }

    protected function prepareForValidation(): void
    {
        $this->quiz_id = QuizAttempt::on($this->getConnectionName())->find($this->attempt_id)?->quiz_id;
    }

    protected function validationRules(): array
    {
        return [
            'attempt_id' => ['required', 'integer', $this->existsIn('quiz_attempts')],
            'question_id' => ['required', 'integer', $this->existsIn('questions')],
            'quiz_id' => ['required', 'integer', $this->existsIn('quizzes')],
            'answer_text' => ['nullable', 'string'],
            'is_correct' => ['nullable', 'boolean'],
        ];
    }

    protected function validateRelationships(): void
    {
        $question = Question::on($this->getConnectionName())->findOrFail($this->question_id);
        if ($question->quiz_id !== $this->quiz_id) {
            throw ValidationException::withMessages(['question_id' => 'The question must belong to the attempt quiz.']);
        }
        if ($question->question_type === 'essay' && $this->is_correct !== null) {
            throw ValidationException::withMessages(['is_correct' => 'An ungraded essay must have is_correct = NULL.']);
        }
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'attempt_id');
    }
}
