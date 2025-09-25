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
        Schema::create('ats_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained()->onDelete('cascade');
            $table->foreignId('section_id')->nullable()->constrained('resume_sections')->onDelete('cascade');
            $table->string('suggestion_type', 50); // keyword, format, content, structure
            $table->enum('priority', ['critical', 'high', 'medium', 'low'])->default('medium');
            $table->string('title');
            $table->text('description');
            $table->text('original_text')->nullable();
            $table->text('suggested_text')->nullable();
            $table->integer('ats_impact')->default(0); // Expected score improvement
            $table->string('reason')->nullable();
            $table->enum('status', ['pending', 'applied', 'dismissed', 'expired'])->default('pending');
            $table->timestamp('applied_at')->nullable();
            $table->json('metadata')->nullable(); // Additional suggestion data
            $table->timestamps();

            $table->index(['resume_id', 'status']);
            $table->index(['section_id', 'priority']);
            $table->index(['suggestion_type', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ats_suggestions');
    }
};