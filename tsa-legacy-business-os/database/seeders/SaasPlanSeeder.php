<?php

namespace Database\Seeders;

use App\Models\FeatureFlag;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class SaasPlanSeeder extends Seeder
{
    public function run(): void
    {
        $featureFlags = [
            ['key' => 'inventory_enabled', 'name' => 'Inventory Module', 'value_type' => 'boolean', 'default_value' => ['value' => false]],
            ['key' => 'crm_enabled', 'name' => 'CRM Module', 'value_type' => 'boolean', 'default_value' => ['value' => false]],
            ['key' => 'hr_enabled', 'name' => 'HR Module', 'value_type' => 'boolean', 'default_value' => ['value' => false]],
            ['key' => 'accounting_enabled', 'name' => 'Accounting Module', 'value_type' => 'boolean', 'default_value' => ['value' => false]],
            ['key' => 'api_access', 'name' => 'API Access', 'value_type' => 'boolean', 'default_value' => ['value' => false]],
            ['key' => 'max_users', 'name' => 'Max Users', 'value_type' => 'integer', 'default_value' => ['value' => 3]],
            ['key' => 'max_products', 'name' => 'Max Products', 'value_type' => 'integer', 'default_value' => ['value' => 500]],
            ['key' => 'max_customers', 'name' => 'Max Customers', 'value_type' => 'integer', 'default_value' => ['value' => 200]],
            ['key' => 'max_invoices', 'name' => 'Max Invoices', 'value_type' => 'integer', 'default_value' => ['value' => 500]],
        ];

        foreach ($featureFlags as $flag) {
            FeatureFlag::query()->updateOrCreate(
                ['key' => $flag['key']],
                $flag + ['is_active' => true]
            );
        }

        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Best for new businesses.',
                'monthly_price' => 999.00,
                'yearly_price' => 9990.00,
                'sort_order' => 1,
                'features' => [
                    'inventory_enabled' => [true, ['value' => true]],
                    'crm_enabled' => [false, ['value' => false]],
                    'hr_enabled' => [false, ['value' => false]],
                    'accounting_enabled' => [true, ['value' => true]],
                    'api_access' => [false, ['value' => false]],
                    'max_users' => [true, ['value' => 5]],
                    'max_products' => [true, ['value' => 1000]],
                    'max_customers' => [true, ['value' => 1000]],
                    'max_invoices' => [true, ['value' => 2000]],
                ],
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Scaling teams and automation.',
                'monthly_price' => 2499.00,
                'yearly_price' => 24990.00,
                'sort_order' => 2,
                'features' => [
                    'inventory_enabled' => [true, ['value' => true]],
                    'crm_enabled' => [true, ['value' => true]],
                    'hr_enabled' => [false, ['value' => false]],
                    'accounting_enabled' => [true, ['value' => true]],
                    'api_access' => [true, ['value' => true]],
                    'max_users' => [true, ['value' => 20]],
                    'max_products' => [true, ['value' => 10000]],
                    'max_customers' => [true, ['value' => 10000]],
                    'max_invoices' => [true, ['value' => 50000]],
                ],
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Full platform with highest limits.',
                'monthly_price' => 4999.00,
                'yearly_price' => 49990.00,
                'sort_order' => 3,
                'features' => [
                    'inventory_enabled' => [true, ['value' => true]],
                    'crm_enabled' => [true, ['value' => true]],
                    'hr_enabled' => [true, ['value' => true]],
                    'accounting_enabled' => [true, ['value' => true]],
                    'api_access' => [true, ['value' => true]],
                    'max_users' => [true, ['value' => 500]],
                    'max_products' => [true, ['value' => 100000]],
                    'max_customers' => [true, ['value' => 200000]],
                    'max_invoices' => [true, ['value' => 1000000]],
                ],
            ],
        ];

        foreach ($plans as $planData) {
            $features = $planData['features'];
            unset($planData['features']);

            $plan = Plan::query()->updateOrCreate(
                ['slug' => $planData['slug']],
                $planData + ['currency' => 'INR', 'is_active' => true]
            );

            foreach ($features as $featureKey => [$enabled, $value]) {
                $flag = FeatureFlag::query()->where('key', $featureKey)->first();

                if (! $flag) {
                    continue;
                }

                $plan->features()->syncWithoutDetaching([
                    $flag->id => [
                        'is_enabled' => $enabled,
                        'value' => json_encode($value, JSON_THROW_ON_ERROR),
                    ],
                ]);
            }
        }
    }
}
