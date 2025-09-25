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
        Schema::create('resume_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained()->onDelete('cascade');
            $table->string('section_type', 50); // contact, summary, experience, education, skills, projects
            $table->string('title');
            $table->json('content'); // Flexible content storage for different section types
            $table->integer('ats_score')->default(0);
            $table->integer('order_index')->default(0);
            $table->json('metadata')->nullable(); // Additional section-specific data
            $table->timestamps();

            $table->index(['resume_id', 'section_type']);
            $table->index(['resume_id', 'order_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resume_sections');
    }
};