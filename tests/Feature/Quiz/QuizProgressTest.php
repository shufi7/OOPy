<?php

namespace Tests\Feature\Quiz;

use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Support\Facades\Auth;

class QuizProgressTest extends QuizTestCase
{
    public function test_progress_best_score_and_first_completion_remain_monotonic_across_retries(): void
    {
        $this->actingAs(User::factory()->create());
        $firstCompletedAt = null;
        foreach ([3, 4, 2, 5, 0] as $index => $correct) {
            $this->travel(1)->minutes();
            $response = $this->postJson($this->start()['submit_url'], $this->answers($this->quiz(), $correct))->assertOk();
            $response->assertJsonPath('progress.next_unlocked', $index > 0)
                ->assertJsonPath('progress.best_score', $index < 1 ? 60 : ($index < 3 ? 80 : 100));
            if ($index === 1) {
                $firstCompletedAt = UserProgress::sole()->completed_at;
            } elseif ($index > 1) {
                $this->assertTrue($firstCompletedAt->equalTo(UserProgress::sole()->completed_at));
            }
        }
        $this->assertDatabaseCount('quiz_attempts', 5);
        $this->assertDatabaseCount('quiz_answers', 25);
        $this->assertDatabaseCount('user_progress', 1);
    }

    public function test_refresh_new_session_logout_and_relogin_preserve_account_progress_without_mixing_accounts(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->postJson($this->start()['submit_url'], $this->answers($this->quiz(), 4))->assertOk();
        $this->get('/materi/dasar-pemrograman-oop')->assertOk()->assertViewHas('quizProgress', fn ($progress) => $progress['passed']);
        $this->post('/logout')->assertRedirect('/');
        $this->assertDatabaseHas('user_progress', ['user_id' => $user->id, 'status' => 'completed']);

        Auth::forgetGuards();
        $this->flushSession();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/materi');
        $this->getJson('/materi/dasar-pemrograman-oop/kuis/progress')->assertOk()->assertJsonPath('next_unlocked', true)->assertJsonPath('best_score', 80);
        $this->actingAs(User::factory()->create())->getJson('/materi/dasar-pemrograman-oop/kuis/progress?user_id='.$user->id)
            ->assertOk()->assertJsonPath('next_unlocked', false)->assertJsonPath('best_score', 0);
        $this->assertDatabaseCount('quiz_attempts', 1);
    }

    public function test_page_and_progress_do_not_trust_forged_local_storage_or_request_status(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/materi/dasar-pemrograman-oop?passed=true&score=100')->assertOk()
            ->assertViewHas('quizProgress', fn ($progress) => ! $progress['passed']);
        $this->getJson('/materi/dasar-pemrograman-oop/kuis/progress?passed=true&best_score=100')
            ->assertOk()->assertJsonPath('next_unlocked', false);
        $script = file_get_contents(public_path('js/oopy-quiz.js'));
        $this->assertStringNotContainsString('localStorage', $script);
        $this->assertStringNotContainsString('isCorrect', $script);
    }

    public function test_only_completed_attempts_count_towards_best_score_and_bad_configuration_fails_closed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        QuizAttempt::create(['user_id' => $user->id, 'quiz_id' => $this->quiz()->id, 'score' => 100]);
        $this->getJson('/materi/dasar-pemrograman-oop/kuis/progress')->assertOk()->assertJsonPath('best_score', 0);
        $this->quiz()->update(['passing_score' => 70]);
        $this->postJson('/materi/dasar-pemrograman-oop/kuis/attempts')->assertStatus(503);
    }
}
