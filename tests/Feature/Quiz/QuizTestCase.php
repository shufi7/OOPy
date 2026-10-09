<?php

namespace Tests\Feature\Quiz;

use App\Models\Quiz;
use Database\Seeders\OopyContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class QuizTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(OopyContentSeeder::class);
    }

    protected function quiz(string $slug = 'dasar-pemrograman-oop'): Quiz
    {
        return Quiz::with('questions')->where('slug', 'kuis-'.$slug)->firstOrFail();
    }

    protected function answers(Quiz $quiz, int $correct = 5): array
    {
        return ['answers' => $quiz->questions->map(function ($question, $index) use ($correct) {
            $answer = $question->question_type === 'multiple_choice'
                ? ($index < $correct ? (int) $question->correct_answer : ((int) $question->correct_answer + 1) % count($question->options))
                : ($index < $correct ? ' '.$question->correct_answer.' ' : strtoupper($question->correct_answer));

            return ['question_id' => $question->id, 'answer' => $answer];
        })->all()];
    }

    protected function start(string $slug = 'dasar-pemrograman-oop'): array
    {
        return $this->postJson("/materi/$slug/kuis/attempts")->assertOk()->json();
    }
}
