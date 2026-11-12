<?php

namespace Tests\Feature\Admin;

use App\Models\FinalExamAttempt;
use App\Models\Quiz;
use App\Models\User;
use App\Services\Admin\QuestionBankService;
use Database\Seeders\OopyContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalExamTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_publication_failure_rolls_back_all_transition_drafts_and_archives(): void
    {
        $this->seed(OopyContentSeeder::class);
        $quiz = Quiz::where('type', 'final_exam')->firstOrFail();
        $legacy = require base_path('tests/Fixtures/legacy-final-exam.php');
        foreach ($quiz->questions as $index => $question) {
            if ($index >= 15) {
                $question->update([
                    'question_type' => 'essay', 'question_text' => $legacy[$index]['question'],
                    'correct_answer' => null, 'code_snippet' => null,
                    'display_order' => $index === 19 ? 1 : $index + 1,
                ]);
            }
        }
        $questions = $quiz->fresh()->questions;
        $before = $questions->map->getAttributes()->all();
        $version = app(QuestionBankService::class)->packageVersion($questions);
        $this->artisan('oopy:upgrade-final-exam', ['--apply' => true, '--package-version' => $version])
            ->assertFailed();
        $this->assertDatabaseCount('questions', 50);
        $this->assertSame($before, $quiz->fresh()->questions->map->getAttributes()->all());
        $this->assertSame(0, $quiz->allQuestions()->where('publication_status', 'draft')->count());
        $this->assertSame(0, $quiz->allQuestions()->where('publication_status', 'archived')->count());
    }

    public function test_preview_apply_and_reseed_preserve_historical_attempts_and_question_revisions(): void
    {
        $this->seed(OopyContentSeeder::class);
        $quiz = Quiz::where('type', 'final_exam')->firstOrFail();
        $legacy = require base_path('tests/Fixtures/legacy-final-exam.php');
        foreach ($quiz->questions as $index => $question) {
            if ($index < 15) {
                continue;
            }
            $question->update([
                'question_type' => 'essay', 'question_text' => $legacy[$index]['question'],
                'correct_answer' => null, 'code_snippet' => null,
            ]);
        }
        $questions = $quiz->fresh()->questions;
        $this->actingAs(User::factory()->create())->postJson('/evaluasi/attempts')->assertStatus(503);
        $user = User::factory()->create();
        $old = FinalExamAttempt::create([
            'user_id' => $user->id, 'quiz_id' => $quiz->id, 'snapshot' => $legacy,
            'answers' => array_fill_keys(array_column($legacy, 'id'), null), 'status' => 'in_progress',
            'duration_seconds' => 2400, 'passing_score' => 70, 'started_at' => now(), 'deadline_at' => now()->addMinutes(40),
        ]);
        $finished = $old->replicate();
        $finished->forceFill([
            'status' => 'completed', 'submitted_at' => now(), 'objective_score' => 80,
            'objective_points' => 60, 'essay_points' => 20, 'essay_score' => 80, 'total_score' => 80,
        ])->save();
        $originalFinished = $finished->fresh()->getAttributes();
        $before = $questions->map->getAttributes()->all();
        $version = app(QuestionBankService::class)->packageVersion($questions);
        $this->artisan('oopy:upgrade-final-exam')->assertSuccessful();
        $this->assertSame($before, $quiz->fresh()->questions->map->getAttributes()->all());
        $this->assertDatabaseCount('questions', 50);
        $this->artisan('oopy:upgrade-final-exam', ['--apply' => true, '--package-version' => str_repeat('0', 64)])->assertFailed();
        $this->assertDatabaseCount('questions', 50);
        $this->artisan('oopy:upgrade-final-exam', ['--apply' => true, '--package-version' => $version])->assertSuccessful();
        $active = $quiz->fresh()->questions;
        $this->assertSame(['multiple_choice' => 10, 'code_fill' => 10], $active->countBy('question_type')->all());
        $this->assertSame($questions->take(15)->pluck('id')->all(), $active->take(15)->pluck('id')->all());
        foreach ($questions->slice(15) as $essay) {
            $this->assertSame('archived', $essay->fresh()->publication_status);
            $this->assertDatabaseHas('questions', ['revision_of_id' => $essay->id, 'question_type' => 'code_fill', 'publication_status' => 'published']);
            $this->assertNotNull(app(QuestionBankService::class)->deletionError($essay->fresh()));
        }
        $this->assertSame($legacy, $old->fresh()->snapshot);
        $this->assertSame($originalFinished, $finished->fresh()->getAttributes());
        $this->artisan('oopy:upgrade-final-exam', ['--apply' => true])->assertSuccessful();
        $this->seed(OopyContentSeeder::class);
        $this->assertDatabaseCount('questions', 55);
        $this->assertSame($active->pluck('id')->all(), $quiz->fresh()->questions->pluck('id')->all());
        $this->actingAs($user)->postJson('/evaluasi/attempts')->assertOk()->assertJsonPath('attempt.id', $old->id);
        $this->postJson('/evaluasi/attempts/'.$old->id.'/submit', ['answers' => []])->assertOk()
            ->assertJsonPath('attempt.result.essayTotal', 5)->assertJsonPath('attempt.result.totalScore', null);
        $this->assertSame('pending_review', $old->fresh()->status);
        $this->postJson('/evaluasi/attempts')->assertOk()->assertJsonCount(20, 'attempt.questions');
    }
}
