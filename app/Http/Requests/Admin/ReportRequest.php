<?php

namespace App\Http\Requests\Admin;

use App\Services\CodingProgressService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:150'],
            'chapter' => ['nullable', 'integer', 'between:1,6'],
            'status' => ['nullable', Rule::in(['attempted', 'not_started', 'passed', 'not_passed'])],
            'coding_exercise' => ['nullable', 'string', Rule::in(array_column(CodingProgressService::EXERCISES, 'key'))],
            'coding_status' => ['nullable', 'string', Rule::in(['not_started', 'in_progress', 'completed'])],
            'sort' => ['nullable', Rule::in(['name', 'latest', 'best', 'coding_latest', 'coding_best'])],
        ];
    }
}
