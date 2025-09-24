<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained()->onDelete('cascade');
            $table->string('analysis_type')->default('comprehensive');
            $table->integer('overall_score')->nullable()->between(0, 100);
            $table->integer('ats_score')->nullable()->between(0, 100);
            $table->integer('content_score')->nullable()->between(0, 100);
            $table->integer('format_score')->nullable()->between(0, 100);
            $table->integer('keyword_score')->nullable()->between(0, 100);
            $table->json('detailed_scores')->nullable();
            $table->json('recommendations')->nullable();
            $table->json('extracted_skills')->nullable();
            $table->json('missing_skills')->nullable();
            $table->json('keywords')->nullable();
            $table->json('sections_analysis')->nullable();
            $table->text('ai_insights')->nullable();
            $table->timestamps();

            $table->index(['resume_id', 'analysis_type']);
            $table->index('overall_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_results');
    }
};