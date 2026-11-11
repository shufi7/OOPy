<?php

use App\Models\Quiz;
use App\Models\User;
use App\Services\Admin\LearningReportService;
use App\Services\Admin\QuestionBankService;
use App\Services\ChapterQuizService;
use App\Services\DashboardProgressService;
use App\Services\QuizGradingService;
use Database\Seeders\OopyContentSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$connection = config('database.connections.mysql');
if (! in_array($connection['host'] ?? null, ['127.0.0.1', 'localhost'], true) || ! empty($connection['url'])) {
    throw new RuntimeException('Only the local MariaDB server is supported by this isolated test.');
}
$control = DB::connection('mysql');
$version = $control->selectOne('SELECT VERSION() AS version')->version;
$database = 'oopy_dynamic_quiz_test_'.bin2hex(random_bytes(16));
$created = false;
$checks = [];
$verify = function (bool $condition, string $message) use (&$checks): void {
    if (! $condition) {
        throw new RuntimeException($message);
    }
    $checks[] = $message;
};
try {
    $control->statement('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    config(['database.connections.dynamic_quiz_qa' => array_replace($connection, ['database' => $database, 'url' => null])]);
    DB::setDefaultConnection('dynamic_quiz_qa');
    $verify(DB::connection()->getDatabaseName() === $database, 'Test database isolation');
    Artisan::call('migrate', ['--database' => 'dynamic_quiz_qa', '--force' => true]);
    Artisan::call('db:seed', ['--class' => OopyContentSeeder::class, '--force' => true]);
    $bank = app(QuestionBankService::class);
    $quizzes = app(ChapterQuizService::class);
    $grading = app(QuizGradingService::class);
    $quiz = Quiz::where('type', 'chapter_quiz')->orderBy('id')->firstOrFail();
    $student = User::create(['name' => 'Dynamic Quiz QA', 'email' => 'dynamic-quiz@example.test', 'password' => 'isolated-password']);
    $payload = fn ($questions, $correct) => $questions->values()->map(fn ($question, $index) => [
        'question_id' => $question->id,
        'answer' => $question->question_type === 'multiple_choice'
            ? ($index < $correct ? (int) $question->correct_answer : ((int) $question->correct_answer + 1) % count($question->options))
            : ($index < $correct ? $question->correct_answer : 'WRONG'),
    ])->all();
    $old = $quizzes->start($quiz, $student);
    $oldAnswers = $payload($quizzes->attemptQuestions($quiz, $old), 4);
    $oldVersion = $bank->packageVersion($quiz->questions()->get());
    $drafts = collect(range(1, 10))->map(fn ($number) => $bank->save([
        'quiz_id' => $quiz->id, 'question_type' => 'multiple_choice', 'question_text' => 'QA '.$number,
        'options' => ['Benar', 'Salah'], 'correct_answer' => '0', 'display_order' => $number,
    ]));
    $verify($quiz->questions()->count() === 5, 'Drafts leave the active five-question package intact');
    $candidate = $drafts->last();
    $draftVersion = $bank->questionVersion($candidate);
    $bank->save(['quiz_id' => $quiz->id, 'question_text' => 'First Admin edit'], $candidate, $draftVersion);
    try {
        $bank->save(['quiz_id' => $quiz->id, 'question_text' => 'Stale edit'], $candidate, $draftVersion);
        throw new RuntimeException('Stale draft edit was accepted.');
    } catch (ValidationException) {
        $verify($candidate->fresh()->question_text === 'First Admin edit', 'Stale draft edit cannot overwrite the first Admin changes');
    }
    $bank->publish($quiz, $drafts->pluck('id')->all(), $oldVersion);
    $verify($quizzes->attemptQuestions($quiz, $old)->count() === 5, 'Open attempt retains five pinned questions after publishing ten');
    $result = $grading->submit($quiz, $old, $student, $oldAnswers);
    $verify($result['result']['score'] === 80.0 && $result['result']['passed'], 'Old attempt grades 4/5 as 80 and unlocks the next chapter');
    $verify($grading->submit($quiz, $old, $student, $oldAnswers) === $result && $old->answers()->count() === 5, 'Repeated submission keeps the stored score and five answers');
    $ten = $quizzes->start($quiz, $student);
    $verify($quizzes->attemptQuestions($quiz, $ten)->count() === 10, 'New attempt receives ten questions');
    $tenResult = $grading->submit($quiz, $ten, $student, $payload($quizzes->attemptQuestions($quiz, $ten), 8));
    $verify($tenResult['result']['score'] === 80.0 && $tenResult['result']['passed'], 'Ten-question package grades 8/10 as 80');
    try {
        $bank->publish($quiz, $drafts->take(6)->pluck('id')->all(), $oldVersion);
        throw new RuntimeException('Stale publication was accepted.');
    } catch (ValidationException) {
        $verify($quiz->questions()->count() === 10, 'Stale Admin publication is rejected without changing ten active questions');
    }
    $bank->publish($quiz, $drafts->take(6)->pluck('id')->all(), $bank->packageVersion($quiz->questions()->get()));
    $six = $quizzes->start($quiz, $student);
    $sixResult = $grading->submit($quiz, $six, $student, $payload($quizzes->attemptQuestions($quiz, $six), 5));
    $verify($sixResult['result']['score'] === 83.33 && $sixResult['result']['passed'], 'Six-question package grades 5/6 as 83.33');
    $verify((float) $old->fresh()->score === 80.0 && $old->fresh()->completed_at >= $old->fresh()->started_at, 'Historical score and attempt timestamps remain intact');
    $dashboard = app(DashboardProgressService::class)->forUser($student);
    $verify($dashboard['chapters'][0]['best_score_label'] === '83,33' && $dashboard['completed_chapters'] === 1, 'Dashboard best score and chapter progress remain consistent');
    $reports = app(LearningReportService::class);
    $record = $reports->users(['q' => $student->email, 'chapter' => 1, 'status' => 'passed', 'sort' => 'best'])->sole();
    $verify($reports::score($record->chapter_1) === '83,33', 'Admin search, status filter, sort and two-decimal score formatting');
    $final = Quiz::where('type', 'final_exam')->sole();
    $verify($final->questions()->count() === 20 && $final->questions()->where('question_type', 'essay')->count() === 5, 'Final Exam twenty-question and essay contract is unchanged');
    $status = 'PASS';
} finally {
    DB::disconnect('dynamic_quiz_qa');
    if ($created && preg_match('/^oopy_dynamic_quiz_test_[a-f0-9]{32}$/D', $database)) {
        $control->statement('DROP DATABASE `'.$database.'`');
    }
}
echo json_encode(['status' => $status, 'version' => $version, 'checks' => $checks, 'temporary_database_removed' => true], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
