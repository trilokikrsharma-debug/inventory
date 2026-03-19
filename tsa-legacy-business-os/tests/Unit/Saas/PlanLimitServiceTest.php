<?php

namespace Tests\Unit\Saas;

use App\Models\FeatureFlag;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\Saas\PlanLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PlanLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reads_numeric_limits_from_active_plan_features(): void
    {
        $tenant = Tenant::withoutEvents(function () {
            return Tenant::query()->create([
                'id' => 'tenant-plan-limit',
                'name' => 'Limit Tenant',
                'slug' => 'limit-tenant',
                'status' => 'active',
                'onboarded_at' => now(),
            ]);
        });

        $plan = Plan::query()->create([
            'name' => 'Starter',
            'slug' => 'starter-limit',
            'monthly_price' => 100,
            'yearly_price' => 1000,
            'currency' => 'INR',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $feature = FeatureFlag::query()->create([
            'key' => 'max_products',
            'name' => 'Max Products',
            'value_type' => 'integer',
            'default_value' => ['value' => 5],
            'is_active' => true,
        ]);

        $plan->features()->attach($feature->id, [
            'is_enabled' => true,
            'value' => ['value' => 10],
        ]);

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => 'monthly',
            'started_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $service = app(PlanLimitService::class);

        $this->assertSame(10, $service->limitForTenant($tenant->id, 'max_products'));
    }

    public function test_it_throws_validation_exception_when_limit_is_exceeded(): void
    {
        $tenant = Tenant::withoutEvents(function () {
            return Tenant::query()->create([
                'id' => 'tenant-plan-limit-2',
                'name' => 'Limit Tenant 2',
                'slug' => 'limit-tenant-2',
                'status' => 'active',
                'onboarded_at' => now(),
            ]);
        });

        $plan = Plan::query()->create([
            'name' => 'Starter 2',
            'slug' => 'starter-limit-2',
            'monthly_price' => 100,
            'yearly_price' => 1000,
            'currency' => 'INR',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $feature = FeatureFlag::query()->create([
            'key' => 'max_invoices',
            'name' => 'Max Invoices',
            'value_type' => 'integer',
            'default_value' => ['value' => 2],
            'is_active' => true,
        ]);

        $plan->features()->attach($feature->id, [
            'is_enabled' => true,
            'value' => ['value' => 2],
        ]);

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => 'monthly',
            'started_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $service = app(PlanLimitService::class);

        $this->expectException(ValidationException::class);

        $service->enforce($tenant->id, 'max_invoices', 2, 'invoices');
    }
}
