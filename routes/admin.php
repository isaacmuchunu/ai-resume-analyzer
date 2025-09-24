<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Admin authentication routes (no middleware, accessible to all)
Route::prefix('admin')->name('admin.')->group(function () {

    // Admin login routes (accessible without authentication)
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

    // Protected admin routes (require admin authentication)
    Route::middleware(['auth', 'is.admin'])->group(function () {

        // Admin dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.alt');

        // User management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/{user}', [UserController::class, 'show'])->name('show');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
            Route::put('/{user}/subscription', [UserController::class, 'updateSubscription'])->name('subscription.update');
        });

        // Admin logout (protected)
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    });
});