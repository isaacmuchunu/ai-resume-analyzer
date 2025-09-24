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
        Schema::table('users', function (Blueprint $table) {
            // API Key fields
            $table->text('api_key')->nullable()->after('remember_token');
            $table->timestamp('api_key_expires_at')->nullable()->after('api_key');
            $table->timestamp('api_last_used_at')->nullable()->after('api_key_expires_at');
            $table->timestamp('api_key_regenerated_at')->nullable()->after('api_last_used_at');

            // Enhanced authentication fields
            $table->integer('login_attempts')->default(0)->after('api_key_regenerated_at');
            $table->timestamp('locked_until')->nullable()->after('login_attempts');
            $table->timestamp('last_login_at')->nullable()->after('locked_until');
            $table->ipAddress('last_login_ip')->nullable()->after('last_login_at');

            // Add indexes for performance
            $table->index('api_key_expires_at');
            $table->index('locked_until');
            $table->index('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'api_key',
                'api_key_expires_at',
                'api_last_used_at',
                'api_key_regenerated_at',
                'login_attempts',
                'locked_until',
                'last_login_at',
                'last_login_ip',
            ]);
        });
    }
};