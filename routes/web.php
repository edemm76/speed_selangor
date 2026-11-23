<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\Vendor;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public / guest
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => redirect()->route('login'));

// Only for people who are NOT logged in (logged-in users are sent to their dashboard)
Route::middleware('guest:admin,vendor')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login') // max 5 attempts per minute (AppServiceProvider)
        ->name('login.attempt');

    // SSMS-UC-100 A1: applicant registration
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('throttle:register') // max 10 attempts per minute (AppServiceProvider)
        ->name('register.store');

    // SSMS-UC-100 A2: forgot / reset password (both roles)
    Route::get('/forgot-password', [PasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordController::class, 'store'])
        ->middleware('throttle:password')
        ->name('password.email');
    Route::get('/reset-password/{role}/{token}', [PasswordController::class, 'edit'])
        ->whereIn('role', ['vendor', 'admin'])
        ->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'update'])
        ->middleware('throttle:password')
        ->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:admin,vendor')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Vendor (Pemohon)
|--------------------------------------------------------------------------
*/

Route::prefix('vendor')
    ->name('vendor.')
    ->middleware(['auth:vendor', 'vendor.active'])
    ->group(function () {
        Route::get('/dashboard', [Vendor\DashboardController::class, 'index'])->name('dashboard');

        // SSMS-UC-300: my applications (new, draft, submit)
        Route::get('/applications', [Vendor\ApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/create', [Vendor\ApplicationController::class, 'create'])->name('applications.create');
        Route::post('/applications', [Vendor\ApplicationController::class, 'store'])->name('applications.store');
        Route::get('/applications/{application}/edit', [Vendor\ApplicationController::class, 'edit'])->name('applications.edit');
        Route::put('/applications/{application}', [Vendor\ApplicationController::class, 'update'])->name('applications.update');
        Route::delete('/applications/{application}', [Vendor\ApplicationController::class, 'destroy'])->name('applications.destroy');
        Route::get('/applications/{application}/documents/{document}', [Vendor\ApplicationController::class, 'document'])
            ->name('applications.document');

        // SSMS-UC-200: own profile
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    });

/*
|--------------------------------------------------------------------------
| Administrator (Pegawai PBT)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware('auth:admin')
    ->group(function () {
        Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

        // SSMS-UC-200: applicant list, suspend / reactivate
        Route::get('/vendors', [Admin\VendorController::class, 'index'])->name('vendors.index');
        Route::get('/vendors/{vendor}', [Admin\VendorController::class, 'show'])->name('vendors.show');
        Route::patch('/vendors/{vendor}/suspend', [Admin\VendorController::class, 'suspend'])->name('vendors.suspend');
        Route::patch('/vendors/{vendor}/activate', [Admin\VendorController::class, 'activate'])->name('vendors.activate');

        // SSMS-UC-200: own profile
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    });