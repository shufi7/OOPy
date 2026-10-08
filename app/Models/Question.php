<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Question extends LearningModel
{
    public const TYPES = ['multiple_choice', 'code_fill', 'essay'];

    protected $fillable = ['quiz_id', 'question_type', 'question_text', 'code_snippet', 'options', 'correct_answer', 'explanation', 'question_order'];

    protected function casts(): array
    {
        return ['quiz_id' => 'integer', 'options' => 'array', 'correct_answer' => 'string', 'question_order' => 'integer'];
    }

    protected function validationRules(): array
    {
        $multipleChoice = $this->question_type === 'multiple_choice';

        return [
            'quiz_id' => ['required', 'integer', $this->existsIn('quizzes')],
            'question_type' => ['required', Rule::in(self::TYPES)],
            'question_text' => ['required', 'string'],
            'code_snippet' => ['nullable', 'string', Rule::requiredIf($this->question_type === 'code_fill')],
            'options' => ['nullable', 'array', Rule::requiredIf($multipleChoice)],
            'options.*' => ['required', 'string'],
            'correct_answer' => ['nullable', 'string', Rule::requiredIf($this->question_type !== 'essay')],
            'explanation' => ['nullable', 'string'],
            'question_order' => ['required', 'integer', 'min:1'],
        ];
    }

    protected function validateRelationships(): void
    {
        if ($this->question_type === 'multiple_choice') {
            if (! array_is_list($this->options) || count($this->options) < 2
                || ! preg_match('/^(0|[1-9][0-9]*)$/D', $this->correct_answer)
                || ! array_key_exists($this->correct_answer, $this->options)) {
                throw ValidationException::withMessages(['options' => 'Use a list of at least two options and an existing zero-based answer index.']);
            }
        } elseif ($this->options !== null || ($this->question_type === 'essay' && $this->correct_answer !== null)) {
            throw ValidationException::withMessages(['correct_answer' => 'Essay answers/options and code-fill options must be NULL.']);
        }

        // Reseeding may refresh unused questions, but never silently rewrite graded history.
        $gradingFields = ['quiz_id', 'question_type', 'question_text', 'code_snippet', 'options', 'correct_answer'];
        if ($this->exists && $this->isDirty($gradingFields) && $this->answers()->exists()) {
            throw ValidationException::withMessages(['question_text' => 'Version answered questions before changing their content or grading contract.']);
        }
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }
}
