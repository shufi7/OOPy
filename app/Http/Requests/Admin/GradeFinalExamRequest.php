<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GradeFinalExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'scores' => ['required', 'array', 'size:5'],
            'scores.*' => ['required', 'numeric', 'between:0,5'],
            'feedback' => ['nullable', 'array', 'max:5'],
            'feedback.*' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
