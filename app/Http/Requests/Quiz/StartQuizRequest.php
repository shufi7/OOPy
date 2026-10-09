<?php

namespace App\Http\Requests\Quiz;

use Illuminate\Foundation\Http\FormRequest;

class StartQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return array_fill_keys(['score', 'passed', 'is_correct', 'best_score', 'correct_count', 'user_id', 'role', 'quiz_id'], ['prohibited']);
    }
}
