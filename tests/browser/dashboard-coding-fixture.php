<?php

use App\Models\Exercise;
use App\Models\User;
use Database\Seeders\CodingExerciseSeeder;
use Database\Seeders\OopyContentSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';

$database = getenv('DB_DATABASE');
if (getenv('DB_CONNECTION') !== 'sqlite' || ! is_string($database)
    || ! preg_match('/^coding-dashboard-[a-f0-9]{32}\.sqlite$/D', basename($database))) {
    throw new RuntimeException('Use a new disposable coding-dashboard SQLite database.');
}
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'sqlite' || DB::connection()->getDatabaseName() !== $database) {
    throw new RuntimeException('Database isolation check failed.');
}

if (($argv[1] ?? '') === 'init') {
    if (filesize($database) !== 0) {
        throw new RuntimeException('Only initialize a new, empty test database.');
    }
    Artisan::call('migrate', ['--force' => true]);
    Artisan::call('db:seed', ['--class' => OopyContentSeeder::class, '--force' => true]);
    Artisan::call('db:seed', ['--class' => CodingExerciseSeeder::class, '--force' => true]);
    echo "Disposable Coding dashboard database initialized.\n";
    exit(0);
}

$email = $argv[2] ?? '';
if (! str_ends_with($email, '@example.test')) {
    throw new RuntimeException('Only test accounts are accepted.');
}
$user = User::where('email', $email)->sole();
$cases = match ($argv[1] ?? '') {
    'failed' => [[1, 'failed', 0, 1]],
    'first-passed' => [[1, 'passed', 90, 2], [1, 'failed', 70, 3]],
    'both-passed' => [[2, 'passed', 100, 4], [2, 'failed', 10, 5], [2, 'error', 0, 6]],
    default => throw new RuntimeException('Unknown fixture scenario.'),
};
foreach ($cases as [$number, $status, $score, $minute]) {
    $user->exerciseSubmissions()->create([
        'exercise_id' => Exercise::where('exercise_key', 'coding-latihan-'.$number)->sole()->id,
        'code' => 'private-browser-fixture-source',
        'output' => 'private-browser-fixture-output',
        'status' => $status,
        'score' => $score,
        'submitted_at' => '2026-11-05 00:0'.$minute.':00',
    ]);
}
echo json_encode(['user_id' => $user->id], JSON_THROW_ON_ERROR);
