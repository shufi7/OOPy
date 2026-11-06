<?php

namespace Tests\Feature\Admin;

use App\Models\Exercise;
use App\Models\FinalExamAttempt;
use App\Models\Question;
use App\Models\User;
use App\Services\Admin\QuestionBankService;
use App\Services\ChapterQuizService;
use App\Services\QuizGradingService;
use Database\Seeders\CodingExerciseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Quiz\QuizTestCase;

class DeletionTest extends QuizTestCase
{
    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin;
    }

    private function draft(): Question
    {
        $question = $this->quiz()->questions->first();
        $data = $question->only(['quiz_id', 'question_type', 'question_text', 'code_snippet', 'options', 'correct_answer']);

        return app(QuestionBankService::class)->save($data, $question);
    }

    public function test_guests_and_students_cannot_access_deletion_routes(): void
    {
        $user = User::factory()->create();
        $question = $this->draft();
        foreach ([route('admin.users.confirm-delete', $user), route('admin.questions.confirm-delete', $question)] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        $this->delete(route('admin.users.destroy', $user))->assertRedirect('/login');
        $this->delete(route('admin.questions.destroy', $question))->assertRedirect('/login');
        $this->actingAs($user);
        $this->get(route('admin.users.confirm-delete', $user))->assertForbidden();
        $this->get(route('admin.questions.confirm-delete', $question))->assertForbidden();
        $this->delete(route('admin.users.destroy', $user), ['email_confirmation' => $user->email])->assertForbidden();
        $this->delete(route('admin.questions.destroy', $question), ['confirmation' => '1'])->assertForbidden();
        $this->assertModelExists($user);
        $this->assertModelExists($question);
    }

    public function test_admin_cannot_delete_self_or_other_administrators(): void
    {
        $admin = $this->admin();
        $other = $this->admin();
        $this->actingAs($admin);
        foreach ([$admin, $other] as $target) {
            $this->get(route('admin.users.confirm-delete', $target))->assertForbidden();
            $this->delete(route('admin.users.destroy', $target), ['email_confirmation' => $target->email])->assertForbidden();
            $this->assertModelExists($target);
        }
    }

    public function test_deleting_user_requires_the_target_email_and_confirmation_get_is_read_only(): void
    {
        $user = User::factory()->create();
        $this->actingAs($this->admin());
        $this->get(route('admin.users.index'))->assertSee(route('admin.users.confirm-delete', $user));
        $this->get(route('admin.users.confirm-delete', $user))->assertOk()->assertSee($user->email)
            ->assertSee('Hapus pengguna permanen');
        $this->assertModelExists($user);
        foreach ([[], ['email_confirmation' => 'someone-else@example.test']] as $data) {
            $this->delete(route('admin.users.destroy', $user), $data)->assertSessionHasErrors('email_confirmation');
            $this->assertModelExists($user);
        }
    }

    public function test_user_deletion_removes_only_their_related_records_and_revokes_sessions(): void
    {
        $this->seed(CodingExerciseSeeder::class);
        $user = User::factory()->create();
        $other = User::factory()->create();
        $quiz = $this->quiz();
        $chapter = app(ChapterQuizService::class);
        $grader = app(QuizGradingService::class);
        $attemptIds = [];
        foreach ([$user, $other] as $owner) {
            $attempt = $chapter->start($quiz, $owner);
            $attemptIds[$owner->id] = $attempt->id;
            $grader->submit($quiz, $attempt, $owner, $this->answers($quiz)['answers']);
            $owner->exerciseSubmissions()->create(['exercise_id' => Exercise::firstOrFail()->id,
                'code' => 'print(1)', 'status' => 'passed', 'score' => 100]);
            $this->actingAs($owner)->postJson(route('evaluasi.attempts.start'))->assertOk();
            DB::table('sessions')->insert(['id' => 'session-'.$owner->id, 'user_id' => $owner->id,
                'payload' => 'fixture', 'last_activity' => time()]);
            DB::table('password_reset_tokens')->insert(['email' => $owner->email, 'token' => 'fixture', 'created_at' => now()]);
        }
        $otherAttempt = $other->quizAttempts()->sole();
        $this->assertDatabaseHas('user_progress', ['user_id' => $user->id]);
        $this->actingAs($this->admin())->delete(route('admin.users.destroy', $user), ['email_confirmation' => $user->email])
            ->assertRedirect(route('admin.users.index'))->assertSessionHas('success');
        $this->assertModelMissing($user);
        foreach (['user_progress', 'quiz_attempts', 'exercise_submissions', 'final_exam_attempts', 'sessions'] as $table) {
            $this->assertDatabaseMissing($table, ['user_id' => $user->id]);
            $this->assertDatabaseHas($table, ['user_id' => $other->id]);
        }
        $this->assertDatabaseMissing('quiz_answers', ['attempt_id' => $attemptIds[$user->id]]);
        $this->assertDatabaseMissing('quiz_attempt_questions', ['attempt_id' => $attemptIds[$user->id]]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertSame(5, $otherAttempt->answers()->count());
        $this->assertSame(5, DB::table('quiz_attempt_questions')->where('attempt_id', $otherAttempt->id)->count());
        $this->assertSame(1, FinalExamAttempt::where('user_id', $other->id)->count());
        $this->assertDatabaseCount('questions', 50);
    }

    public function test_unused_draft_can_be_deleted_without_changing_published_quiz(): void
    {
        $draft = $this->draft();
        $activeIds = $this->quiz()->questions->pluck('id')->all();
        $this->actingAs($this->admin());
        $this->get(route('admin.questions.index'))->assertSee(route('admin.questions.confirm-delete', $draft));
        $this->get(route('admin.questions.confirm-delete', $draft))->assertOk()->assertSee('Hapus soal permanen');
        $this->assertModelExists($draft);
        $this->delete(route('admin.questions.destroy', $draft))->assertSessionHasErrors('confirmation');
        $this->delete(route('admin.questions.destroy', $draft), ['confirmation' => '1'])
            ->assertRedirect(route('admin.questions.index', ['quiz_id' => $draft->quiz_id]));
        $this->assertModelMissing($draft);
        $this->assertSame($activeIds, $this->quiz()->questions->pluck('id')->all());
    }

    public function test_active_questions_and_archived_questions_used_in_open_attempts_cannot_be_deleted(): void
    {
        $quiz = $this->quiz();
        $question = $quiz->questions->first();
        $this->actingAs($this->admin());
        $this->delete(route('admin.questions.destroy', $question), ['confirmation' => '1'])->assertSessionHasErrors('deletion');
        $this->get(route('admin.questions.confirm-delete', $question))->assertOk()->assertSee('Soal aktif tidak dapat dihapus.')
            ->assertDontSee('Hapus soal permanen');
        $attempt = app(ChapterQuizService::class)->start($quiz, User::factory()->create());
        $question->update(['publication_status' => 'archived']);
        $this->delete(route('admin.questions.destroy', $question), ['confirmation' => '1'])->assertSessionHasErrors('deletion');
        $this->assertModelExists($question);
        $this->assertSame(5, DB::table('quiz_attempt_questions')->where('attempt_id', $attempt->id)->count());
    }

    public function test_revision_source_is_protected_until_unused_revision_is_deleted(): void
    {
        $source = $this->draft();
        $revision = $source->replicate();
        $revision->question_order++;
        $revision->revision_of_id = $source->id;
        $revision->save();
        $this->actingAs($this->admin());
        $this->delete(route('admin.questions.destroy', $source), ['confirmation' => '1'])->assertSessionHasErrors('deletion');
        $this->delete(route('admin.questions.destroy', $revision), ['confirmation' => '1'])->assertRedirect();
        $this->delete(route('admin.questions.destroy', $source), ['confirmation' => '1'])->assertRedirect();
        $this->assertModelMissing($source);
    }

    public function test_delete_requests_require_csrf_tokens(): void
    {
        $user = User::factory()->create();
        $question = $this->draft();
        $this->actingAs($this->admin());
        $this->app['env'] = 'local';
        $this->delete(route('admin.users.destroy', $user), ['email_confirmation' => $user->email])->assertStatus(419);
        $this->delete(route('admin.questions.destroy', $question), ['confirmation' => '1'])->assertStatus(419);
        $this->assertModelExists($user);
        $this->assertModelExists($question);
    }

    public function test_database_failure_rolls_back_user_and_related_record_deletion(): void
    {
        $user = User::factory()->create();
        $attempt = app(ChapterQuizService::class)->start($this->quiz(), $user);
        DB::unprepared("CREATE TRIGGER prevent_fixture_user_deletion BEFORE DELETE ON users BEGIN SELECT RAISE(ABORT, 'fixture deletion failure'); END");
        $this->actingAs($this->admin())->withoutExceptionHandling();
        try {
            $this->delete(route('admin.users.destroy', $user), ['email_confirmation' => $user->email]);
            $this->fail('The fixture trigger should reject user deletion.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('fixture deletion failure', $exception->getMessage());
        }
        $this->assertModelExists($user);
        $this->assertModelExists($attempt);
        $this->assertSame(5, DB::table('quiz_attempt_questions')->where('attempt_id', $attempt->id)->count());
    }
}
