<?php

namespace Tests\Feature\Admin;

use App\Models\FinalExamAttempt;
use App\Models\Quiz;
use App\Models\User;
use App\Services\Admin\QuestionBankService;
use Database\Seeders\OopyContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;
use Tests\TestCase;

class FinalExamAcademicRefreshTest extends TestCase
{
    use RefreshDatabase;

    private function baseline(): Quiz
    {
        $this->seed(OopyContentSeeder::class);
        $quiz = Quiz::where('type', 'final_exam')->firstOrFail();
        $baseline = (require resource_path('evaluasi/baseline-2026-11-12.php'))['questions'];
        foreach ($quiz->questions as $index => $q) {
            $old = $baseline[$index];
            $q->update([
                'question_text' => $old['question'], 'code_snippet' => $old['code'] ?? null,
                'options' => $old['options'] ?? null, 'correct_answer' => (string) ($old['answer'] ?? $old['correct']),
                'explanation' => null,
            ]);
        }

        return $quiz->fresh();
    }

    private function preview(): string
    {
        $this->assertSame(0, Artisan::call('oopy:refresh-final-exam'));
        preg_match('/revision: ([a-f0-9]{64})/', Artisan::output(), $matches);

        return $matches[1];
    }

    public function test_preview_apply_and_reseed_protect_snapshots_and_versions(): void
    {
        $quiz = $this->baseline();
        $this->actingAs(User::factory()->create());
        $id = $this->postJson('/evaluasi/attempts')->assertOk()->json('attempt.id');
        $attempt = FinalExamAttempt::findOrFail($id);
        $before = $attempt->getAttributes();
        $oldIds = $quiz->questions->pluck('id')->all();
        $revision = $this->preview();
        $this->assertDatabaseCount('questions', 50);
        $this->artisan('oopy:refresh-final-exam', ['--apply' => true, '--revision' => str_repeat('0', 64)])->assertFailed();
        $this->assertDatabaseCount('questions', 50);
        $this->artisan('oopy:refresh-final-exam', ['--apply' => true, '--revision' => $revision])->assertSuccessful();
        $active = $quiz->fresh()->questions;
        $this->assertSame(['multiple_choice' => 10, 'code_fill' => 10], $active->countBy('question_type')->all());
        $this->assertSame(20, $quiz->allQuestions()->whereIn('id', $oldIds)->where('publication_status', 'archived')->count());
        $this->assertDatabaseCount('questions', 70);
        $this->assertSame($before, $attempt->fresh()->getAttributes());
        $answers = collect($attempt->snapshot)->mapWithKeys(fn ($q) => [$q['id'] => $q['answer'] ?? $q['correct']])->all();
        $this->postJson('/evaluasi/attempts/'.$id.'/submit', ['answers' => $answers])->assertOk()->assertJsonPath('attempt.result.totalScore', 100);
        $this->seed(OopyContentSeeder::class);
        $this->assertSame($active->pluck('id')->all(), $quiz->fresh()->questions->pluck('id')->all());
        $this->artisan('oopy:refresh-final-exam', ['--apply' => true])->assertSuccessful();
        $this->assertDatabaseCount('questions', 70);
    }

    public function test_admin_edits_and_stale_content_fingerprints_are_never_overwritten(): void
    {
        $quiz = $this->baseline();
        $revision = $this->preview();
        $q = $quiz->questions->first();
        $q->update(['question_text' => 'Pertanyaan yang telah disunting Admin']);
        $this->artisan('oopy:refresh-final-exam', ['--apply' => true, '--revision' => $revision])->assertFailed();
        $this->assertSame('Pertanyaan yang telah disunting Admin', $q->fresh()->question_text);
        $this->assertDatabaseCount('questions', 50);
    }

    public function test_publication_failure_rolls_back_every_draft_and_archive(): void
    {
        $quiz = $this->baseline();
        $revision = $this->preview();
        $before = $quiz->fresh()->questions->map->getAttributes()->all();
        $this->partialMock(QuestionBankService::class, function (MockInterface $mock) {
            $mock->shouldReceive('publish')->once()->andThrow(ValidationException::withMessages(['publication' => 'Publikasi uji ditolak.']));
        });
        $this->artisan('oopy:refresh-final-exam', ['--apply' => true, '--revision' => $revision])->assertFailed();
        $this->assertDatabaseCount('questions', 50);
        $this->assertSame($before, $quiz->fresh()->questions->map->getAttributes()->all());
    }

    public function test_preview_fingerprint_detects_known_content_changes_with_the_same_question_ids(): void
    {
        $quiz = $this->baseline();
        $revision = $this->preview();
        $target = (require resource_path('materi/evaluasi-akhir.php'))['questions'][0];
        $q = $quiz->questions->first();
        $q->update([
            'question_text' => $target['question'], 'code_snippet' => $target['code'],
            'options' => $target['options'], 'correct_answer' => (string) $target['correct'],
            'explanation' => $target['explanation'],
        ]);
        $this->artisan('oopy:refresh-final-exam', ['--apply' => true, '--revision' => $revision])->assertFailed();
        $this->assertDatabaseCount('questions', 50);
        $this->assertSame('published', $q->fresh()->publication_status);
    }
}
