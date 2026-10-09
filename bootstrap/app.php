<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('materi.index'));
        $middleware->alias(['role' => EnsureUserHasRole::class]);
        // Code answers use the old quiz engine's exact whitespace/case comparison.
        $middleware->trimStrings(except: ['answers.*.answer']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
