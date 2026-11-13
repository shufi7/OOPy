<?php

namespace App\Http\Requests\Coding;

use App\Services\CodingExerciseCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitCodingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $names = array_keys(app(CodingExerciseCatalog::class)->find($this->route('slug'))['files']);
        $rules = [
            'files' => ['required', 'array', 'size:'.count($names)],
            'user_id' => ['prohibited'],
            'role' => ['prohibited'],
            'score' => ['prohibited'],
            'passed' => ['prohibited'],
            'status' => ['prohibited'],
            'exercise_id' => ['prohibited'],
            'exercise_key' => ['prohibited'],
        ];
        foreach ($names as $name) {
            $rules['files.'.str_replace('.', '\\.', $name)] = ['required', 'string', 'max:30000', function ($attribute, $value, $fail) use ($name) {
                if (is_string($value) && (! preg_match('/[^\s\p{Z}\x{FEFF}]/u', $value) || strlen($value) > 60000)) {
                    $fail($name.' wajib berisi kode; maksimal 30.000 karakter dan 60.000 byte UTF-8.');
                }
            }];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (array_diff(array_keys($this->all()), ['files']) !== []) {
                $validator->errors()->add('payload', 'Request hanya boleh memuat files.');
            }
            $files = $this->input('files');
            $names = array_keys(app(CodingExerciseCatalog::class)->find($this->route('slug'))['files']);
            if (is_array($files) && (array_diff(array_keys($files), $names) || array_diff($names, array_keys($files)))) {
                $validator->errors()->add('files', 'File wajib: '.implode(', ', $names).'. File lain tidak diperbolehkan.');
            }
            if (! is_array($files) || array_filter($files, fn ($source) => ! is_string($source))) {
                return;
            }
            if (array_sum(array_map('mb_strlen', $files)) > 30000 || array_sum(array_map('strlen', $files)) > 60000) {
                $validator->errors()->add('files', 'Total proyek maksimal 30.000 karakter dan 60.000 byte UTF-8.');
            }
        });
    }
}
