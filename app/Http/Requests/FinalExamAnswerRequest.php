<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinalExamAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ownership is checked by the service against the authenticated account/session.
    }

    public function rules(): array
    {
        return ['answers' => ['present', 'array', 'max:20']];
    }
}
