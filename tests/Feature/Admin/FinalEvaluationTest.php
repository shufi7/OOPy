<?php

namespace Tests\Feature\Admin;

use App\Models\FinalExamAttempt;
use App\Models\Quiz;
use App\Models\User;
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

    public function test_server_grades_objective_answers_ignores_client_score_and_waits_for_manual_essays(): void
    {
        $attempt = $this->start(User::factory()->create());
        $response = $this->postJson(route('evaluasi.attempts.submit', $attempt->id), ['answers' => $this->answers($attempt), 'total_score' => 100, 'essay_points' => 25])->assertOk();
        $response->assertJsonPath('attempt.result.score', 100)->assertJsonPath('attempt.result.objectivePoints', 75)
            ->assertJsonPath('attempt.result.totalScore', null)->assertJsonPath('attempt.result.essayPoints', null);
        $attempt->refresh();
        $this->assertSame('pending_review', $attempt->status);
        $this->assertNull($attempt->total_score);
        $this->assertCount(20, $attempt->answers);
        $this->assertSame('75.00', $attempt->objective_points);
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
        $attempt = $this->start($user);
        $this->postJson(route('evaluasi.attempts.submit', $attempt->id), ['answers' => $this->answers($attempt)])->assertOk();
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
        $this->postJson(route('evaluasi.attempts.submit', $attempt->id), ['answers' => $this->answers($attempt)])->assertOk()->assertJsonPath('attempt.result.correct', 15);
    }

    public function test_admin_authored_numeric_string_and_operator_code_keys_are_gradeable_without_execution(): void
    {
        $questions = Quiz::where('type', 'final_exam')->firstOrFail()->questions()->where('question_type', 'code_fill')->get();
        foreach (['42', "'Rawa Kalimantan'", 'nilai + 1', '[1, 2, 3]', 'print("<script>alert(1)</script>")'] as $index => $key) {
            $questions[$index]->update(['correct_answer' => $key]);
        }
        $attempt = $this->start(User::factory()->create());
        $this->postJson(route('evaluasi.attempts.submit', $attempt->id), ['answers' => $this->answers($attempt)])->assertOk()->assertJsonPath('attempt.result.correct', 15);
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
}
