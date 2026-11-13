<?php

use App\Models\Exercise;
use App\Models\User;
use App\Services\ChapterQuizService;
use App\Services\QuizGradingService;
use Database\Seeders\CodingExerciseSeeder;
use Database\Seeders\OopyContentSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

// Disposable browser fixture/router. Never reads or migrates the application's database.
$root = dirname(__DIR__, 2);
$databaseName = getenv('OOPY_ADMIN_TEST_DATABASE') ?: 'admin-browser.sqlite';
if ($databaseName !== 'admin-browser.sqlite' && ! preg_match('/^admin-coding-[a-f0-9]{32}\.sqlite$/D', $databaseName)) {
    throw new RuntimeException('Only an isolated Admin browser database is accepted.');
}
$database = $root.'/storage/framework/testing/'.$databaseName;
foreach ([
    'APP_ENV' => 'testing', 'APP_DEBUG' => 'true',
    'APP_KEY' => 'base64:'.base64_encode(str_repeat('b', 32)),
    'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $database, 'DB_URL' => '',
    'SESSION_DRIVER' => 'file', 'SESSION_COOKIE' => 'oopy_admin_browser_test',
    'CACHE_STORE' => 'array', 'APP_URL' => getenv('OOPY_BASE_URL') ?: 'http://127.0.0.1:8035',
] as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $_SERVER[$name] = $value;
}

if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $asset = realpath($root.'/public'.$path);
    if ($path !== '/' && $asset && str_starts_with($asset, realpath($root.'/public').DIRECTORY_SEPARATOR) && is_file($asset)) {
        return false;
    }
    require $root.'/public/index.php';

    return;
}

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! is_dir(dirname($database))) {
    mkdir(dirname($database), 0777, true);
}
if (file_exists($database)) {
    fwrite(STDERR, "Browser fixture already exists; use its existing accounts.\n");
    exit(0);
}
touch($database);
Artisan::call('migrate', ['--force' => true]);
Artisan::call('db:seed', ['--class' => OopyContentSeeder::class, '--force' => true]);
Artisan::call('db:seed', ['--class' => CodingExerciseSeeder::class, '--force' => true]);
$admin = User::create(['name' => 'Administrator Uji', 'email' => 'admin.browser@example.test', 'password' => 'password123']);
$admin->role = 'admin';
$admin->save();
$user = User::create(['name' => 'Pelajar Uji', 'email' => 'student.browser@example.test', 'password' => 'password123']);
$service = app(ChapterQuizService::class);
$quiz = $service->quiz('dasar-pemrograman-oop');
$attempt = $service->start($quiz, $user);
app(QuizGradingService::class)->submit($quiz, $attempt, $user, $quiz->questions->map(fn ($question) => [
    'question_id' => $question->id,
    'answer' => $question->question_type === 'multiple_choice' ? (int) $question->correct_answer : $question->correct_answer,
])->all());
$first = Exercise::where('exercise_key', 'coding-latihan-1')->sole();
$second = Exercise::where('exercise_key', 'coding-latihan-2-multifile-v1')->sole();
foreach (range(1, 23) as $index) {
    $user->exerciseSubmissions()->create([
        'exercise_id' => $index === 23 ? $second->id : $first->id,
        'code' => 'private-admin-coding-fixture-source', 'output' => 'private-admin-coding-fixture-output',
        'status' => $index === 1 ? 'passed' : 'failed', 'score' => $index === 1 ? 90 : 70,
        'submitted_at' => '2026-11-05 00:'.str_pad((string) $index, 2, '0', STR_PAD_LEFT).':00',
    ]);
}
$zero = User::create(['name' => '=SUM(1;2)', 'email' => 'zero.browser@example.test', 'password' => 'password123']);
$zero->exerciseSubmissions()->create([
    'exercise_id' => $first->id, 'code' => 'private-admin-coding-fixture-source', 'status' => 'error', 'score' => 0,
    'submitted_at' => '2026-11-05 00:24:00',
]);
User::create(['name' => 'Belum Coding', 'email' => 'empty.browser@example.test', 'password' => 'password123']);

echo 'Disposable Admin browser fixture created: '.$databaseName."\n";
