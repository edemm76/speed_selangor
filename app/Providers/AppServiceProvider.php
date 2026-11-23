<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Page numbers (1 2 3 ...) styled with Bootstrap, like the rest of the system
        Paginator::useBootstrapFive();

        // Separate attempt limits for each form, so mistakes on one form
        // never block another (used as throttle:login / register / password in routes/web.php)

        // Login: 5 tries per minute for the same e-mail from the same computer
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')) . '|' . $request->ip());
        });

        // Registration: 10 tries per minute from the same computer
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Forgot / reset password: 10 tries per minute from the same computer
        RateLimiter::for('password', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}