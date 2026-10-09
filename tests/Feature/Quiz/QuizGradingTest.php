<?php

namespace Tests\Feature\Quiz;

use App\Models\Quiz;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;

class QuizGradingTest extends QuizTestCase
{
    public static function scores(): array
    {
        return array_map(fn ($correct) => [$correct, $correct * 20, $correct >= 4], range(0, 5));
    }

    #[DataProvider('scores')]
    public function test_every_score_and_the_four_correct_boundary(int $correct, int $score, bool $passed): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $quiz = $this->quiz();
        $attempt = $this->start();
        $response = $this->postJson($attempt['submit_url'], $this->answers($quiz, $correct))->assertOk()
            ->assertJsonPath('result.score', $score)->assertJsonPath('result.correct_count', $correct)
            ->assertJsonPath('result.incorrect_count', 5 - $correct)->assertJsonPath('result.passed', $passed)
            ->assertJsonPath('progress.next_unlocked', $passed);
        $this->assertDatabaseHas('quiz_attempts', ['id' => $attempt['attempt_id'], 'user_id' => $user->id, 'status' => 'completed', 'score' => $score]);
        $this->assertDatabaseCount('quiz_answers', 5);
        $this->assertDatabaseHas('user_progress', ['user_id' => $user->id, 'status' => $passed ? 'completed' : 'in_progress']);
        $this->assertSame($passed, $response->json('progress.completed_at') !== null);
    }

    public function test_all_six_quizzes_are_graded_from_their_database_keys(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (Quiz::where('type', 'chapter_quiz')->with('chapter')->get() as $quiz) {
            $attempt = $this->start($quiz->chapter->slug);
            $this->postJson($attempt['submit_url'], $this->answers($this->quiz($quiz->chapter->slug), 4))
                ->assertOk()->assertJsonPath('result.score', 80)->assertJsonPath('result.passed', true);
        }
        $this->assertDatabaseCount('quiz_attempts', 6);
        $this->assertDatabaseCount('quiz_answers', 30);
        $this->assertDatabaseCount('user_progress', 6);
    }

    public function test_code_fill_trims_ends_but_preserves_case_and_internal_spacing(): void
    {
        $this->actingAs(User::factory()->create());
        $quiz = $this->quiz('kelas-dan-objek');
        $payload = $this->answers($quiz);
        $payload['answers'][3]['answer'] = 'self.nama  = nama';
        $payload['answers'][4]['answer'] = 'ekosistem';
        $this->postJson($this->start('kelas-dan-objek')['submit_url'], $payload)
            ->assertOk()->assertJsonPath('result.correct_count', 3)->assertJsonPath('result.passed', false);
        $this->postJson($this->start('kelas-dan-objek')['submit_url'], $this->answers($quiz))
            ->assertOk()->assertJsonPath('result.correct_count', 5);
        $this->assertDatabaseHas('quiz_answers', ['question_id' => $quiz->questions[3]->id, 'answer_text' => 'self.nama = nama', 'is_correct' => true]);
    }

    public function test_code_fill_preserves_the_original_javascript_trim_semantics(): void
    {
        $this->actingAs(User::factory()->create());
        foreach ([["\u{00A0}\u{2028}return\u{FEFF}\u{3000}", 5], ["\u{200B}return\u{200B}", 4], ["\0return\0", 4]] as [$answer, $correct]) {
            $payload = $this->answers($this->quiz());
            $payload['answers'][3]['answer'] = $answer;
            $this->postJson($this->start()['submit_url'], $payload)->assertOk()->assertJsonPath('result.correct_count', $correct);
        }
    }
}
