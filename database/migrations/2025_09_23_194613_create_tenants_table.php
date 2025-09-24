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
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('domain')->unique()->nullable();
            $table->string('subdomain')->unique()->nullable();
            $table->enum('plan', ['starter', 'professional', 'enterprise'])->default('starter');
            $table->boolean('is_active')->default(true);
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['domain', 'subdomain']);
            $table->index('plan');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
