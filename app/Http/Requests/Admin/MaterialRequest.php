<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:0'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'objectives' => ['nullable', 'string', 'max:15000'],
            'summary' => ['nullable', 'string', 'max:15000'],
            'reflection' => ['nullable', 'string', 'max:15000'],
            'sections' => ['required', 'array', 'min:1', 'max:80'],
            'sections.*' => ['array:id,title,order,paragraphs,code,output,tip'],
            'sections.*.id' => ['nullable', 'string', 'max:191'],
            'sections.*.title' => ['required', 'string', 'max:255'],
            'sections.*.order' => ['required', 'integer', 'min:1', 'max:999', 'distinct'],
            'sections.*.paragraphs' => ['nullable', 'string', 'max:30000'],
            'sections.*.code' => ['nullable', 'string', 'max:30000'],
            'sections.*.output' => ['nullable', 'string', 'max:15000'],
            'sections.*.tip' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
