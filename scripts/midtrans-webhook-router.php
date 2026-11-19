<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

// Expose only this webhook when a developer starts a temporary HTTPS tunnel.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (PHP_SAPI !== 'cli-server' || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
    || $path !== '/api/payments/midtrans/notification') {
    http_response_code(404);
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo '{"message":"Not found."}';

    return;
}
ini_set('display_errors', '0');
putenv('APP_DEBUG=false');
$_ENV['APP_DEBUG'] = $_SERVER['APP_DEBUG'] = 'false';
try {
    require dirname(__DIR__).'/vendor/autoload.php';
    $app = require dirname(__DIR__).'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    $app['config']->set('app.debug', false);
    $app->handleRequest(Request::capture());
} catch (Throwable) {
    http_response_code(503);
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo '{"message":"Webhook temporarily unavailable."}';
}
