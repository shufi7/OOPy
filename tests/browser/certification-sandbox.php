<?php

use App\Models\Certificate;
use App\Models\User;
use App\Services\CertificateIssuanceService;
use App\Services\CertificatePdfService;
use Database\Seeders\CodingExerciseSeeder;
use Database\Seeders\OopyContentSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\CertificationFixture;

$root = dirname(__DIR__, 2);
$run = getenv('OOPY_CERTIFICATION_RUN');
if (! is_string($run) || ! preg_match('/^certification-[a-f0-9]{32}$/D', $run)) {
    throw new RuntimeException('Use a unique disposable certification test directory.');
}
$directory = $root.'/storage/framework/testing/'.$run;
$database = $directory.'/database.sqlite';
foreach ([
    'APP_ENV' => 'local', 'APP_DEBUG' => 'false', 'XDEBUG_MODE' => 'off', 'APP_CONFIG_CACHE' => $directory.'/config.php',
    'APP_KEY' => 'base64:'.base64_encode(str_repeat('c', 32)),
    'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $database, 'DB_URL' => '',
    'SESSION_DRIVER' => 'file', 'SESSION_COOKIE' => 'oopy_certification_qa',
    'CACHE_STORE' => 'array', 'APP_URL' => getenv('OOPY_BASE_URL') ?: 'http://127.0.0.1:8047',
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
if (config('database.default') !== 'sqlite' || DB::connection()->getDatabaseName() !== $database) {
    throw new RuntimeException('Database isolation check failed.');
}
if (($argv[1] ?? '') === 'issue') {
    DB::statement('PRAGMA busy_timeout = 10000');
    $user = User::where('email', 'concurrent.certification@example.test')->sole();
    echo app(CertificateIssuanceService::class)->issue($user, 'qr_payment')->public_id;
    exit(0);
}
if (($argv[1] ?? '') === 'pdf-evidence') {
    $certificate = Certificate::where('user_id', User::where('email', 'eligible.certification@example.test')->sole()->id)->sole();
    $pdf = app(CertificatePdfService::class);
    file_put_contents($directory.'/certificate-http.pdf', $pdf->generate($certificate));
    $certificate->participant_name = str_repeat('Nama Peserta Panjang ', 12).'Akhir';
    file_put_contents($directory.'/certificate-long-name.pdf', $pdf->generate($certificate));
    echo "Isolated PDF evidence generated.\n";
    exit(0);
}
if (file_exists($database)) {
    throw new RuntimeException('Refusing to overwrite an existing test database.');
}
if (! is_dir($directory)) {
    mkdir($directory, 0777, true);
}
touch($database);
Artisan::call('migrate', ['--force' => true]);
Artisan::call('db:seed', ['--class' => OopyContentSeeder::class, '--force' => true]);
Artisan::call('db:seed', ['--class' => CodingExerciseSeeder::class, '--force' => true]);
foreach (['eligible', 'empty', 'concurrent'] as $kind) {
    $user = User::create(['name' => 'Peserta Sertifikasi '.ucfirst($kind), 'email' => $kind.'.certification@example.test', 'password' => 'password123']);
    if ($kind !== 'empty') {
        CertificationFixture::complete($user);
    }
}
echo "Isolated certification fixture created.\n";
