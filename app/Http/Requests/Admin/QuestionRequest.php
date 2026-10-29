<?php

namespace App\Http\Requests\Admin;

use App\Models\Quiz;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    protected function prepareForValidation(): void
    {
        $multiple = $this->input('question_type') === 'multiple_choice';
        $essay = $this->input('question_type') === 'essay';
        $this->merge([
            'options' => $multiple && is_string($this->input('options_text'))
                ? array_map('trim', preg_split('/\r\n|\r|\n/', $this->input('options_text'))) : null,
            'correct_answer' => $essay ? null : $this->input('correct_answer'),
            'essay_rubric' => $essay ? $this->input('essay_rubric') : null,
        ]);
    }

    public function rules(): array
    {
        $multiple = $this->input('question_type') === 'multiple_choice';
        $essay = $this->input('question_type') === 'essay';

        return [
            'quiz_id' => ['required', 'integer', Rule::exists('quizzes', 'id')],
            'question_type' => ['required', Rule::in(['multiple_choice', 'code_fill', 'essay'])],
            'question_text' => ['required', 'string', 'max:12000'],
            'code_snippet' => [Rule::requiredIf($this->input('question_type') === 'code_fill'), 'nullable', 'string', 'max:20000'],
            'options' => [Rule::requiredIf($multiple), 'nullable', 'array', 'min:2', 'max:8'],
            'options.*' => ['required', 'string', 'max:4000'],
            'correct_answer' => [Rule::requiredIf(! $essay), 'nullable', 'string', 'max:'.config('quiz.maximum_answer_length')],
            'explanation' => ['nullable', 'string', 'max:12000'],
            'essay_rubric' => ['nullable', 'string', 'max:12000'],
            'display_order' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            if ($this->input('question_type') === 'essay'
                && ! $validator->errors()->has('quiz_id')
                && Quiz::whereKey($this->input('quiz_id'))->value('type') !== 'final_exam') {
                $validator->errors()->add('question_type', 'Uraian hanya tersedia untuk Evaluasi Akhir.');
            }
            if ($this->input('question_type') === 'multiple_choice') {
                $answer = $this->input('correct_answer');
                $options = $this->input('options');
                if (! is_string($answer) || ! preg_match('/^(0|[1-9][0-9]*)$/D', $answer)
                    || ! is_array($options) || ! array_key_exists($answer, $options)) {
                    $validator->errors()->add('correct_answer', 'Masukkan indeks jawaban yang tersedia, dimulai dari 0.');
                }
            }
            if ($this->input('question_type') === 'code_fill' && is_string($this->input('correct_answer'))) {
                $answer = $this->input('correct_answer');
                $normalized = preg_replace('/\A[\x09-\x0D\p{Zs}\x{2028}\x{2029}\x{FEFF}]+|[\x09-\x0D\p{Zs}\x{2028}\x{2029}\x{FEFF}]+\z/u', '', $answer);
                if ($normalized !== $answer) {
                    $validator->errors()->add('correct_answer', 'Kunci isian tidak boleh diawali atau diakhiri spasi/baris kosong karena mesin kuis memangkas tepi jawaban. Spasi di dalam jawaban tetap dipertahankan.');
                }
            }
        }];
    }
}
