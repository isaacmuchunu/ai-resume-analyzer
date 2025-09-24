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
        Schema::table('user_subscriptions', function (Blueprint $table) {
            // Stripe-specific fields
            $table->string('stripe_customer_id')->nullable()->after('metadata');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');
            $table->string('stripe_status')->nullable()->after('stripe_subscription_id');
            $table->string('stripe_price_id')->nullable()->after('stripe_status');

            // Enhanced subscription tracking
            $table->timestamp('current_period_start')->nullable()->after('stripe_price_id');
            $table->timestamp('current_period_end')->nullable()->after('current_period_start');
            $table->timestamp('trial_ends_at')->nullable()->after('current_period_end');
            $table->timestamp('last_payment_date')->nullable()->after('trial_ends_at');

            // Update plan enum to include new Stripe plans
            $table->dropColumn('plan');
        });

        // Add the new plan column with updated enum values
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->enum('plan', ['free', 'starter', 'professional', 'enterprise', 'basic', 'pro'])->default('free')->after('user_id');
        });

        // Update status enum to include Stripe statuses
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->enum('status', [
                'active',
                'cancelled',
                'expired',
                'suspended',
                'trialing',
                'past_due',
                'unpaid',
                'incomplete',
                'incomplete_expired'
            ])->default('active')->after('plan');
        });

        // Add indexes for Stripe fields
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->index('stripe_customer_id');
            $table->index('stripe_subscription_id');
            $table->index(['stripe_status', 'status']);
            $table->index('current_period_end');
            $table->index('trial_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            // Remove Stripe-specific fields
            $table->dropColumn([
                'stripe_customer_id',
                'stripe_subscription_id',
                'stripe_status',
                'stripe_price_id',
                'current_period_start',
                'current_period_end',
                'trial_ends_at',
                'last_payment_date'
            ]);

            // Remove indexes
            $table->dropIndex(['stripe_customer_id']);
            $table->dropIndex(['stripe_subscription_id']);
            $table->dropIndex(['stripe_status', 'status']);
            $table->dropIndex(['current_period_end']);
            $table->dropIndex(['trial_ends_at']);
        });

        // Revert plan enum to original values
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropColumn('plan');
        });

        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->enum('plan', ['free', 'basic', 'pro', 'enterprise'])->default('free')->after('user_id');
        });

        // Revert status enum to original values
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->enum('status', ['active', 'cancelled', 'expired', 'suspended'])->default('active')->after('plan');
        });
    }
};