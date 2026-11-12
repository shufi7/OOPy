<?php

namespace Tests\Feature\Admin;

use App\Models\FinalExamAttempt;
use App\Models\Quiz;
use App\Models\User;
use App\Services\Admin\LearningReportService;
use App\Services\DashboardProgressService;
use Database\Seeders\OopyContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalEvaluationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(OopyContentSeeder::class);
    }

    private function start(User $user): FinalExamAttempt
    {
        $data = $this->actingAs($user)->postJson(route('evaluasi.attempts.start'))->assertOk()->json('attempt');

        return FinalExamAttempt::findOrFail($data['id']);
    }

    private function answers(FinalExamAttempt $attempt): array
    {
        return collect($attempt->snapshot)->mapWithKeys(fn ($q) => [$q['id'] => match ($q['type']) {
            'multiple_choice' => $q['correct'], 'code_fill' => $q['answer'], default => 'Penjelasan konsep dan contoh yang dinilai oleh Admin.',
        }])->all();
    }

    private function legacyAttempt(User $user): FinalExamAttempt
    {
        $attempt = $this->start($user);
        $snapshot = require base_path('tests/Fixtures/legacy-final-exam.php');
        $attempt->update(['snapshot' => $snapshot, 'answers' => array_fill_keys(array_column($snapshot, 'id'), null)]);

        return $attempt;
    }

    public function test_page_and_active_payload_never_expose_bank_keys(): void
    {
        $response = $this->get(route('evaluasi.exam'))->assertOk();
        preg_match('/data-exam="config">(.*?)<\/script>/s', $response->getContent(), $match);
        $config = json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);
        foreach ($config['questions'] as $q) {
            $this->assertArrayNotHasKey('correct', $q);
            $this->assertArrayNotHasKey('answer', $q);
        }
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson(route('evaluasi.attempts.start'))->assertOk();
        foreach ($response->json('attempt.questions') as $q) {
            $this->assertArrayNotHasKey('correct', $q);
            $this->assertArrayNotHasKey('answer', $q);
            $this->assertArrayNotHasKey('rubric', $q);
        }
        $this->assertArrayNotHasKey('snapshot', $response->json('attempt'));
        $this->assertArrayNotHasKey('result', $response->json('attempt'));
    }

    public function test_server_grades_all_twenty_answers_and_completes_without_manual_grading(): void
    {
        $attempt = $this->start(User::factory()->create());
        $response = $this->postJson(route('evaluasi.attempts.submit', $attempt->id), ['answers' => $this->answers($attempt), 'total_score' => 100, 'essay_points' => 25])->assertOk();
        $response->assertJsonPath('attempt.result.score', 100)->assertJsonPath('attempt.result.objectivePoints', 100)
            ->assertJsonPath('attempt.result.totalScore', 100)->assertJsonPath('attempt.result.essayPoints', null)
            ->assertJsonPath('attempt.result.essayTotal', 0)->assertJsonPath('attempt.result.passed', true);
        $attempt->refresh();
        $this->assertSame('completed', $attempt->status);
        $this->assertSame('100.00', $attempt->total_score);
        $this->assertCount(20, $attempt->answers);
        $this->assertSame('100.00', $attempt->objective_points);
        $this->assertNotNull($attempt->submitted_at);
    }

    public function test_start_resumes_one_attempt_and_submitting_twice_does_not_change_history(): void
    {
        $user = User::factory()->create();
        $attempt = $this->start($user);
        $this->postJson(route('evaluasi.attempts.start'))->assertOk()->assertJsonPath('attempt.id', $attempt->id);
        $this->assertSame(1, FinalExamAttempt::count());
        $this->postJson(route('evaluasi.attempts.submit', $attempt->id), ['answers' => $this->answers($attempt)])->assertOk();
        $before = $attempt->fresh()->getAttributes();
        $this->postJson(route('evaluasi.attempts.submit', $attempt->id), ['answers' => []])->assertOk();
        $this->assertSame($before['answers'], $attempt->fresh()->getAttributes()['answers']);
        $this->assertSame($before['objective_score'], $attempt->fresh()->getAttributes()['objective_score']);
        $this->assertSame($before['submitted_at'], $attempt->fresh()->getAttributes()['submitted_at']);
    }

    public function test_deadline_is_server_owned_and_late_payload_cannot_replace_saved_answers(): void
    {
        $attempt = $this->start(User::factory()->create());
        $q = $attempt->snapshot[0];
        $this->putJson(route('evaluasi.attempts.save', $attempt->id), ['answers' => [$q['id'] => $q['correct']]])->assertOk();
        $this->travel(41)->minutes();
        $this->putJson(route('evaluasi.attempts.save', $attempt->id), ['answers' => $this->answers($attempt)])->assertStatus(409);
        $response = $this->postJson(route('evaluasi.attempts.submit', $attempt->id), ['answers' => $this->answers($attempt), 'deadline' => now()->addHour()->timestamp])->assertOk();
        $response->assertJsonPath('attempt.result.correct', 1)->assertJsonPath('attempt.timedOut', true);
        $this->assertTrue($attempt->fresh()->submitted_at->equalTo($attempt->deadline_at));
    }

    public function test_answer_validation_and_account_ownership_are_enforced(): void
    {
        $attempt = $this->start(User::factory()->create());
        $q = $attempt->snapshot[0];
        $this->putJson(route('evaluasi.attempts.save', $attempt->id), ['answers' => [$q['id'] => '0']])->assertUnprocessable();
        $this->putJson(route('evaluasi.attempts.save', $attempt->id), ['answers' => ['unknown' => 1]])->assertUnprocessable();
        $this->actingAs(User::factory()->create())->putJson(route('evaluasi.attempts.save', $attempt->id), ['answers' => []])->assertNotFound();
        $this->postJson(route('evaluasi.attempts.submit', $attempt->id), ['answers' => []])->assertNotFound();
    }

    public function test_manual_essay_grading_is_bounded_admin_only_and_publishes_valid_total(): void
    {
        $user = User::factory()->create();
        $attempt = $this->legacyAttempt($user);
        $this->postJson(route('evaluasi.attempts.submit', $attempt->id), ['answers' => $this->answers($attempt)])->assertOk();
        $this->assertSame('pending_review', $attempt->fresh()->status);
        $scores = collect($attempt->snapshot)->where('type', 'essay')->mapWithKeys(fn ($q) => [$q['id'] => 4])->all();
        $this->get(route('admin.evaluations.show', $attempt))->assertForbidden();
        $this->put(route('admin.evaluations.update', $attempt), ['scores' => $scores])->assertForbidden();
        $admin = User::factory()->create();
        $admin->role = 'admin';
        $admin->save();
        $this->actingAs($admin)->get(route('admin.evaluations.show', $attempt))->assertOk()->assertSee('0–5 poin');
        $this->put(route('admin.evaluations.update', $attempt), ['scores' => array_replace($scores, [array_key_first($scores) => 6])])->assertSessionHasErrors();
        $this->put(route('admin.evaluations.update', $attempt), ['scores' => $scores])->assertRedirect(route('admin.evaluations.show', $attempt));
        $attempt->refresh();
        $this->assertSame('completed', $attempt->status);
        $this->assertSame('20.00', $attempt->essay_points);
        $this->assertSame('95.00', $attempt->total_score);
        $this->assertSame($admin->id, $attempt->graded_by);
        $this->actingAs($user)->getJson(route('evaluasi.attempts.state'))->assertOk()->assertJsonPath('history.0.result.totalScore', 95);
    }

    public function test_question_revision_cannot_change_an_existing_attempt_snapshot(): void
    {
        $attempt = $this->start(User::factory()->create());
        $quiz = Quiz::where('type', 'final_exam')->firstOrFail();
        $question = $quiz->questions->first();
        $question->update(['correct_answer' => (string) (((int) $question->correct_answer + 1) % 4)]);
        $this->postJson(route('evaluasi.attempts.submit', $attempt->id), ['answers' => $this->answers($attempt)])->assertOk()->assertJsonPath('attempt.result.correct', 20);
    }

    public function test_admin_authored_numeric_string_and_operator_code_keys_are_gradeable_without_execution(): void
    {
        $questions = Quiz::where('type', 'final_exam')->firstOrFail()->questions()->where('question_type', 'code_fill')->get();
        foreach (['42', "'Rawa Kalimantan'", 'nilai + 1', '[1, 2, 3]', 'print("<script>alert(1)</script>")'] as $index => $key) {
            $questions[$index]->update(['correct_answer' => $key]);
        }
        $attempt = $this->start(User::factory()->create());
        $this->postJson(route('evaluasi.attempts.submit', $attempt->id), ['answers' => $this->answers($attempt)])->assertOk()->assertJsonPath('attempt.result.correct', 20);
    }

    public function test_guest_can_start_a_new_practice_after_the_existing_deadline(): void
    {
        $id = $this->postJson(route('evaluasi.attempts.start'))->assertOk()->json('attempt.id');
        $this->travel(41)->minutes();
        $next = $this->postJson(route('evaluasi.attempts.start'))->assertOk()->json('attempt.id');
        $this->assertNotSame($id, $next);
        $this->getJson(route('evaluasi.attempts.state'))->assertOk()->assertJsonCount(1, 'history');
    }

    public function test_guest_practice_uses_session_and_never_creates_official_records(): void
    {
        $response = $this->postJson(route('evaluasi.attempts.start'))->assertOk()->assertJsonPath('attempt.official', false);
        $id = $response->json('attempt.id');
        $this->postJson(route('evaluasi.attempts.submit', $id), ['answers' => []])->assertOk()->assertJsonPath('attempt.official', false);
        $this->assertSame(0, FinalExamAttempt::count());
        $this->getJson(route('evaluasi.attempts.state'))->assertOk()->assertJsonCount(1, 'history');
        $this->flushSession();
        $this->postJson(route('evaluasi.attempts.submit', $id), ['answers' => []])->assertNotFound();
    }

    public function test_scores_thresholds_and_official_reports_use_server_totals(): void
    {
        $user = User::factory()->create();
        foreach ([20, 18, 16, 14, 13, 10, 0] as $correct) {
            $this->travel(61)->seconds();
            $attempt = $this->start($user);
            $this->assertSame(2400, $attempt->duration_seconds);
            $this->assertSame('70.00', $attempt->passing_score);
            $answers = $this->answers($attempt);
            foreach (array_slice($attempt->snapshot, $correct) as $q) {
                $answers[$q['id']] = $q['type'] === 'multiple_choice' ? ($q['correct'] + 1) % count($q['options']) : 'salah';
            }
            $response = $this->postJson(route('evaluasi.attempts.submit', $attempt->id), [
                'answers' => $answers, 'total_score' => 99, 'status' => 'pending_review', 'objective_points' => 99,
            ])->assertOk()->assertJsonPath('attempt.result.correct', $correct)
                ->assertJsonPath('attempt.result.incorrect', 20 - $correct)
                ->assertJsonPath('attempt.result.totalScore', $correct * 5)
                ->assertJsonPath('attempt.result.passed', $correct >= 14);
            $this->assertSame('completed', $attempt->fresh()->status);
            foreach ($response->json('attempt.questions') as $q) {
                $this->assertArrayNotHasKey('answer', $q);
                $this->assertArrayNotHasKey('correct', $q);
            }
            $before = $attempt->fresh()->getAttributes();
            $this->postJson(route('evaluasi.attempts.submit', $attempt->id), ['answers' => $this->answers($attempt)])->assertOk();
            $this->assertSame($before, $attempt->fresh()->getAttributes());
        }
        $reports = app(LearningReportService::class);
        $reported = $reports->users()->whereKey($user->id)->firstOrFail();
        $this->assertEquals(100, $reported->final_score);
        $this->assertEquals(0, $reported->final_pending);
        $dashboard = app(DashboardProgressService::class)->forUser($user);
        $this->assertSame('100', $dashboard['final_exam']['best_score_label']);
        $this->assertSame(7, $dashboard['final_exam']['submitted']);
        $this->get('/dashboard')->assertOk()->assertSee('data-dashboard="final-exam"', false)->assertSee('100');
        $admin = User::factory()->create(['role' => 'admin']);
        $stats = $this->actingAs($admin)->get('/admin')->assertOk()->viewData('stats');
        $this->assertSame(7, $stats['finals']);
        $this->assertSame(0, $stats['pending']);
        $this->assertEqualsWithDelta(455 / 7, $stats['final_average'], 0.01);
        $this->get('/admin/scores?q='.$user->email)->assertOk()->assertSee('100');
        $this->get('/admin/users/'.$user->id)->assertOk()->assertDontSee('Menunggu penilaian uraian');
        $this->get('/admin/evaluations/'.$attempt->id)->assertOk()->assertDontSee('Simpan penilaian uraian')->assertSee('dinilai otomatis');
        $this->putJson('/admin/evaluations/'.$attempt->id, ['scores' => []])->assertStatus(409);
        $csv = $this->get('/admin/scores/export?q='.$user->email)->assertOk()->streamedContent();
        $rows = array_map(fn ($line) => str_getcsv($line, ';', '"', ''), explode("\n", trim($csv)));
        $this->assertSame('100', $rows[1][8]);
        $this->assertSame('Dinilai', $rows[1][9]);
    }

    public function test_saved_answers_resume_with_identical_deadline_and_code_tokens_are_tolerated(): void
    {
        $attempt = $this->start(User::factory()->create());
        $answers = $this->answers($attempt);
        $code = collect($attempt->snapshot)->firstWhere('type', 'code_fill');
        $answers[$code['id']] = ' self . nilai= nilai ';
        $this->putJson(route('evaluasi.attempts.save', $attempt->id), ['answers' => $answers])->assertOk();
        $this->travel(10)->minutes();
        $this->getJson(route('evaluasi.attempts.state'))->assertOk()
            ->assertJsonPath('active.deadline', $attempt->deadline_at->getTimestampMs())
            ->assertJsonPath('active.answers.'.$code['id'], 'self . nilai= nilai');
        $this->postJson(route('evaluasi.attempts.submit', $attempt->id), ['answers' => []])->assertOk()
            ->assertJsonPath('attempt.result.totalScore', 100);
    }

    public function test_completed_historical_scores_are_not_recalculated_on_read_or_submit(): void
    {
        $user = User::factory()->create();
        $attempt = $this->legacyAttempt($user);
        $attempt->update([
            'status' => 'completed', 'submitted_at' => now(), 'objective_score' => 80,
            'objective_points' => 60, 'essay_points' => 17, 'essay_score' => 68, 'total_score' => 77,
        ]);
        $before = $attempt->fresh()->getAttributes();
        $this->getJson(route('evaluasi.attempts.state'))->assertOk()->assertJsonPath('history.0.result.totalScore', 77);
        $this->postJson(route('evaluasi.attempts.submit', $attempt->id), ['answers' => $this->answers($attempt)])->assertOk()
            ->assertJsonPath('attempt.result.totalScore', 77);
        $this->assertSame($before, $attempt->fresh()->getAttributes());
    }
}
