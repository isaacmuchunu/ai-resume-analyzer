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
    Route::middleware(['auth:sanctum'])->group(function () {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Resume management
        Route::prefix('resumes')->name('resumes.')->group(function () {
            Route::get('/', [ResumeController::class, 'index'])->name('index');
            Route::get('/{resume}', [ResumeController::class, 'show'])->name('show');
            Route::post('/upload', [ResumeController::class, 'upload'])->name('upload');
            Route::get('/{resume}/download', [ResumeController::class, 'download'])->name('download');
            Route::delete('/{resume}', [ResumeController::class, 'delete'])->name('delete');
            Route::post('/{resume}/reanalyze', [ResumeController::class, 'reanalyze'])->name('reanalyze');
        });

    });

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
