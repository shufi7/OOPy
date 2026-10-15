<?php

namespace App\Http\Requests\Coding;

use Illuminate\Foundation\Http\FormRequest;

class SubmitCodingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // The current catalog uses one file. Do not accept arbitrary paths or extra files.
        return [
            'files' => ['required', 'array', 'size:1'],
            'files.main\\.py' => ['required', 'string', 'max:30000'],
            'user_id' => ['prohibited'],
            'role' => ['prohibited'],
            'score' => ['prohibited'],
            'passed' => ['prohibited'],
            'status' => ['prohibited'],
            'exercise_id' => ['prohibited'],
            'exercise_key' => ['prohibited'],
        ];
    }
}
