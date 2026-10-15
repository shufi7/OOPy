<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\Material;
use App\Services\CodingExerciseCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CodingExerciseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach (app(CodingExerciseCatalog::class)->all() as $exercise) {
                $material = Material::where('slug', $exercise['material_slug'])->first();
                if (! $material) {
                    throw new RuntimeException('Jalankan OopyContentSeeder sebelum CodingExerciseSeeder.');
                }
                Exercise::updateOrCreate(['exercise_key' => $exercise['key']], [
                    'material_id' => $material->id,
                    'title' => $exercise['title'],
                    'instruction' => $exercise['case']."\n".implode("\n", $exercise['requirements']),
                    'starter_code' => $exercise['files']['main.py'],
                    'expected_output' => $exercise['example'],
                ]);
            }
        });
    }
}
