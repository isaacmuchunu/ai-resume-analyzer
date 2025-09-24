<?php

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\ResumeApiController;
use App\Http\Controllers\Api\AnalysisApiController;
use App\Http\Controllers\Api\UserApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // Public API routes (no authentication required)
    Route::get('/', [ApiController::class, 'index'])->name('index');
    Route::get('/status', [ApiController::class, 'status'])->name('status');
    Route::get('/health', [ApiController::class, 'health'])->name('health');

    // Authentication required routes
    Route::middleware(['api.auth', 'rate.limit:api'])->group(function () {

        // User endpoints
        Route::prefix('user')->name('user.')->group(function () {
            Route::get('/', [UserApiController::class, 'profile'])->name('profile');
            Route::put('/', [UserApiController::class, 'updateProfile'])->name('update');
            Route::post('/regenerate-api-key', [UserApiController::class, 'regenerateApiKey'])->name('regenerate-key');
            Route::get('/usage', [UserApiController::class, 'usage'])->name('usage');
        });

        // Resume endpoints
        Route::prefix('resumes')->name('resumes.')->group(function () {
            Route::get('/', [ResumeApiController::class, 'index'])->name('index');
            Route::post('/', [ResumeApiController::class, 'upload'])
                ->middleware('rate.limit:upload')
                ->name('upload');
            Route::get('/{resume}', [ResumeApiController::class, 'show'])->name('show');
            Route::delete('/{resume}', [ResumeApiController::class, 'delete'])->name('delete');
            Route::post('/{resume}/reanalyze', [ResumeApiController::class, 'reanalyze'])
                ->middleware('rate.limit:analysis')
                ->name('reanalyze');
        });

        // Analysis endpoints
        Route::prefix('analysis')->name('analysis.')->group(function () {
            Route::get('/{resume}', [AnalysisApiController::class, 'show'])->name('show');
            Route::get('/{resume}/history', [AnalysisApiController::class, 'history'])->name('history');
            Route::post('/{resume}/feedback', [AnalysisApiController::class, 'feedback'])->name('feedback');
        });
    });

    // Rate limited public endpoints
    Route::middleware(['rate.limit:api'])->group(function () {
        Route::post('/analyze-text', [AnalysisApiController::class, 'analyzeText'])->name('analyze.text');
    });
});

// Webhook endpoints (special handling)
Route::prefix('webhooks')->name('api.webhooks.')->group(function () {
    Route::post('/stripe', [\App\Http\Controllers\Api\WebhookController::class, 'stripe'])->name('stripe');
    Route::post('/anthropic', [\App\Http\Controllers\Api\WebhookController::class, 'anthropic'])->name('anthropic');
});