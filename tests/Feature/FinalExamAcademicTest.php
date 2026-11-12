<?php

namespace Tests\Feature;

use App\Models\FinalExamAttempt;
use App\Models\Quiz;
use App\Models\User;
use App\Services\Admin\QuestionBankService;
use App\Services\FinalExamCodeAnswer;
use Database\Seeders\OopyContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinalExamAcademicTest extends TestCase
{
    use RefreshDatabase;

    public function test_code_variations_and_invalid_fragments_are_graded_by_the_server(): void
    {
        $this->seed(OopyContentSeeder::class);
        $this->actingAs(User::factory()->create());
        $cases = json_decode(file_get_contents(base_path('tests/Fixtures/final-exam-cases.json')), true, flags: JSON_THROW_ON_ERROR);
        $questions = collect((require resource_path('materi/evaluasi-akhir.php'))['questions'])->keyBy('id');
        $matcher = app(FinalExamCodeAnswer::class);
        foreach ($cases as $id => $case) {
            $q = $questions[$id];
            foreach ([$q['answer'], ...$case['valid']] as $answer) {
                $this->assertTrue($matcher->matches($q, $answer), "$id must accept $answer");
            }
            foreach (['', ' ', ...$case['invalid']] as $answer) {
                $this->assertFalse($matcher->matches($q, $answer), "$id must reject $answer");
            }
        }
        $id = $this->postJson('/evaluasi/attempts')->assertOk()->json('attempt.id');
        $attempt = FinalExamAttempt::findOrFail($id);
        $answers = [];
        foreach ($attempt->snapshot as $index => $q) {
            $answers[$q['id']] = $q['type'] === 'multiple_choice' ? $q['correct'] : $cases['q'.($index + 1)]['valid'][0];
        }
        $this->postJson('/evaluasi/attempts/'.$id.'/submit', ['answers' => $answers])->assertOk()
            ->assertJsonPath('attempt.result.correct', 20)->assertJsonPath('attempt.result.totalScore', 100);
        $this->assertSame('100.00', $attempt->fresh()->total_score);

        $id = $this->postJson('/evaluasi/attempts')->assertOk()->json('attempt.id');
        $attempt = FinalExamAttempt::findOrFail($id);
        foreach ($attempt->snapshot as $index => $q) {
            $answers[$q['id']] = $q['type'] === 'multiple_choice' ? null : $cases['q'.($index + 1)]['invalid'][0];
        }
        $this->postJson('/evaluasi/attempts/'.$id.'/submit', ['answers' => $answers, 'total_score' => 100])->assertOk()
            ->assertJsonPath('attempt.result.correct', 0)->assertJsonPath('attempt.result.totalScore', 0);
    }

    public function test_old_snapshot_keeps_its_original_comparison_policy_and_result(): void
    {
        $this->seed(OopyContentSeeder::class);
        $this->actingAs(User::factory()->create());
        $id = $this->postJson('/evaluasi/attempts')->assertOk()->json('attempt.id');
        $attempt = FinalExamAttempt::findOrFail($id);
        $snapshot = $attempt->snapshot;
        foreach ($snapshot as &$q) {
            unset($q['grading_version']);
        }
        unset($q);
        $q = $snapshot[11];
        $attempt->update(['snapshot' => $snapshot]);
        $this->postJson('/evaluasi/attempts/'.$id.'/submit', ['answers' => [$q['id'] => 'return (self._ph)']])->assertOk()
            ->assertJsonPath('attempt.result.correct', 0)->assertJsonPath('attempt.result.totalScore', 0);
        $this->getJson('/evaluasi/attempts')->assertOk()->assertJsonPath('history.0.result.correct', 0);
    }

    public function test_invalid_multiple_choice_options_cannot_be_published_or_started(): void
    {
        $this->seed(OopyContentSeeder::class);
        $quiz = Quiz::where('type', 'final_exam')->firstOrFail();
        $q = $quiz->questions->first();
        $q->update(['options' => ['A', 'B', 'C'], 'correct_answer' => '0']);
        try {
            app(QuestionBankService::class)->publish($quiz, $quiz->questions->pluck('id')->all());
            $this->fail('Three-option multiple choice must not be published.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('question_ids', $exception->errors());
        }
        $this->actingAs(User::factory()->create())->postJson('/evaluasi/attempts')->assertStatus(503);
        $this->assertDatabaseCount('final_exam_attempts', 0);
    }
}
