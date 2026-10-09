<?php

namespace Tests\Feature\Quiz;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

class QuizAuthorizationTest extends QuizTestCase
{
    public function test_guests_cannot_start_submit_or_read_account_progress_but_can_read_material(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $attempt = $this->start();
        $this->post('/logout');
        $this->postJson('/materi/dasar-pemrograman-oop/kuis/attempts')->assertUnauthorized();
        $this->postJson($attempt['submit_url'], $this->answers($this->quiz()))->assertUnauthorized();
        $this->getJson('/materi/dasar-pemrograman-oop/kuis/progress')->assertUnauthorized();
        $this->post('/materi/dasar-pemrograman-oop/kuis/attempts')->assertRedirect('/login');
        $this->get('/materi/dasar-pemrograman-oop')->assertOk()->assertSee('Masuk untuk Mengerjakan Kuis');
        $this->assertDatabaseCount('quiz_answers', 0);
    }

    public function test_another_user_including_admin_cannot_submit_someone_elses_attempt(): void
    {
        $this->actingAs(User::factory()->create());
        $attempt = $this->start();
        $other = User::factory()->create();
        $this->actingAs($other)->postJson($attempt['submit_url'], $this->answers($this->quiz()))->assertForbidden();
        $other->role = 'admin';
        $other->save();
        $this->postJson($attempt['submit_url'], $this->answers($this->quiz()))->assertForbidden();
        $this->assertDatabaseCount('quiz_answers', 0);
    }

    public function test_forged_grading_and_identity_fields_are_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $attempt = $this->start();
        foreach (['score' => 100, 'passed' => true, 'is_correct' => true, 'best_score' => 100, 'correct_count' => 5, 'user_id' => 999, 'role' => 'admin'] as $key => $value) {
            $this->postJson($attempt['submit_url'], $this->answers($this->quiz(), 0) + [$key => $value])
                ->assertUnprocessable()->assertJsonValidationErrors($key);
        }
        $nested = $this->answers($this->quiz(), 0);
        $nested['answers'][0]['is_correct'] = true;
        $this->postJson($attempt['submit_url'], $nested)->assertUnprocessable();
        $this->postJson($attempt['submit_url'], $this->answers($this->quiz(), 0))->assertOk()->assertJsonPath('result.score', 0);
        $this->assertDatabaseHas('quiz_attempts', ['id' => $attempt['attempt_id'], 'user_id' => $user->id, 'score' => 0]);
    }

    public function test_attempts_cannot_be_submitted_under_another_chapter_and_final_exam_is_excluded(): void
    {
        $this->actingAs(User::factory()->create());
        $attempt = $this->start();
        $this->postJson('/materi/kelas-dan-objek/kuis/attempts/'.$attempt['attempt_id'].'/submit', $this->answers($this->quiz()))->assertNotFound();
        foreach (['evaluasi-akhir', 'unknown'] as $slug) {
            $this->postJson("/materi/$slug/kuis/attempts")->assertNotFound();
            $this->getJson("/materi/$slug/kuis/progress")->assertNotFound();
        }
    }

    public function test_csrf_is_enforced_on_both_quiz_write_endpoints(): void
    {
        $this->actingAs(User::factory()->create());
        $attempt = $this->start();
        $this->app->instance(ValidateCsrfToken::class, new class($this->app, $this->app['encrypter']) extends ValidateCsrfToken
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
        $this->postJson('/materi/dasar-pemrograman-oop/kuis/attempts')->assertStatus(419);
        $this->postJson($attempt['submit_url'], $this->answers($this->quiz()))->assertStatus(419);
        $this->assertDatabaseCount('quiz_answers', 0);
    }

    public function test_html_and_all_json_responses_are_whitelisted_without_answer_keys_or_per_question_results(): void
    {
        $this->actingAs(User::factory()->create());
        $html = $this->get('/materi/dasar-pemrograman-oop')->assertOk()->getContent();
        preg_match('/data-quiz="questions">(.*?)<\/script>/s', $html, $matches);
        foreach (json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR) as $question) {
            $this->assertSame(['question_id', 'question_order', 'type', 'question', 'options', 'code'], array_keys($question));
            $this->assertIsInt($question['question_id']);
        }
        $attempt = $this->start();
        $responses = [
            $attempt,
            $this->getJson('/materi/dasar-pemrograman-oop/kuis/progress')->assertOk()->json(),
            $this->postJson($attempt['submit_url'], $this->answers($this->quiz()))->assertOk()->json(),
        ];
        foreach ($responses as $response) {
            $this->assertNoKeys($response);
        }
    }

    private function assertNoKeys(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->assertNotContains($key, ['correct', 'correct_answer', 'answer', 'answers', 'is_correct', 'explanation']);
            if (is_array($value)) {
                $this->assertNoKeys($value);
            }
        }
    }
}
