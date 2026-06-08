<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',

        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'account_type' => \App\Http\Middleware\AccountTypeMiddleware::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\SetUserLocation::class,
            \App\Http\Middleware\EnsureProfileIsCompleted::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            '/shufti/callback',
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
