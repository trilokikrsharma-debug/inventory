<?php

namespace Tests\Feature\Billing;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookReplayProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_replay_is_idempotent_and_yearly_subscription_is_extended_for_one_year(): void
    {
        config()->set('services.razorpay.webhook_secret', 'webhook-test-secret');

        $tenant = Tenant::withoutEvents(function () {
            return Tenant::query()->create([
                'id' => 'tenant-webhook-test',
                'name' => 'Webhook Tenant',
                'slug' => 'webhook-tenant',
                'status' => 'active',
                'onboarded_at' => now(),
            ]);
        });

        $plan = Plan::query()->create([
            'name' => 'Enterprise',
            'slug' => 'enterprise-test',
            'monthly_price' => 999,
            'yearly_price' => 9999,
            'currency' => 'INR',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_PENDING,
            'billing_cycle' => 'yearly',
        ]);

        $payment = Payment::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'gateway' => 'razorpay',
            'gateway_order_id' => 'order_replay_test_001',
            'amount' => 9999,
            'currency' => 'INR',
            'status' => 'pending',
        ]);

        $payloadData = [
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_replay_test_001',
                        'order_id' => $payment->gateway_order_id,
                        'created_at' => now()->timestamp,
                    ],
                ],
            ],
        ];

        $payloadJson = json_encode($payloadData, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $payloadJson, 'webhook-test-secret');

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
            'HTTP_X_RAZORPAY_EVENT_ID' => 'evt_replay_001',
        ];

        $first = $this->call('POST', '/api/v1/webhooks/razorpay', [], [], [], $headers, $payloadJson);
        $first->assertStatus(200);

        $second = $this->call('POST', '/api/v1/webhooks/razorpay', [], [], [], $headers, $payloadJson);
        $second->assertStatus(200);

        $payment->refresh();
        $subscription->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertSame('pay_replay_test_001', $payment->gateway_payment_id);
        $this->assertSame(1, WebhookEvent::query()->count());
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertNotNull($subscription->ends_at);
        $this->assertTrue($subscription->ends_at->greaterThan(now()->addMonths(11)));
    }
}
