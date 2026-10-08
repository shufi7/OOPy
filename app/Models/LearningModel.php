<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

abstract class LearningModel extends Model
{
    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $model->prepareForValidation();
            $data = $model->getAttributes();
            foreach ($model->getCasts() as $field => $cast) {
                if ($cast === 'array') {
                    $data[$field] = $model->getAttribute($field);
                }
            }
            Validator::make($data, $model->validationRules())->validate();
            $model->validateRelationships();
        });
    }

    abstract protected function validationRules(): array;

    protected function prepareForValidation(): void {}

    protected function validateRelationships(): void {}

    protected function existsIn(string $table): Exists
    {
        return Rule::exists(($this->getConnectionName() ?? config('database.default')).'.'.$table, 'id');
    }
}
