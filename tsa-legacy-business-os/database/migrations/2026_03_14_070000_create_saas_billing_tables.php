<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('monthly_price', 12, 2);
            $table->decimal('yearly_price', 12, 2)->nullable();
            $table->string('currency', 8)->default('INR');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();
        });

        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('value_type', ['boolean', 'integer', 'string', 'json'])->default('boolean');
            $table->json('default_value')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('plan_feature_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->foreignId('feature_flag_id')->constrained('feature_flags')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(false);
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['plan_id', 'feature_flag_id']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('plan_id')->constrained('plans');
            $table->enum('status', ['trialing', 'active', 'past_due', 'canceled', 'expired', 'pending'])->default('pending')->index();
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancel_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->string('gateway')->default('razorpay')->index();
            $table->string('gateway_payment_id')->nullable()->index();
            $table->string('gateway_order_id')->nullable()->index();
            $table->string('gateway_signature')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('INR');
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending')->index();
            $table->timestamp('paid_at')->nullable()->index();
            $table->string('invoice_number')->nullable()->unique();
            $table->string('receipt_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->index();
            $table->string('event_id')->nullable()->index();
            $table->string('event_type')->nullable()->index();
            $table->string('signature')->nullable();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->enum('status', ['received', 'processed', 'failed'])->default('received')->index();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('action')->index();
            $table->string('resource_type')->nullable()->index();
            $table->string('resource_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
        });

        Schema::create('login_activities', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->enum('status', ['success', 'failed'])->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->timestamp('attempted_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_activities');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plan_feature_flags');
        Schema::dropIfExists('feature_flags');
        Schema::dropIfExists('plans');
    }
};

