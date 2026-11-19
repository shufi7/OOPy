<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'sections.*' => ['array:id,title,order,paragraphs,code,output,tip,media_present,media,learning_check'],
            'sections.*.media_present' => ['sometimes', 'boolean'],
            'sections.*.id' => ['nullable', 'string', 'max:191'],
            'sections.*.title' => ['required', 'string', 'max:255'],
            'sections.*.order' => ['required', 'integer', 'min:1', 'max:999', 'distinct'],
            'sections.*.paragraphs' => ['nullable', 'string', 'max:30000'],
            'sections.*.code' => ['nullable', 'string', 'max:30000'],
            'sections.*.output' => ['nullable', 'string', 'max:15000'],
            'sections.*.tip' => ['nullable', 'string', 'max:5000'],
            'sections.*.media' => ['sometimes', 'array', 'max:2'],
            'sections.*.media.*' => ['array:type,src,alt,caption,source_label,source_url,license_label,license_url,upload'],
            'sections.*.media.*.type' => ['required', 'in:photo,diagram'],
            'sections.*.media.*.src' => ['nullable', 'string', 'max:500'],
            'sections.*.media.*.alt' => ['required', 'string', 'max:500'],
            'sections.*.media.*.caption' => ['required', 'string', 'max:1000'],
            'sections.*.media.*.source_label' => ['nullable', 'string', 'max:300'],
            'sections.*.media.*.source_url' => ['nullable', 'string', 'max:2000', 'regex:/^https:\/\//i'],
            'sections.*.media.*.license_label' => ['nullable', 'string', 'max:200'],
            'sections.*.media.*.license_url' => ['nullable', 'string', 'max:2000', 'regex:/^https:\/\//i'],
            'sections.*.media.*.upload' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
            'sections.*.learning_check' => ['sometimes', 'array:enabled,kind,prompt,code,options,visualization'],
            'sections.*.learning_check.enabled' => ['sometimes', 'boolean'],
            'sections.*.learning_check.kind' => ['nullable', 'string', 'max:120'],
            'sections.*.learning_check.prompt' => ['nullable', 'string', 'max:2000'],
            'sections.*.learning_check.code' => ['nullable', 'string', 'max:5000'],
            'sections.*.learning_check.options' => ['nullable', 'array', 'max:6'],
            'sections.*.learning_check.options.*' => ['array:label,correct,feedback'],
            'sections.*.learning_check.options.*.label' => ['nullable', 'string', 'max:400'],
            'sections.*.learning_check.options.*.correct' => ['required', 'boolean'],
            'sections.*.learning_check.options.*.feedback' => ['nullable', 'string', 'max:1000'],
            'sections.*.learning_check.visualization' => ['nullable', 'array:enabled,caption,layout,items'],
            'sections.*.learning_check.visualization.enabled' => ['required_with:sections.*.learning_check.visualization', 'boolean'],
            'sections.*.learning_check.visualization.caption' => ['nullable', 'string', 'max:300'],
            'sections.*.learning_check.visualization.layout' => ['nullable', 'in:compare,flow'],
            'sections.*.learning_check.visualization.items' => ['nullable', 'array', 'max:6'],
            'sections.*.learning_check.visualization.items.*' => ['array:label,code,detail'],
            'sections.*.learning_check.visualization.items.*.label' => ['nullable', 'string', 'max:200'],
            'sections.*.learning_check.visualization.items.*.code' => ['nullable', 'string', 'max:300'],
            'sections.*.learning_check.visualization.items.*.detail' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $sections = $this->input('sections', []);
            if (! is_array($sections)) {
                return;
            }
            foreach ($sections as $index => $section) {
                if (! is_array($section)) {
                    continue;
                }
                $check = $section['learning_check'] ?? null;
                if (! is_array($check)) {
                    continue;
                }
                if (! array_key_exists('enabled', $check)) {
                    $validator->errors()->add("sections.$index.learning_check.enabled", 'Pilih status aktivitas.');
                    continue;
                }
                if (! filter_var($check['enabled'], FILTER_VALIDATE_BOOLEAN)) continue;
                if (trim((string) ($check['kind'] ?? '')) === '') {
                    $validator->errors()->add("sections.$index.learning_check.kind", 'Kategori aktivitas wajib diisi.');
                }
                if (trim((string) ($check['prompt'] ?? '')) === '') {
                    $validator->errors()->add("sections.$index.learning_check.prompt", 'Pertanyaan aktivitas wajib diisi.');
                }
                $options = $check['options'] ?? [];
                if (! is_array($options) || count($options) < 2 || count($options) > 6) {
                    $validator->errors()->add("sections.$index.learning_check.options", 'Aktivitas aktif memerlukan 2–6 pilihan.');
                } else {
                    $correct = 0;
                    foreach ($options as $optionIndex => $option) {
                        if (! is_array($option)) {
                            $validator->errors()->add("sections.$index.learning_check.options.$optionIndex", 'Setiap pilihan harus berupa data pilihan yang valid.');
                            continue;
                        }
                        $correct += filter_var($option['correct'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
                        if (trim((string) ($option['label'] ?? '')) === '') {
                            $validator->errors()->add("sections.$index.learning_check.options.$optionIndex.label", 'Label pilihan wajib diisi.');
                        }
                        if (trim((string) ($option['feedback'] ?? '')) === '') {
                            $validator->errors()->add("sections.$index.learning_check.options.$optionIndex.feedback", 'Feedback setiap pilihan wajib diisi.');
                        }
                    }
                    if ($correct !== 1) {
                        $validator->errors()->add("sections.$index.learning_check.options", 'Pilih tepat satu jawaban yang benar.');
                    }
                }
                $visual = $check['visualization'] ?? [];
                if (! is_array($visual)) {
                    continue;
                }
                if (is_array($visual) && filter_var($visual['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    $items = $visual['items'] ?? [];
                    if (trim((string) ($visual['caption'] ?? '')) === '' || ! in_array($visual['layout'] ?? '', ['compare', 'flow'], true)
                        || ! is_array($items) || count($items) < 2 || count($items) > 6) {
                        $validator->errors()->add("sections.$index.learning_check.visualization", 'Lengkapi caption, tata letak, dan 2–6 item visualisasi.');
                    }
                    foreach (is_array($items) ? $items : [] as $itemIndex => $item) {
                        if (! is_array($item)) {
                            $validator->errors()->add("sections.$index.learning_check.visualization.items.$itemIndex", 'Setiap item visualisasi harus berupa data yang valid.');
                            continue;
                        }
                        foreach (['label', 'code', 'detail'] as $field) {
                            if (trim((string) ($item[$field] ?? '')) === '') {
                                $validator->errors()->add("sections.$index.learning_check.visualization.items.$itemIndex.$field", 'Lengkapi setiap item visualisasi.');
                            }
                        }
                    }
                }
            }
        });
    }
}
