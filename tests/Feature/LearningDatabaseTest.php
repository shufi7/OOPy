<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Exercise;
use App\Models\ExerciseSubmission;
use App\Models\Material;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserProgress;
use Carbon\CarbonInterface;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LearningDatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Never use migrate:fresh or the configured application database.
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.foreign_key_constraints' => true,
        ]);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->artisan('migrate', ['--database' => 'sqlite', '--force' => true])->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    private function seedContent(): void
    {
        $this->seed(DatabaseSeeder::class);
    }

    private function attemptFor(Quiz $quiz, ?User $user = null): QuizAttempt
    {
        $user ??= User::factory()->create();

        return QuizAttempt::create(['quiz_id' => $quiz->id, 'user_id' => $user->id]);
    }

    private function rejected(callable $operation, string $exception = QueryException::class): void
    {
        try {
            $operation();
        } catch (\Throwable $error) {
            $this->assertInstanceOf($exception, $error);

            return;
        }
        $this->fail('The invalid database operation was accepted.');
    }

    public function test_schema_has_ten_application_tables_and_keeps_framework_tables(): void
    {
        foreach (['users', 'chapters', 'materials', 'user_progress', 'exercises', 'exercise_submissions', 'quizzes', 'questions', 'quiz_attempts', 'quiz_answers'] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }
        foreach (['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens', 'migrations'] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }
        foreach (['quiz_questions', 'quiz_options', 'multiple_choice_questions', 'essay_questions', 'final_exams'] as $table) {
            $this->assertFalse(Schema::hasTable($table), $table);
        }
        $this->assertTrue(Schema::hasColumn('users', 'role'));
        $this->assertFalse(Schema::hasColumn('quiz_answers', 'option_id'));
    }

    public function test_seeding_is_idempotent_and_exactly_matches_php_content(): void
    {
        $this->seedContent();
        $ids = Question::orderBy('id')->pluck('id')->all();
        $material = Material::first();
        $material->update(['content' => '<p>Authored content must survive seeding.</p>']);
        $this->seedContent();
        $this->assertSame($ids, Question::orderBy('id')->pluck('id')->all());
        $this->assertSame('<p>Authored content must survive seeding.</p>', $material->fresh()->content);
        $this->assertSame(7, Chapter::count());
        $this->assertSame(6, Material::count());
        $this->assertSame(7, Exercise::count());
        $this->assertSame(7, Quiz::count());
        $this->assertSame(50, Question::count());
        $this->assertSame(0, User::count(), 'Content seeding must not create default accounts.');
        $this->assertSame(28, Question::where('question_type', 'multiple_choice')->count());
        $this->assertSame(17, Question::where('question_type', 'code_fill')->count());
        $this->assertSame(5, Question::where('question_type', 'essay')->count());

        $registry = require resource_path('materi/chapters.php');
        foreach ($registry as $slug => $metadata) {
            $content = require resource_path('materi/'.$metadata['content']);
            $chapter = Chapter::where('slug', $slug)->firstOrFail();
            $this->assertSame($metadata['judul'], $chapter->title);
            $quiz = $chapter->quizzes()->firstOrFail();
            $final = $slug === 'evaluasi-akhir';
            $this->assertSame($final ? 'final_exam' : 'chapter_quiz', $quiz->type);
            $this->assertSame($final ? number_format(config('evaluasi.pass_threshold'), 2, '.', '') : '80.00', $quiz->passing_score);
            $this->assertSame($final ? config('evaluasi.duration_seconds') : null, $quiz->duration_seconds);
            $source = $final ? $content['questions'] : $content['quiz'];
            $this->assertCount($final ? 20 : 5, $quiz->questions);
            foreach ($source as $index => $question) {
                $saved = $quiz->questions[$index];
                $this->assertSame($question['type'], $saved->question_type);
                $this->assertSame($question['question'], $saved->question_text);
                $this->assertSame($question['code'] ?? null, $saved->code_snippet);
                $this->assertSame($question['options'] ?? null, $saved->options);
                $answer = $question['type'] === 'multiple_choice' ? (string) $question['correct'] : ($question['answer'] ?? null);
                $this->assertSame($answer, $saved->correct_answer);
                $this->assertSame($question['explanation'] ?? null, $saved->explanation);
                $this->assertSame($index + 1, $saved->question_order);
                if ($saved->options !== null) {
                    $raw = DB::table('questions')->where('id', $saved->id)->value('options');
                    $this->assertSame($saved->options, json_decode($raw, true, flags: JSON_THROW_ON_ERROR));
                }
            }
            foreach ($content['sections'] ?? [] as $section) {
                foreach ($section['live_codes'] ?? [] as $sourceExercise) {
                    $exercise = Exercise::where('exercise_key', $sourceExercise['id'])->firstOrFail();
                    $this->assertSame($sourceExercise['files'][$sourceExercise['entry_file']], $exercise->starter_code);
                    $this->assertSame($chapter->id, $exercise->material->chapter_id);
                }
            }
        }
    }

    public function test_eloquent_relationships_and_multiple_attempts_work(): void
    {
        $this->seedContent();
        $user = User::factory()->create();
        $material = Material::firstOrFail();
        $progress = UserProgress::create(['user_id' => $user->id, 'material_id' => $material->id]);
        $this->assertSame('not_started', $progress->status);
        $progress->update(['status' => 'completed', 'completed_at' => now()]);
        $this->assertInstanceOf(CarbonInterface::class, $progress->completed_at);
        $this->assertTrue($progress->user->is($user));
        $this->assertTrue($progress->material->is($material));
        $this->assertCount(1, $user->progress);
        $this->assertCount(1, $material->userProgress);
        $this->assertTrue($material->chapter->materials->contains($material));
        $exercise = $material->exercises()->firstOrFail();
        $submission = ExerciseSubmission::create(['exercise_id' => $exercise->id, 'user_id' => $user->id, 'code' => 'print(1)', 'output' => '1', 'status' => 'passed', 'score' => 83.33]);
        $this->assertSame('83.33', $submission->score);
        $this->assertTrue($submission->exercise->is($exercise));
        $this->assertTrue($submission->user->is($user));
        $this->assertCount(1, $exercise->submissions);
        $this->assertCount(1, $user->exerciseSubmissions);
        $quiz = $material->chapter->quizzes()->firstOrFail();
        $one = $this->attemptFor($quiz, $user);
        $two = $this->attemptFor($quiz, $user);
        $this->assertNotSame($one->id, $two->id);
        $this->assertCount(2, $user->quizAttempts);
        $this->assertCount(2, $quiz->attempts);
        $this->assertTrue($one->user->is($user));
        $this->assertTrue($one->quiz->is($quiz));
        $question = $quiz->questions()->firstOrFail();
        $answer = QuizAnswer::create(['attempt_id' => $one->id, 'question_id' => $question->id, 'answer_text' => '0', 'is_correct' => false]);
        $this->assertSame(false, $answer->is_correct);
        $this->assertSame($quiz->id, $answer->quiz_id);
        $this->assertTrue($answer->question->quiz->is($quiz));
        $this->assertTrue($answer->attempt->is($one));
        $this->assertCount(1, $one->answers);
        $this->assertCount(1, $question->answers);
    }

    public function test_roles_default_to_user_and_require_explicit_trusted_assignment(): void
    {
        $user = User::factory()->create();
        $this->assertSame('user', $user->role);
        $user->fill(['role' => 'admin']);
        $this->assertSame('user', $user->role);
        $user->forceFill(['role' => 'admin'])->save();
        $this->assertSame('admin', $user->fresh()->role);
        $this->rejected(fn () => $user->forceFill(['role' => 'teacher'])->save(), ValidationException::class);
        $this->rejected(fn () => DB::table('users')->where('id', $user->id)->update(['role' => 'teacher']));
    }

    public function test_unique_and_foreign_keys_are_database_constraints(): void
    {
        $this->seedContent();
        $user = User::factory()->create();
        $material = Material::firstOrFail();
        UserProgress::create(['user_id' => $user->id, 'material_id' => $material->id]);
        $this->rejected(fn () => UserProgress::create(['user_id' => $user->id, 'material_id' => $material->id]));
        $this->rejected(fn () => DB::table('user_progress')->insert(['user_id' => 999999, 'material_id' => $material->id, 'status' => 'in_progress']));
        $this->rejected(fn () => $material->delete());
        $quiz = Quiz::firstOrFail();
        $attempt = $this->attemptFor($quiz, $user);
        $question = $quiz->questions()->firstOrFail();
        QuizAnswer::create(['attempt_id' => $attempt->id, 'question_id' => $question->id, 'answer_text' => '0']);
        $this->rejected(fn () => QuizAnswer::create(['attempt_id' => $attempt->id, 'question_id' => $question->id, 'answer_text' => '1']));
        $this->rejected(fn () => $question->delete());
        $this->rejected(fn () => $user->delete());
        $attempt->delete();
        $this->assertSame(0, QuizAnswer::count(), 'Deleting a selected attempt cascades only its answer rows.');
    }

    public function test_question_shape_validation_rejects_invalid_authoring(): void
    {
        $this->seedContent();
        $quiz = Quiz::firstOrFail();
        $base = ['quiz_id' => $quiz->id, 'question_order' => 99, 'question_text' => 'Shape test'];
        foreach ([
            ['question_type' => 'multiple_choice', 'options' => ['A', 'B'], 'correct_answer' => '2'],
            ['question_type' => 'multiple_choice', 'options' => ['a' => 'A', 'b' => 'B'], 'correct_answer' => '0'],
            ['question_type' => 'multiple_choice', 'options' => ['A', ''], 'correct_answer' => '0'],
            ['question_type' => 'code_fill', 'options' => [], 'code_snippet' => '___', 'correct_answer' => 'return'],
            ['question_type' => 'code_fill', 'correct_answer' => 'return'],
            ['question_type' => 'essay', 'correct_answer' => 'false'],
            ['question_type' => 'essay', 'options' => []],
        ] as $invalid) {
            $this->rejected(fn () => Question::create($base + $invalid), ValidationException::class);
        }
        $essay = Question::create($base + ['question_type' => 'essay']);
        $this->assertNull($essay->options);
        $this->assertNull($essay->correct_answer);
    }

    public function test_database_checks_reject_raw_invalid_questions_and_score_ranges(): void
    {
        $this->seedContent();
        $quiz = Quiz::firstOrFail();
        $base = ['quiz_id' => $quiz->id, 'question_order' => 99, 'question_text' => 'Raw shape test'];
        foreach ([
            ['question_type' => 'multiple_choice', 'options' => json_encode(['A', 'B']), 'correct_answer' => '2'],
            ['question_type' => 'multiple_choice', 'options' => json_encode(['a' => 'A']), 'correct_answer' => '0'],
            ['question_type' => 'multiple_choice', 'options' => json_encode(['A', 'B']), 'correct_answer' => '01'],
            ['question_type' => 'code_fill', 'code_snippet' => '___', 'correct_answer' => null],
            ['question_type' => 'essay', 'correct_answer' => '0'],
        ] as $invalid) {
            $this->rejected(fn () => DB::table('questions')->insert($base + $invalid));
        }
        $this->rejected(fn () => DB::table('quizzes')->where('id', $quiz->id)->update(['passing_score' => 101]));
        $this->rejected(fn () => DB::table('quizzes')->where('id', $quiz->id)->update(['duration_seconds' => 0]));
        $this->rejected(fn () => DB::table('chapters')->where('id', $quiz->chapter_id)->update(['chapter_order' => 0]));
    }

    public function test_cross_quiz_answers_and_automatic_essay_correctness_are_rejected(): void
    {
        $this->seedContent();
        $final = Quiz::where('type', 'final_exam')->firstOrFail();
        $attempt = $this->attemptFor($final);
        $foreign = Question::where('quiz_id', '!=', $final->id)->firstOrFail();
        $this->rejected(fn () => QuizAnswer::create(['attempt_id' => $attempt->id, 'question_id' => $foreign->id, 'answer_text' => '0']), ValidationException::class);
        $this->rejected(fn () => DB::table('quiz_answers')->insert(['attempt_id' => $attempt->id, 'question_id' => $foreign->id, 'quiz_id' => $final->id, 'answer_text' => '0']));
        $essay = $final->questions()->where('question_type', 'essay')->firstOrFail();
        $this->rejected(fn () => QuizAnswer::create(['attempt_id' => $attempt->id, 'question_id' => $essay->id, 'answer_text' => 'Penjelasan', 'is_correct' => false]), ValidationException::class);
        $this->rejected(fn () => DB::table('quiz_answers')->insert(['attempt_id' => $attempt->id, 'question_id' => $essay->id, 'quiz_id' => $final->id, 'answer_text' => 'Penjelasan', 'is_correct' => false]));
        $answer = QuizAnswer::create(['attempt_id' => $attempt->id, 'question_id' => $essay->id, 'answer_text' => 'Penjelasan']);
        $this->assertNull($answer->fresh()->is_correct);
        $this->seedContent();
        $this->assertTrue($answer->fresh()->question->is($essay));
        $this->rejected(fn () => $essay->update(['question_type' => 'code_fill', 'code_snippet' => '___', 'correct_answer' => 'return']), ValidationException::class);
    }

    public function test_empty_schema_rollback_and_reapply_preserve_existing_users(): void
    {
        $user = User::factory()->create();
        $password = $user->getRawOriginal('password');
        $this->artisan('migrate:rollback', ['--database' => 'sqlite', '--step' => 11, '--force' => true])->assertExitCode(0);
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertFalse(Schema::hasColumn('users', 'role'));
        $this->assertFalse(Schema::hasTable('chapters'));
        $this->assertSame($password, DB::table('users')->where('id', $user->id)->value('password'));
        $this->artisan('migrate', ['--database' => 'sqlite', '--force' => true])->assertExitCode(0);
        $this->assertSame('user', $user->fresh()->role);
    }

    public function test_rollback_refuses_to_remove_learning_records_before_export(): void
    {
        $this->seedContent();
        $attempt = $this->attemptFor(Quiz::firstOrFail());
        $migration = require database_path('migrations/2026_10_09_000011_add_learning_integrity_constraints.php');
        $this->rejected(fn () => $migration->down(), \RuntimeException::class);
        $this->assertTrue($attempt->fresh()->exists);
        $this->assertSame(50, Question::count());
    }
}
