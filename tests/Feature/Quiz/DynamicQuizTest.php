<?php

namespace Tests\Feature\Quiz;

use App\Models\Quiz;
use App\Models\User;
use App\Services\Admin\LearningReportService;
use App\Services\Admin\QuestionBankService;
use App\Services\ChapterQuizScoring;
use App\Services\ChapterQuizService;
use App\Services\DashboardProgressService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;

class DynamicQuizTest extends QuizTestCase
{
    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin;
    }

    private function drafts(Quiz $quiz, int $count)
    {
        return collect(range(1, $count))->map(fn ($number) => app(QuestionBankService::class)->save([
            'quiz_id' => $quiz->id,
            'question_type' => $number % 2 === 0 ? 'code_fill' : 'multiple_choice',
            'question_text' => 'Soal dinamis '.$number,
            'code_snippet' => $number % 2 === 0 ? "def fungsi():\n    ___ 1" : null,
            'options' => $number % 2 === 0 ? null : ['Benar', 'Salah'],
            'correct_answer' => $number % 2 === 0 ? 'return' : '0',
            'display_order' => $number,
        ]));
    }

    private function publish(Quiz $quiz, array $ids, ?string $version = null)
    {
        return $this->actingAs($this->admin())->postJson('/admin/questions/quizzes/'.$quiz->id.'/publish', [
            'question_ids' => $ids,
            'package_version' => $version ?? app(QuestionBankService::class)->packageVersion($quiz->questions()->get()),
        ]);
    }

    public static function scores(): array
    {
        return [
            'five, four correct' => [5, 4, 80.0, true],
            'six, five correct' => [6, 5, 83.33, true],
            'six, four correct' => [6, 4, 66.67, false],
            'ten, eight correct' => [10, 8, 80.0, true],
            'twenty, fifteen correct' => [20, 15, 75.0, false],
            'all correct' => [30, 30, 100.0, true],
            'all incorrect' => [10, 0, 0.0, false],
            'one question' => [1, 1, 100.0, true],
        ];
    }

    #[DataProvider('scores')]
    public function test_dynamic_publication_grading_progress_and_reports(int $total, int $correct, float $score, bool $passed): void
    {
        $quiz = $this->quiz();
        $drafts = $this->drafts($quiz, $total);
        $this->assertCount(5, $quiz->fresh()->questions);
        $this->publish($quiz, $drafts->pluck('id')->all())->assertRedirect();
        $user = User::factory()->create();
        $this->actingAs($user);
        $quiz = $quiz->fresh('questions');
        $this->get('/materi/'.$quiz->chapter->slug)->assertOk()
            ->assertViewHas('quizConfig', fn ($config) => $config['total_questions'] === $total
                && $config['minimum_correct'] === (int) ceil($total * .8));
        $attempt = $this->start();
        $this->assertCount($total, $attempt['questions']);
        $this->assertSame($total, $attempt['scoring']['total_questions']);
        $this->assertEqualsWithDelta(100 / $total, $attempt['scoring']['points_per_question'], .0000001);
        $response = $this->postJson($attempt['submit_url'], $this->answers($quiz, $correct))->assertOk()
            ->assertJsonPath('result.correct_count', $correct)->assertJsonPath('result.incorrect_count', $total - $correct)
            ->assertJsonPath('result.total_questions', $total)->assertJsonPath('result.passed', $passed)
            ->assertJsonPath('progress.next_unlocked', $passed);
        $this->assertEquals($score, $response->json('result.score'));
        $this->assertDatabaseHas('quiz_attempts', ['id' => $attempt['attempt_id'], 'score' => $score]);
        $this->assertDatabaseCount('quiz_answers', $total);
        $dashboard = app(DashboardProgressService::class)->forUser($user);
        $this->assertSame($passed ? 1 : 0, $dashboard['completed_chapters']);
        $this->assertSame($passed, $dashboard['recent_activity'][0]['passed']);
        $this->assertSame(ChapterQuizScoring::format($score), $dashboard['chapters'][0]['best_score_label']);
        $reports = app(LearningReportService::class);
        $filtered = $reports->users(['chapter' => 1, 'status' => $passed ? 'passed' : 'not_passed'])->get();
        $this->assertTrue($filtered->contains('id', $user->id));
        $row = $reports->row($filtered->firstWhere('id', $user->id));
        $this->assertSame($score, $row['scores'][1]);
        $this->actingAs($this->admin())->get('/admin/scores/export?q='.urlencode($user->email))->assertOk();
    }

    public function test_open_and_historical_attempts_keep_their_package_and_scores_after_publication(): void
    {
        $quiz = $this->quiz();
        $oldAnswers = $this->answers($quiz, 4);
        $student = User::factory()->create();
        $this->actingAs($student);
        $old = $this->start();
        $this->publish($quiz, $this->drafts($quiz, 10)->pluck('id')->all())->assertRedirect();
        $this->actingAs($student)->get('/materi/'.$quiz->chapter->slug)->assertOk()
            ->assertViewHas('quizConfig', fn ($config) => $config['total_questions'] === 5);
        $this->assertSame($old['questions'], $this->start()['questions']);
        $first = $this->postJson($old['submit_url'], $oldAnswers)->assertOk()->assertJsonPath('result.score', 80)->json();
        $completed = $student->quizAttempts()->findOrFail($old['attempt_id']);
        $this->assertCount(5, app(ChapterQuizService::class)->publicQuestions($quiz->fresh(), $student, $completed));
        $completedAt = $student->progress()->sole()->completed_at;
        $this->assertSame($first, $this->postJson($old['submit_url'], $this->answers($quiz, 0))->assertOk()->json());
        $next = $this->start();
        $this->assertCount(10, $next['questions']);
        $this->postJson($next['submit_url'], $this->answers($quiz->fresh('questions'), 0))->assertOk()
            ->assertJsonPath('result.score', 0)->assertJsonPath('result.passed', false)
            ->assertJsonPath('progress.best_score', 80)->assertJsonPath('progress.next_unlocked', true);
        $this->assertTrue($completedAt->equalTo($student->progress()->sole()->completed_at));
        $this->assertDatabaseCount('quiz_answers', 15);
        $this->assertDatabaseHas('quiz_attempts', ['id' => $old['attempt_id'], 'score' => 80]);
        $newStudent = User::factory()->create();
        $this->actingAs($newStudent);
        $this->assertCount(10, $this->start()['questions']);
        $this->assertNotNull(app(QuestionBankService::class)->deletionError($quiz->questions->first()->fresh()));
    }

    public function test_legacy_active_attempts_are_pinned_before_publication_and_completed_ones_use_their_answers(): void
    {
        $quiz = $this->quiz();
        $student = User::factory()->create();
        $legacy = $student->quizAttempts()->create(['quiz_id' => $quiz->id, 'started_at' => now()]);
        $this->publish($quiz, $this->drafts($quiz, 10)->pluck('id')->all())->assertRedirect();
        $this->assertSame(5, DB::table('quiz_attempt_questions')->where('attempt_id', $legacy->id)->count());
        $url = '/materi/'.$quiz->chapter->slug.'/kuis/attempts/'.$legacy->id.'/submit';
        $this->actingAs($student)->postJson($url, $this->answers($quiz, 4))->assertOk()->assertJsonPath('result.score', 80);
        DB::table('quiz_attempt_questions')->where('attempt_id', $legacy->id)->delete();
        $this->publish($quiz, $this->drafts($quiz, 20)->pluck('id')->all())->assertRedirect();
        $this->actingAs($student)->postJson($url, $this->answers($quiz, 0))->assertOk()
            ->assertJsonPath('result.score', 80)->assertJsonPath('result.total_questions', 5)->assertJsonPath('result.correct_count', 4);
        $this->assertSame('80.00', $legacy->fresh()->score);
    }

    public function test_invalid_publication_is_atomic_and_stale_admin_choices_are_rejected(): void
    {
        $quiz = $this->quiz();
        $original = $quiz->questions->pluck('id')->all();
        $drafts = $this->drafts($quiz, 31);
        $version = app(QuestionBankService::class)->packageVersion($quiz->questions);
        foreach ([[], $drafts->pluck('id')->all(), [$drafts[0]->id, $drafts[0]->id], [$this->quiz('kelas-dan-objek')->questions[0]->id], [999999]] as $ids) {
            $response = $this->publish($quiz, $ids)->assertUnprocessable();
            $this->assertNotEmpty($response->json('errors'));
            $this->assertSame($original, $quiz->fresh()->questions->pluck('id')->all());
        }
        $drafts[1]->update(['display_order' => $drafts[0]->display_order]);
        $this->publish($quiz, [$drafts[0]->id, $drafts[1]->id])->assertUnprocessable()->assertJsonValidationErrors('question_ids');
        $drafts[1]->update(['display_order' => 2]);
        DB::table('questions')->where('id', $drafts[1]->id)->update(['correct_answer' => ' return ']);
        $this->publish($quiz, [$drafts[1]->id])->assertUnprocessable()->assertJsonValidationErrors('question_ids');
        DB::table('questions')->where('id', $drafts[1]->id)->update(['correct_answer' => 'return']);
        $this->publish($quiz, $drafts->take(6)->pluck('id')->all())->assertRedirect();
        $this->publish($quiz, $drafts->take(10)->pluck('id')->all(), $version)->assertUnprocessable()->assertJsonValidationErrors('publication');
        $this->assertCount(6, $quiz->fresh()->questions);
        $this->publish($quiz, [$original[0], ...$drafts->take(6)->pluck('id')->all()])->assertUnprocessable()->assertJsonValidationErrors('question_ids');
        $this->assertCount(6, $quiz->fresh()->questions);
    }

    public function test_archived_chapter_questions_can_be_reselected_without_changing_attempt_history(): void
    {
        $quiz = $this->quiz();
        $six = $this->drafts($quiz, 6);
        $this->publish($quiz, $six->pluck('id')->all())->assertRedirect();
        $quiz = $quiz->fresh('questions');
        $student = User::factory()->create();
        $this->actingAs($student);
        $completed = $this->start();
        $answers = $this->answers($quiz, 5);
        $this->postJson($completed['submit_url'], $answers)->assertOk()->assertJsonPath('result.score', 83.33);
        $open = $this->start();

        $this->publish($quiz, $six->take(5)->pluck('id')->all())->assertRedirect();
        $archived = $six->last()->fresh();
        $this->assertSame('archived', $archived->publication_status);
        $html = $this->get('/admin/questions?quiz_id='.$quiz->id)->assertOk()->getContent();
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $inputs = (new \DOMXPath($document))->query('//input[@name="question_ids[]" and @value="'.$archived->id.'"]');
        $this->assertCount(1, $inputs);
        $this->assertFalse($inputs->item(0)->hasAttribute('checked'));
        $this->assertSame(5, $quiz->questions()->count());

        $this->publish($quiz, $six->pluck('id')->all())->assertRedirect();
        $this->assertSame('published', $archived->fresh()->publication_status);
        $this->assertSame($six->pluck('id')->all(), $quiz->fresh()->questions->pluck('id')->all());
        $this->actingAs($student)->postJson($open['submit_url'], $answers)->assertOk()
            ->assertJsonPath('result.score', 83.33)->assertJsonPath('result.total_questions', 6);
        $this->assertDatabaseHas('quiz_attempts', ['id' => $completed['attempt_id'], 'score' => 83.33]);
        $this->assertDatabaseCount('quiz_answers', 12);
        $this->assertNotNull(app(QuestionBankService::class)->deletionError($archived->fresh()));
        $this->assertCount(6, $this->start()['questions']);
    }

    public function test_publish_database_failure_rolls_back_archival_and_legacy_pinning(): void
    {
        $quiz = $this->quiz();
        $original = $quiz->questions->pluck('id')->all();
        $student = User::factory()->create();
        $student->quizAttempts()->create(['quiz_id' => $quiz->id]);
        $drafts = $this->drafts($quiz, 6);
        DB::statement("CREATE TRIGGER reject_dynamic_publish BEFORE UPDATE ON questions WHEN NEW.id = {$drafts[0]->id} AND NEW.publication_status = 'published' BEGIN SELECT RAISE(ABORT, 'publication failure'); END");
        try {
            app(QuestionBankService::class)->publish($quiz, $drafts->pluck('id')->all());
            $this->fail('Expected a database failure.');
        } catch (QueryException) {
            $this->assertSame($original, $quiz->fresh()->questions->pluck('id')->all());
            $this->assertDatabaseCount('quiz_attempt_questions', 0);
            $this->assertSame(6, $quiz->allQuestions()->where('publication_status', 'draft')->count());
        } finally {
            DB::statement('DROP TRIGGER reject_dynamic_publish');
        }
    }

    public function test_dynamic_answers_must_match_the_pinned_package_exactly(): void
    {
        $quiz = $this->quiz();
        $this->publish($quiz, $this->drafts($quiz, 6)->pluck('id')->all())->assertRedirect();
        $this->actingAs(User::factory()->create());
        $attempt = $this->start();
        $payload = $this->answers($quiz->fresh('questions'), 6);
        $duplicate = $payload;
        $duplicate['answers'][1]['question_id'] = $duplicate['answers'][0]['question_id'];
        $foreign = $payload;
        $foreign['answers'][0]['question_id'] = $this->quiz('kelas-dan-objek')->questions[0]->id;
        foreach ([['answers' => array_slice($payload['answers'], 0, 5)], $duplicate, $foreign, $payload + ['score' => 100]] as $invalid) {
            $this->postJson($attempt['submit_url'], $invalid)->assertUnprocessable();
        }
        $this->assertDatabaseCount('quiz_answers', 0);
        $this->assertDatabaseHas('quiz_attempts', ['id' => $attempt['attempt_id'], 'score' => null, 'status' => 'in_progress']);
        $this->postJson($attempt['submit_url'], $payload)->assertOk()->assertJsonPath('result.score', 100);
    }

    public function test_code_only_packages_are_supported_and_chapter_essays_cannot_be_published(): void
    {
        $quiz = $this->quiz();
        $code = $this->drafts($quiz, 2)->firstWhere('question_type', 'code_fill');
        $this->publish($quiz, [$code->id])->assertRedirect();
        $this->actingAs(User::factory()->create());
        $this->postJson($this->start()['submit_url'], $this->answers($quiz->fresh('questions'), 1))->assertOk()
            ->assertJsonPath('result.score', 100)->assertJsonPath('result.total_questions', 1);
        $essay = app(QuestionBankService::class)->save([
            'quiz_id' => $quiz->id, 'question_type' => 'essay', 'question_text' => 'Uraian tidak untuk BAB',
            'options' => null, 'correct_answer' => null, 'display_order' => 3,
        ]);
        $this->publish($quiz, [$essay->id])->assertUnprocessable()->assertJsonValidationErrors('question_ids');
        $this->assertSame([$code->id], $quiz->fresh()->questions->pluck('id')->all());
    }

    public function test_limits_are_configurable_and_rounding_does_not_decide_passing(): void
    {
        $quiz = $this->quiz();
        config(['quiz.maximum_questions' => 6]);
        $drafts = $this->drafts($quiz, 7);
        $this->publish($quiz, $drafts->pluck('id')->all())->assertUnprocessable();
        $this->publish($quiz, $drafts->take(6)->pluck('id')->all())->assertRedirect();
        $this->actingAs(User::factory()->create());
        $attempt = $this->start();
        config(['quiz.maximum_questions' => 1]);
        $this->postJson($attempt['submit_url'], $this->answers($quiz->fresh('questions'), 5))->assertOk()
            ->assertJsonPath('result.passed', true);
        config(['quiz.passing_score' => 66.67]);
        $this->assertSame(66.67, ChapterQuizScoring::score(2, 3));
        $this->assertFalse(ChapterQuizScoring::passed(2, 3));
        $this->assertSame(3, ChapterQuizScoring::minimumCorrect(3));
    }
}
