<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

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
        $middleware->prependToPriorityList(SubstituteBindings::class, EnsureUserHasRole::class);
        // Preserve Python source snapshots and the quiz engine's exact comparisons.
        $middleware->trimStrings(except: ['answers.*.answer', 'files.*', 'code_snippet', 'correct_answer', 'sections.*.code', 'sections.*.output',
            'participant_name', 'confirmed_name']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
