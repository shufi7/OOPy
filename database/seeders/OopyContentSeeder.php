<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Exercise;
use App\Models\Material;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OopyContentSeeder extends Seeder
{
    public function run(): void
    {
        $registry = require resource_path('materi/chapters.php');

        DB::transaction(function () use ($registry) {
            $order = 0;
            foreach ($registry as $slug => $metadata) {
                if (empty($metadata['content'])) {
                    continue;
                }
                // Existing PHP files remain authoritative; loading data never runs Python.
                $content = require resource_path('materi/'.$metadata['content']);
                $chapter = Chapter::updateOrCreate(['slug' => $slug], [
                    'title' => $metadata['judul'],
                    'description' => $content['description'] ?? null,
                    'chapter_order' => ++$order,
                ]);
                $final = isset($content['questions']);
                if (! $final) {
                    $material = Material::firstOrCreate(['chapter_id' => $chapter->id, 'slug' => $slug], [
                        'title' => $metadata['judul'], 'content' => null, 'material_order' => 1,
                    ]);
                    // Do not overwrite any authored database content on later seed runs.
                    $material->update(['title' => $metadata['judul'], 'material_order' => 1]);
                    foreach ($content['sections'] ?? [] as $section) {
                        foreach ($section['live_codes'] ?? [] as $exercise) {
                            $entry = $exercise['entry_file'] ?? 'main.py';
                            if (! isset($exercise['files'][$entry])) {
                                throw new \RuntimeException('Missing starter entry for '.$exercise['id']);
                            }
                            Exercise::updateOrCreate(['exercise_key' => $exercise['id']], [
                                'material_id' => $material->id,
                                'title' => $exercise['title'],
                                'instruction' => $exercise['description'],
                                'starter_code' => $exercise['files'][$entry],
                                'expected_output' => $exercise['expected_output'] ?? null,
                            ]);
                        }
                    }
                }

                $quiz = Quiz::updateOrCreate(['slug' => $final ? $slug : 'kuis-'.$slug], [
                    'chapter_id' => $chapter->id,
                    'title' => $final ? 'Evaluasi Akhir OOPy' : 'Kuis '.$metadata['bab'],
                    'type' => $final ? 'final_exam' : 'chapter_quiz',
                    'passing_score' => $final ? config('evaluasi.pass_threshold') : round(config('quiz.minimum_correct') / config('quiz.total_questions') * 100),
                    'duration_seconds' => $final ? config('evaluasi.duration_seconds') : null,
                ]);
                foreach (($final ? $content['questions'] : $content['quiz']) as $index => $question) {
                    $type = $question['type'] ?? 'multiple_choice';
                    Question::updateOrCreate(['quiz_id' => $quiz->id, 'question_order' => $index + 1], [
                        'question_type' => $type,
                        'question_text' => $question['question'],
                        'code_snippet' => $question['code'] ?? null,
                        'options' => $type === 'multiple_choice' ? $question['options'] : null,
                        'correct_answer' => match ($type) {
                            'multiple_choice' => (string) $question['correct'],
                            'code_fill' => $question['answer'],
                            'essay' => null,
                        },
                        'explanation' => $question['explanation'] ?? null,
                    ]);
                }
            }
        });
    }
}
