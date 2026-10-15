<?php

namespace App\Services;

class CodingExerciseCatalog
{
    public function all(): array
    {
        return require resource_path('coding/exercises.php');
    }

    public function find(string $slug): array
    {
        return $this->all()[$slug] ?? abort(404);
    }
}
