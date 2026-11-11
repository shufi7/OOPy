<?php

namespace App\Http\Requests\Quiz;

use App\Services\ChapterQuizService;
use Illuminate\Support\Facades\Gate;

class SubmitQuizRequest extends StartQuizRequest
{
    public function authorize(): bool
    {
        return Gate::allows('submit', $this->route('attempt'));
    }

    public function rules(): array
    {
        $attempt = $this->route('attempt');
        $total = app(ChapterQuizService::class)->attemptQuestions($attempt->quiz, $attempt)->count();

        return array_merge(parent::rules(), [
            'answers' => ['required', 'array', 'list', 'size:'.$total],
            'answers.*' => ['required', 'array:question_id,answer'],
            'answers.*.question_id' => ['required', 'integer', 'distinct'],
            'answers.*.answer' => ['required'],
        ]);
    }

    public function messages(): array
    {
        return [
            'answers.required' => 'Jawab seluruh soal sebelum menyelesaikan kuis.',
            'answers.size' => 'Seluruh soal kuis wajib dijawab.',
            'answers.*.question_id.distinct' => 'Setiap soal hanya boleh dijawab sekali.',
            'answers.*.answer.required' => 'Jawaban tidak boleh kosong.',
        ];
    }
}
