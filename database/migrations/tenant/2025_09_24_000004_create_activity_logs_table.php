<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->nullableMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->string('batch_uuid')->nullable();
            $table->timestamps();

            $table->index('log_name');
            $table->index(['subject_type', 'subject_id'], 'subject');
            $table->index(['causer_type', 'causer_id'], 'causer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};