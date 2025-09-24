<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ResumeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public landing page (no tenant required)
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('welcome');

// Webhooks (no authentication required)
Route::post('/webhooks/stripe', [\App\Http\Controllers\WebhookController::class, 'stripe'])->name('webhooks.stripe');

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

            // Stripe payment integration
            Route::post('/checkout', [\App\Http\Controllers\SubscriptionController::class, 'checkout'])->name('checkout');
            Route::get('/success', [\App\Http\Controllers\SubscriptionController::class, 'success'])->name('success');
            Route::get('/cancel-checkout', [\App\Http\Controllers\SubscriptionController::class, 'checkoutCancel'])->name('cancel-checkout');
            Route::post('/portal', [\App\Http\Controllers\SubscriptionController::class, 'portal'])->name('portal');
            Route::post('/cancel-subscription', [\App\Http\Controllers\SubscriptionController::class, 'cancelSubscription'])->name('cancel-subscription');
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

            // Editor
            Route::get('/{resume}/editor', [\App\Http\Controllers\EditorController::class, 'show'])->name('editor');
            Route::post('/{resume}/editor/suggest', [\App\Http\Controllers\EditorController::class, 'suggest'])->name('editor.suggest');
            Route::post('/{resume}/editor/save', [\App\Http\Controllers\EditorController::class, 'saveVersion'])->name('editor.save');
            Route::get('/{resume}/editor/versions', [\App\Http\Controllers\EditorController::class, 'versions'])->name('editor.versions');
            Route::post('/{resume}/editor/versions/{version}/restore', [\App\Http\Controllers\EditorController::class, 'restoreVersion'])->name('editor.versions.restore');
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

        // Notification management
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [\App\Http\Controllers\NotificationController::class, 'index'])->name('index');
            Route::get('/api', [\App\Http\Controllers\NotificationController::class, 'api'])->name('api');
            Route::get('/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('unread-count');
            Route::post('/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('mark-as-read');
            Route::post('/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
            Route::delete('/{notification}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('destroy');
            Route::post('/test', [\App\Http\Controllers\NotificationController::class, 'test'])->name('test');
        });

    });

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
