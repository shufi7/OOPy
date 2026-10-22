<?php

require __DIR__.'/../../vendor/autoload.php';

$database = getenv('OOPY_TEST_DATABASE');
if (! is_string($database) || ! preg_match('/^oopy_grader_test_[a-f0-9]{32}$/D', $database)) {
    throw new RuntimeException('This probe requires an isolated oopy_grader_test database.');
}
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'mysql', 'database.connections.mysql.database' => $database,
    'database.connections.mysql.url' => null]);
Illuminate\Support\Facades\DB::purge('mysql');
if (Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== $database) {
    throw new RuntimeException('Database isolation check failed.');
}
$email = $argv[1] ?? '';
if (! str_ends_with($email, '@example.test')) {
    throw new RuntimeException('Only test account addresses are accepted.');
}
$user = App\Models\User::where('email', $email)->sole();
$rows = App\Models\ExerciseSubmission::with('exercise')->where('user_id', $user->id)->orderBy('id')->get();
echo json_encode(['database' => $database, 'driver' => 'mysql', 'user_id' => $user->id,
    'submissions' => $rows->map(fn ($row) => [
        'id' => $row->id, 'user_id' => $row->user_id, 'exercise_key' => $row->exercise->exercise_key,
        'status' => $row->status, 'score' => $row->score, 'code' => json_decode($row->code, true),
        'output' => $row->output, 'submitted_at' => $row->submitted_at->toIso8601String(),
    ])->all()], JSON_THROW_ON_ERROR);
