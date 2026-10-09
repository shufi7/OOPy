<?php

namespace Tests\Feature\Quiz;

use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class QuizSubmissionTest extends QuizTestCase
{
    public function test_refresh_does_not_create_an_attempt_and_repeated_start_reuses_the_active_attempt(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/materi/dasar-pemrograman-oop')->assertOk();
        $this->get('/materi/dasar-pemrograman-oop')->assertOk();
        $this->assertDatabaseCount('quiz_attempts', 0);
        $first = $this->start();
        $this->assertSame($first['attempt_id'], $this->start()['attempt_id']);
        $this->assertDatabaseCount('quiz_attempts', 1);
        $this->assertDatabaseCount('quiz_answers', 0);
    }

    public function test_duplicate_submission_returns_the_stored_result_and_cannot_regrade_it(): void
    {
        $this->actingAs(User::factory()->create());
        $attempt = $this->start();
        $first = $this->postJson($attempt['submit_url'], $this->answers($this->quiz(), 4))->assertOk()->json();
        $completedAt = QuizAttempt::findOrFail($attempt['attempt_id'])->completed_at;
        $this->travel(10)->minutes();
        $this->assertSame($first, $this->postJson($attempt['submit_url'], $this->answers($this->quiz(), 0))->assertOk()->json());
        $this->assertTrue($completedAt->equalTo(QuizAttempt::findOrFail($attempt['attempt_id'])->completed_at));
        $this->assertDatabaseCount('quiz_attempts', 1);
        $this->assertDatabaseCount('quiz_answers', 5);
        $this->assertDatabaseCount('user_progress', 1);
        $this->assertNotSame($attempt['attempt_id'], $this->start()['attempt_id']);
    }

    public function test_completion_preserves_the_original_start_time(): void
    {
        $this->actingAs(User::factory()->create());
        $attempt = $this->start();
        $startedAt = QuizAttempt::findOrFail($attempt['attempt_id'])->started_at;
        $this->travel(2)->minutes();
        $this->postJson($attempt['submit_url'], $this->answers($this->quiz()))->assertOk();
        $saved = QuizAttempt::findOrFail($attempt['attempt_id']);
        $this->assertTrue($startedAt->equalTo($saved->started_at));
        $this->assertTrue($saved->completed_at->greaterThanOrEqualTo($saved->started_at));
    }

    public function test_incomplete_duplicate_and_cross_quiz_question_sets_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $attempt = $this->start();
        $payload = $this->answers($this->quiz());
        $this->postJson($attempt['submit_url'], ['answers' => array_slice($payload['answers'], 0, 4)])
            ->assertUnprocessable()->assertJsonValidationErrors('answers');
        $duplicate = $payload;
        $duplicate['answers'][1]['question_id'] = $duplicate['answers'][0]['question_id'];
        $this->postJson($attempt['submit_url'], $duplicate)->assertUnprocessable();
        $foreign = $payload;
        $foreign['answers'][0]['question_id'] = $this->quiz('kelas-dan-objek')->questions[0]->id;
        $this->postJson($attempt['submit_url'], $foreign)->assertUnprocessable()->assertJsonValidationErrors('answers.0.question_id');
        $foreign['answers'][0]['question_id'] = 999999;
        $this->postJson($attempt['submit_url'], $foreign)->assertUnprocessable();
        $this->assertDatabaseHas('quiz_attempts', ['id' => $attempt['attempt_id'], 'status' => 'in_progress', 'score' => null]);
        $this->assertDatabaseCount('quiz_answers', 0);
        $this->assertDatabaseCount('user_progress', 0);
    }

    public function test_invalid_choice_indices_and_non_string_empty_or_oversized_code_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $attempt = $this->start();
        foreach ([[0, -1], [0, 4], [0, '0'], [0, true], [3, 123], [3, []], [3, '   '], [3, str_repeat('a', 1001)]] as [$index, $answer]) {
            $payload = $this->answers($this->quiz());
            $payload['answers'][$index]['answer'] = $answer;
            $this->postJson($attempt['submit_url'], $payload)->assertUnprocessable()->assertJsonValidationErrors("answers.$index.answer");
        }
        $this->assertDatabaseCount('quiz_answers', 0);
        $this->assertDatabaseCount('user_progress', 0);
    }

    public function test_database_failure_rolls_back_answers_attempt_completion_and_progress_then_retry_succeeds(): void
    {
        $this->actingAs(User::factory()->create());
        $quiz = $this->quiz();
        $attempt = $this->start();
        $questionId = $quiz->questions[2]->id;
        DB::statement("CREATE TRIGGER test_quiz_insert_failure BEFORE INSERT ON quiz_answers WHEN NEW.question_id = $questionId BEGIN SELECT RAISE(ABORT, 'test insert failure'); END");
        $this->postJson($attempt['submit_url'], $this->answers($quiz))->assertStatus(503);
        $this->assertDatabaseCount('quiz_answers', 0);
        $this->assertDatabaseCount('user_progress', 0);
        $this->assertDatabaseHas('quiz_attempts', ['id' => $attempt['attempt_id'], 'status' => 'in_progress', 'score' => null, 'completed_at' => null]);
        DB::statement('DROP TRIGGER test_quiz_insert_failure');
        $this->postJson($attempt['submit_url'], $this->answers($quiz))->assertOk()->assertJsonPath('result.passed', true);
        $this->assertDatabaseCount('quiz_answers', 5);
    }
}
