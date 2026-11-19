<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Not logged in and opens a protected page -> go to the login page
        $middleware->redirectGuestsTo(fn () => route('login'));

        // Already logged in and opens the login page -> go to their own dashboard
        $middleware->redirectUsersTo(fn () => Auth::guard('admin')->check()
            ? route('admin.dashboard')
            : route('vendor.dashboard'));

        // Short names we can use in routes/web.php
        $middleware->alias([
            'vendor.active' => \App\Http\Middleware\EnsureVendorIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();