<?php

// Disposable browser fixture/router. Never reads or migrates the application's database.
$root = dirname(__DIR__, 2);
$database = $root.'/storage/framework/testing/admin-browser.sqlite';
foreach ([
    'APP_ENV' => 'testing', 'APP_DEBUG' => 'true',
    'APP_KEY' => 'base64:'.base64_encode(str_repeat('b', 32)),
    'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $database, 'DB_URL' => '',
    'SESSION_DRIVER' => 'file', 'SESSION_COOKIE' => 'oopy_admin_browser_test',
    'CACHE_STORE' => 'array', 'APP_URL' => 'http://127.0.0.1:8035',
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
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (! is_dir(dirname($database))) {
    mkdir(dirname($database), 0777, true);
}
if (file_exists($database)) {
    fwrite(STDERR, "Browser fixture already exists; use its existing accounts.\n");
    exit(0);
}
touch($database);
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => Database\Seeders\OopyContentSeeder::class, '--force' => true]);
$admin = App\Models\User::create(['name' => 'Administrator Uji', 'email' => 'admin.browser@example.test', 'password' => 'password123']);
$admin->role = 'admin';
$admin->save();
$user = App\Models\User::create(['name' => 'Pelajar Uji', 'email' => 'student.browser@example.test', 'password' => 'password123']);
$service = app(App\Services\ChapterQuizService::class);
$quiz = $service->quiz('dasar-pemrograman-oop');
$attempt = $service->start($quiz, $user);
app(App\Services\QuizGradingService::class)->submit($quiz, $attempt, $user, $quiz->questions->map(fn ($question) => [
    'question_id' => $question->id,
    'answer' => $question->question_type === 'multiple_choice' ? (int) $question->correct_answer : $question->correct_answer,
])->all());
echo "Disposable browser fixture created at storage/framework/testing/admin-browser.sqlite.\n";
