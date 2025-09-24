<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->integer('resumes_uploaded')->default(0);
            $table->integer('analyses_completed')->default(0);
            $table->integer('downloads_count')->default(0);
            $table->integer('page_views')->default(0);
            $table->integer('session_duration')->default(0); // in seconds
            $table->json('actions_taken')->nullable(); // track specific user actions
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index(['date', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_analytics');
    }
};