<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_optimizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained()->onDelete('cascade');
            $table->string('job_title');
            $table->text('job_description');
            $table->json('required_skills')->nullable();
            $table->json('missing_skills')->nullable();
            $table->json('keyword_gaps')->nullable();
            $table->integer('match_score')->default(0);
            $table->json('optimization_data')->nullable(); // Detailed optimization suggestions
            $table->json('industry_keywords')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['resume_id', 'is_active']);
            $table->index('match_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_optimizations');
    }
};