<?php

namespace Tests\Feature\Admin;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use App\Services\Admin\QuestionBankService;
use App\Services\ChapterQuizService;
use App\Services\QuizGradingService;
use Database\Seeders\OopyContentSeeder;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Quiz\QuizTestCase;

class QuestionBankTest extends QuizTestCase
{
    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin;
    }

    private function data(Question $question): array
    {
        return [
            'quiz_id' => $question->quiz_id,
            'question_type' => $question->question_type,
            'question_text' => $question->question_text,
            'code_snippet' => $question->code_snippet,
            'options' => $question->options,
            'correct_answer' => $question->correct_answer,
            'explanation' => $question->explanation,
            'display_order' => $question->display_order ?? $question->question_order,
            'essay_rubric' => $question->essay_rubric,
        ];
    }

    public function test_all_bank_endpoints_require_admin_role(): void
    {
        $question = $this->quiz()->questions->first();
        $this->get('/admin/questions')->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        $this->get('/admin/questions')->assertForbidden();
        $this->get('/admin/questions/create')->assertForbidden();
        $this->get('/admin/questions/'.$question->id)->assertForbidden();
        $this->post('/admin/questions', [])->assertForbidden();
        $this->put('/admin/questions/'.$question->id, [])->assertForbidden();
        $this->patch('/admin/questions/'.$question->id.'/archive')->assertForbidden();
        $this->post('/admin/questions/quizzes/'.$question->quiz_id.'/publish', [])->assertForbidden();
    }

    public function test_question_form_validation_enforces_zero_based_keys_code_and_essay_category(): void
    {
        $this->actingAs($this->admin());
        $data = $this->data($this->quiz()->questions->first());
        $data['options_text'] = "Pilihan A\nPilihan B";
        $data['correct_answer'] = '2';
        $this->post('/admin/questions', $data)->assertSessionHasErrors('correct_answer');
        $data['question_type'] = 'code_fill';
        $data['code_snippet'] = '';
        $this->post('/admin/questions', $data)->assertSessionHasErrors('code_snippet');
        $data['question_type'] = 'essay';
        $this->post('/admin/questions', $data)->assertSessionHasErrors('question_type');
        $data['question_type'] = 'code_fill';
        $data['code_snippet'] = "if True:\n    ___";
        $data['correct_answer'] = ' return ';
        $this->post('/admin/questions', $data)->assertSessionHasErrors('correct_answer');
        $data['correct_answer'] = str_repeat('x', config('quiz.maximum_answer_length') + 1);
        $this->post('/admin/questions', $data)->assertSessionHasErrors('correct_answer');
        $this->assertDatabaseCount('questions', 50);
    }

    public function test_draft_creation_does_not_change_active_quiz_and_incomplete_publication_is_rejected(): void
    {
        $this->actingAs($this->admin());
        $quiz = $this->quiz();
        $data = $this->data($quiz->questions->first());
        $data['question_text'] = 'Pertanyaan tambahan';
        $data['options_text'] = implode("\n", $data['options']);
        $this->post('/admin/questions', $data)->assertRedirect();
        $draft = Question::where('publication_status', 'draft')->firstOrFail();
        $this->assertSame('Pertanyaan tambahan', $draft->question_text);
        $this->assertCount(5, $quiz->fresh()->questions);
        $this->post('/admin/questions/quizzes/'.$quiz->id.'/publish', ['question_ids' => [$draft->id]])
            ->assertSessionHasErrors('question_ids');
        $this->get('/materi/'.$quiz->chapter->slug)->assertOk();
        $this->assertCount(5, $quiz->fresh()->questions);
    }

    public function test_malformed_question_payloads_return_validation_errors_instead_of_server_errors(): void
    {
        $this->actingAs($this->admin());
        $base = $this->data($this->quiz()->questions->first());
        $base['options_text'] = "A\nB";
        foreach ([
            [['question_type' => 'essay', 'quiz_id' => [1]], 'quiz_id'],
            [['question_type' => 'essay', 'quiz_id' => ['id' => 1]], 'quiz_id'],
            [['question_type' => 'essay', 'quiz_id' => '1 OR 1=1'], 'quiz_id'],
            [['question_type' => 'essay', 'quiz_id' => null], 'quiz_id'],
            [['correct_answer' => []], 'correct_answer'],
            [['options_text' => ['A', 'B']], 'options'],
            [['question_type' => ['essay']], 'question_type'],
        ] as [$invalid, $field]) {
            $this->postJson('/admin/questions', array_replace($base, $invalid))
                ->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $this->assertDatabaseCount('questions', 50);
    }

    public function test_published_revision_preserves_open_attempt_keys_history_and_reseeded_content(): void
    {
        $quiz = $this->quiz();
        $user = User::factory()->create();
        $chapterQuizzes = app(ChapterQuizService::class);
        $attempt = $chapterQuizzes->start($quiz, $user);
        $original = $quiz->questions->first();
        $originalText = $original->question_text;
        $data = $this->data($original);
        $data['question_text'] = 'Soal versi revisi Admin';
        $data['correct_answer'] = (string) (((int) $original->correct_answer + 1) % count($original->options));
        $draft = app(QuestionBankService::class)->save($data, $original);
        $this->assertSame($originalText, $original->fresh()->question_text);
        $selected = $quiz->questions->pluck('id')->map(fn ($id) => $id === $original->id ? $draft->id : $id)->all();
        app(QuestionBankService::class)->publish($quiz, $selected);
        $this->assertSame('archived', $original->fresh()->publication_status);
        $this->assertTrue($quiz->fresh()->questions->contains('id', $draft->id));
        $this->assertSame($original->id, $chapterQuizzes->publicQuestions($quiz->fresh(), $user)[0]['question_id']);
        $answers = $this->answers($quiz)['answers'];
        $result = app(QuizGradingService::class)->submit($quiz->fresh(), $attempt, $user, $answers);
        $this->assertSame(100.0, $result['result']['score']);
        $this->seed(OopyContentSeeder::class);
        $this->assertSame('Soal versi revisi Admin', $draft->fresh()->question_text);
        $this->assertSame($originalText, $original->fresh()->question_text);
        $this->assertTrue($quiz->fresh()->questions->contains('id', $draft->id));
        $this->assertDatabaseHas('quiz_answers', ['attempt_id' => $attempt->id, 'question_id' => $original->id, 'is_correct' => true]);
        $this->assertSame($draft->id, $chapterQuizzes->publicQuestions($quiz->fresh())[0]['question_id']);
        $this->get('/materi/'.$quiz->chapter->slug)->assertOk()->assertSee('Soal versi revisi Admin');
    }

    public function test_question_model_still_rejects_mutating_question_in_open_attempt(): void
    {
        $quiz = $this->quiz();
        app(ChapterQuizService::class)->start($quiz, User::factory()->create());
        $this->expectException(ValidationException::class);
        $quiz->questions->first()->update(['correct_answer' => '0', 'question_text' => 'Unsafe mutation']);
    }

    public function test_active_question_cannot_be_archived_individually_and_preview_escapes_markup(): void
    {
        $this->actingAs($this->admin());
        $question = $this->quiz()->questions->first();
        $this->patch('/admin/questions/'.$question->id.'/archive')->assertSessionHasErrors('publication');
        $this->assertSame('published', $question->fresh()->publication_status);
        $data = $this->data($question);
        $data['question_text'] = '<script>alert("unsafe")</script>';
        $draft = app(QuestionBankService::class)->save($data);
        $this->get('/admin/questions/'.$draft->id)->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert', false);
        $this->get('/admin/questions')->assertOk();
        $this->get('/admin/questions/'.$draft->id.'/edit')->assertOk();
    }

    public function test_final_bank_requires_twenty_questions_and_supports_manual_essay_rubric(): void
    {
        $quiz = Quiz::with('questions')->where('type', 'final_exam')->firstOrFail();
        $essay = $quiz->questions->firstWhere('question_type', 'essay');
        $data = $this->data($essay);
        $data['essay_rubric'] = '0: tidak menjawab; 1–4: konsep sebagian; 5: konsep dan contoh tepat.';
        $draft = app(QuestionBankService::class)->save($data, $essay);
        $selected = $quiz->questions->pluck('id')->map(fn ($id) => $id === $essay->id ? $draft->id : $id)->all();
        app(QuestionBankService::class)->publish($quiz, $selected);
        $this->assertCount(20, $quiz->fresh()->questions);
        $this->assertSame(5, $quiz->fresh()->questions->where('question_type', 'essay')->count());
        $this->assertNull($draft->fresh()->correct_answer);
        $this->assertSame($data['essay_rubric'], $draft->fresh()->essay_rubric);
    }
}
