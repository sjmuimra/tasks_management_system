<?php

use App\Http\Middleware\TaskManagement\EnsureNotOverdueUnlessAdmin;
use App\Http\Middleware\TaskManagement\EnsureTaskOwnership;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'task.owner' => EnsureTaskOwnership::class,
            'task.overdue' => EnsureNotOverdueUnlessAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
