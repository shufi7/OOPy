<?php

use App\Models\Certificate;
use App\Models\CertificateOrder;
use App\Models\CertificatePaymentAttempt;
use App\Models\User;
use App\Services\CertificateIssuanceService;
use App\Services\CertificatePdfService;
use App\Services\MidtransGateway;
use App\Services\MidtransPaymentService;
use Database\Seeders\CodingExerciseSeeder;
use Database\Seeders\OopyContentSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Support\CertificationFixture;

$root = dirname(__DIR__, 2);
$run = getenv('OOPY_MIDTRANS_RUN');
if (! is_string($run) || ! preg_match('/^midtrans-[a-f0-9]{32}$/D', $run)) {
    throw new RuntimeException('A unique disposable Midtrans test directory is required.');
}
$directory = $root.'/storage/framework/testing/'.$run;
$database = $directory.'/database.sqlite';
foreach ([
    'APP_ENV' => 'local', 'APP_DEBUG' => 'false', 'APP_CONFIG_CACHE' => $directory.'/config.php',
    'APP_KEY' => 'base64:'.base64_encode(str_repeat('t', 32)),
    'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $database, 'DB_URL' => '',
    'SESSION_DRIVER' => 'file', 'SESSION_COOKIE' => 'oopy_midtrans_qa',
    'CACHE_STORE' => 'array', 'APP_URL' => getenv('OOPY_BASE_URL') ?: 'http://127.0.0.1:8048',
    'MIDTRANS_IS_PRODUCTION' => 'false', 'MIDTRANS_SERVER_KEY' => 'SB-Mid-server-browser-fixture-only',
    'MIDTRANS_CLIENT_KEY' => 'SB-Mid-client-browser-fixture-only', 'MIDTRANS_SIGNATURE_SECRET_KEY' => '',
    'MIDTRANS_MERCHANT_ID' => 'BROWSER-FIXTURE-MERCHANT',
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
}
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'sqlite' || DB::connection()->getDatabaseName() !== $database) {
    throw new RuntimeException('Database isolation check failed.');
}
Http::preventStrayRequests();
Http::fake(function ($request) use ($directory) {
    if ($request->url() === MidtransGateway::SNAP_URL) {
        $token = 'fixture-token-'.strtolower($request['transaction_details']['order_id']);

        return Http::response(['token' => $token, 'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/'.$token], 201);
    }
    if (str_starts_with($request->url(), MidtransGateway::STATUS_URL)) {
        $status = trim(file_get_contents($directory.'/provider-status.txt'));
        if ($status === 'unselected') {
            return Http::response(['status_code' => '404'], 404);
        }
        preg_match('~/v2/([^/]+)/status$~', $request->url(), $matches);

        return Http::response(['order_id' => $matches[1], 'gross_amount' => '24999.00', 'currency' => 'IDR',
            'transaction_id' => 'fixture-transaction-'.$matches[1], 'merchant_id' => 'BROWSER-FIXTURE-MERCHANT',
            'payment_type' => 'qris', 'status_code' => $status === 'pending' ? '201' : '200',
            'transaction_status' => $status, 'fraud_status' => 'accept']);
    }
    throw new RuntimeException('Unexpected network request in isolated browser fixture.');
});
if (PHP_SAPI === 'cli-server') {
    $app->handleRequest(Request::capture());

    return;
}
if (($argv[1] ?? '') === 'prepare-race') {
    DB::statement('PRAGMA busy_timeout = 15000');
    $user = User::where('email', 'concurrent.midtrans@example.test')->sole();
    app(MidtransPaymentService::class)->start($user);
    echo "Concurrent fixture prepared.\n";
    exit;
}
if (($argv[1] ?? '') === 'sync-race') {
    DB::statement('PRAGMA busy_timeout = 15000');
    $attempt = CertificatePaymentAttempt::whereHas('order', fn ($query) => $query->where('user_id', User::where('email', 'concurrent.midtrans@example.test')->sole()->id))->sole();
    app(MidtransPaymentService::class)->synchronize($attempt);
    echo "Verified.\n";
    exit;
}
if (($argv[1] ?? '') === 'report') {
    $user = User::where('email', 'concurrent.midtrans@example.test')->sole();
    echo json_encode(['race_certificates' => Certificate::where('user_id', $user->id)->count(),
        'race_orders' => CertificateOrder::where('user_id', $user->id)->count()]);
    exit;
}
if (($argv[1] ?? '') === 'pdf-evidence') {
    $certificate = Certificate::where('program_key', MidtransPaymentService::PROGRAM)->where('user_id', User::where('email', 'eligible.midtrans@example.test')->sole()->id)->sole();
    $pdf = app(CertificatePdfService::class);
    file_put_contents($directory.'/certificate-sandbox.pdf', $pdf->generate($certificate));
    $certificate->participant_name = str_repeat('Nama Peserta Panjang ', 12).'Akhir';
    file_put_contents($directory.'/certificate-long-name.pdf', $pdf->generate($certificate));
    echo "PDF evidence generated.\n";
    exit;
}
if (file_exists($database)) {
    throw new RuntimeException('Refusing to overwrite an existing database.');
}
if (! is_dir($directory)) {
    mkdir($directory, 0777, true);
}
touch($database);
DB::statement('PRAGMA journal_mode = WAL');
Artisan::call('migrate', ['--force' => true]);
Artisan::call('db:seed', ['--class' => OopyContentSeeder::class, '--force' => true]);
Artisan::call('db:seed', ['--class' => CodingExerciseSeeder::class, '--force' => true]);
foreach (['eligible', 'empty', 'concurrent', 'legacy'] as $kind) {
    $user = User::create(['name' => 'Peserta Sandbox '.ucfirst($kind), 'email' => $kind.'.midtrans@example.test', 'password' => 'password123']);
    if ($kind !== 'empty') {
        CertificationFixture::complete($user);
    }
    if ($kind === 'legacy') {
        app(CertificateIssuanceService::class)->issue($user, 'e_wallet');
    }
}
file_put_contents($directory.'/provider-status.txt', 'unselected');
echo "Isolated Midtrans fixture created; all provider calls are mocked.\n";
