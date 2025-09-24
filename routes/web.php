<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ResumeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public landing page (no tenant required)
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('welcome');

// For development/demo purposes, allow direct access without strict tenant requirements
Route::middleware(['web'])->group(function () {

    // Authenticated routes
    Route::middleware(['auth', 'track.activity'])->group(function () {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Analytics
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/', [\App\Http\Controllers\AnalyticsController::class, 'index'])->name('index');
            Route::get('/api', [\App\Http\Controllers\AnalyticsController::class, 'api'])->name('api');
        });

        // Subscription management
        Route::prefix('subscription')->name('subscription.')->group(function () {
            Route::get('/', [\App\Http\Controllers\SubscriptionController::class, 'index'])->name('index');
            Route::get('/upgrade', [\App\Http\Controllers\SubscriptionController::class, 'upgrade'])->name('upgrade');
            Route::post('/change-plan', [\App\Http\Controllers\SubscriptionController::class, 'changePlan'])->name('change-plan');
            Route::post('/cancel', [\App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('cancel');
            Route::get('/usage', [\App\Http\Controllers\SubscriptionController::class, 'usage'])->name('usage');
        });

        // Resume management
        Route::prefix('resumes')->name('resumes.')->group(function () {
            Route::get('/', [ResumeController::class, 'index'])->name('index');
            Route::get('/upload', function () {
                return Inertia::render('Resumes/Upload', [
                    'subscription' => auth()->user()->subscription ? [
                        'plan' => auth()->user()->subscription->plan,
                        'remaining_resumes' => auth()->user()->subscription->remaining_resumes,
                        'can_upload' => auth()->user()->subscription->canUploadResume(),
                    ] : null,
                ]);
            })->name('upload.form');
            Route::post('/upload', [ResumeController::class, 'upload'])
                ->middleware('check.subscription:upload_resume')
                ->name('upload');
            Route::get('/{resume}', [ResumeController::class, 'show'])->name('show');
            Route::get('/{resume}/download', [ResumeController::class, 'download'])->name('download');
            Route::delete('/{resume}', [ResumeController::class, 'delete'])->name('delete');
            Route::post('/{resume}/reanalyze', [ResumeController::class, 'reanalyze'])
                ->middleware('check.subscription:reanalyze')
                ->name('reanalyze');
        });

        // Settings management
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [\App\Http\Controllers\SettingsController::class, 'index'])->name('index');
            Route::put('/profile', [\App\Http\Controllers\SettingsController::class, 'updateProfile'])->name('profile.update');
            Route::put('/password', [\App\Http\Controllers\SettingsController::class, 'updatePassword'])->name('password.update');
            Route::put('/notifications', [\App\Http\Controllers\SettingsController::class, 'updateNotifications'])->name('notifications.update');
            Route::put('/appearance', [\App\Http\Controllers\SettingsController::class, 'updateAppearance'])->name('appearance.update');
            Route::delete('/account', [\App\Http\Controllers\SettingsController::class, 'deleteAccount'])->name('account.delete');
        });

    });

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
