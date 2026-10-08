<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Chapter extends LearningModel
{
    protected $fillable = ['slug', 'title', 'description', 'chapter_order'];

    protected function casts(): array
    {
        return ['chapter_order' => 'integer'];
    }

    protected function validationRules(): array
    {
        return [
            'slug' => ['required', 'string', 'alpha_dash:ascii', 'max:191'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'chapter_order' => ['required', 'integer', 'min:1'],
        ];
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class)->orderBy('material_order');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }
}
