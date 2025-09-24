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
            Route::post('/{user}/impersonate', [UserController::class, 'impersonate'])->name('impersonate');
            Route::post('/bulk-action', [UserController::class, 'bulkAction'])->name('bulk-action');
        });

        // Resume management
        Route::prefix('resumes')->name('resumes.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ResumeController::class, 'index'])->name('index');
            Route::get('/{resume}', [\App\Http\Controllers\Admin\ResumeController::class, 'show'])->name('show');
            Route::delete('/{resume}', [\App\Http\Controllers\Admin\ResumeController::class, 'destroy'])->name('destroy');
            Route::post('/{resume}/flag', [\App\Http\Controllers\Admin\ResumeController::class, 'flag'])->name('flag');
        });

        // Analytics and reports
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('index');
            Route::get('/users', [\App\Http\Controllers\Admin\AnalyticsController::class, 'users'])->name('users');
            Route::get('/revenue', [\App\Http\Controllers\Admin\AnalyticsController::class, 'revenue'])->name('revenue');
            Route::get('/export/{type}', [\App\Http\Controllers\Admin\AnalyticsController::class, 'export'])->name('export');
        });

        // System logs and monitoring
        Route::prefix('system')->name('system.')->group(function () {
            Route::get('/logs', [\App\Http\Controllers\Admin\SystemController::class, 'logs'])->name('logs');
            Route::get('/errors', [\App\Http\Controllers\Admin\SystemController::class, 'errors'])->name('errors');
            Route::get('/performance', [\App\Http\Controllers\Admin\SystemController::class, 'performance'])->name('performance');
            Route::get('/health', [\App\Http\Controllers\Admin\SystemController::class, 'health'])->name('health');
            Route::post('/clear-cache', [\App\Http\Controllers\Admin\SystemController::class, 'clearCache'])->name('clear-cache');
        });

        // Subscription management
        Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\SubscriptionController::class, 'index'])->name('index');
            Route::get('/{subscription}', [\App\Http\Controllers\Admin\SubscriptionController::class, 'show'])->name('show');
            Route::post('/{subscription}/cancel', [\App\Http\Controllers\Admin\SubscriptionController::class, 'cancel'])->name('cancel');
            Route::post('/sync-stripe', [\App\Http\Controllers\Admin\SubscriptionController::class, 'syncStripe'])->name('sync-stripe');
        });

        // Settings
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('index');
            Route::put('/general', [\App\Http\Controllers\Admin\SettingsController::class, 'updateGeneral'])->name('update-general');
            Route::put('/features', [\App\Http\Controllers\Admin\SettingsController::class, 'updateFeatures'])->name('update-features');
            Route::put('/integrations', [\App\Http\Controllers\Admin\SettingsController::class, 'updateIntegrations'])->name('update-integrations');
        });

        // Admin logout (protected)
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    });
});