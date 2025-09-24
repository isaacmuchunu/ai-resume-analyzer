<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('filename');
            $table->string('original_filename');
            $table->integer('file_size');
            $table->string('file_type');
            $table->string('storage_path');
            $table->enum('parsing_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->enum('analysis_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['parsing_status', 'analysis_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resumes');
    }
};