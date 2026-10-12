<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\LogSlowRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->appendToGroup('web', [LogSlowRequests::class]);
        $middleware->alias([
            'admin' => \App\Http\Middleware\CheckAdminRole::class,
            'admin.preferences' => \App\Http\Middleware\ApplyAdminPreferences::class,
            'idempotent' => \App\Http\Middleware\IdempotentFormSubmission::class,
            'personal-security.recovery' => \App\Http\Middleware\RequirePersonalSecurityRecoverySetup::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash([
            'security_answer',
            'security_answer_confirmation',
            'personal_security_answer',
            'recovery_code',
            'current_password',
        ]);
    })->create();
