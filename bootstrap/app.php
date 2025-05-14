<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;
use App\Exceptions\Handler;
use Illuminate\Contracts\Debug\ExceptionHandler;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(
        \App\Http\Middleware\CorsImageMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (Throwable $e, $request) {
            // optional: custom rendering
        });

        // Properly bind your custom handler (only works if Laravel 11 is fully set up)
        $exceptions->reportable(function (Throwable $e) {
            // logging or custom reporting logic
        });
    })
    ->create();

