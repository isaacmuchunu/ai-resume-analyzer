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
        // Error logs table
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('error_id')->unique();
            $table->string('type');
            $table->text('message');
            $table->string('file');
            $table->integer('line');
            $table->longText('trace');
            $table->json('context')->nullable();
            $table->enum('severity', ['critical', 'error', 'warning', 'info']);
            $table->string('fingerprint', 32);
            $table->timestamps();

            $table->index(['created_at', 'severity']);
            $table->index('fingerprint');
            $table->index('type');
        });

        // Performance logs table
        Schema::create('performance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('operation');
            $table->decimal('duration', 8, 3);
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'duration']);
            $table->index('operation');
        });

        // Security logs table
        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->json('data');
            $table->ipAddress('ip_address');
            $table->text('user_agent')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('severity', ['high', 'medium', 'low']);
            $table->timestamps();

            $table->index(['created_at', 'severity']);
            $table->index('type');
            $table->index('ip_address');
        });

        // System health metrics table
        Schema::create('system_health_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_name');
            $table->decimal('value', 10, 4);
            $table->string('unit')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['metric_name', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_health_metrics');
        Schema::dropIfExists('security_logs');
        Schema::dropIfExists('performance_logs');
        Schema::dropIfExists('error_logs');
    }
};