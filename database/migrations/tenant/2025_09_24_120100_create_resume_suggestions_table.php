<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resume_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained('resumes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type'); // rewrite, shorten, expand, quantify
            $table->text('original_text');
            $table->text('suggested_text');
            $table->json('context')->nullable();
            $table->enum('status', ['pending', 'applied', 'dismissed'])->default('pending');
            $table->timestamps();

            $table->index(['resume_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_suggestions');
    }
};


