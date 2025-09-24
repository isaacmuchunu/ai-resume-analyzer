<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('plan', ['free', 'basic', 'pro', 'enterprise'])->default('free');
            $table->enum('status', ['active', 'cancelled', 'expired', 'suspended'])->default('active');
            $table->integer('resumes_limit')->default(5);
            $table->integer('resumes_used')->default(0);
            $table->date('period_starts_at');
            $table->date('period_ends_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->json('features')->nullable(); // plan features
            $table->json('metadata')->nullable(); // payment info, etc
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['period_ends_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};