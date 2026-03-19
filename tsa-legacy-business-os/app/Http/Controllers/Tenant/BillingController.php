<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SelectPlanRequest;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\CentralDatabase;
use App\Services\Billing\InvoiceNumberGenerator;
use App\Services\Billing\RazorpayService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(): View
    {
        $tenantId = (string) tenant('id');

        $plans = Plan::query()
            ->where('is_active', true)
            ->with('features')
            ->orderBy('sort_order')
            ->get();

        $subscription = Subscription::query()
            ->with('plan.features')
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->first();

        $payments = Payment::query()
            ->where('tenant_id', $tenantId)
            ->latest()
            ->limit(10)
            ->get();

        return view('tenant.billing.index', compact('plans', 'subscription', 'payments'));
    }

    public function selectPlan(SelectPlanRequest $request): JsonResponse
    {
        $tenantId = (string) tenant('id');
        $plan = Plan::query()->findOrFail($request->integer('plan_id'));

        $billingCycle = $request->string('billing_cycle')->toString();

        $subscription = Subscription::query()
            ->where('tenant_id', $tenantId)
            ->where('status', Subscription::STATUS_PENDING)
            ->latest('id')
            ->first();

        if ($subscription) {
            $subscription->update([
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'metadata' => array_merge($subscription->metadata ?? [], [
                    'updated_via' => 'plan_selection',
                    'updated_at' => now()->toISOString(),
                ]),
            ]);
        } else {
            $subscription = Subscription::query()->create([
                'tenant_id' => $tenantId,
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_PENDING,
                'billing_cycle' => $billingCycle,
                'trial_ends_at' => null,
                'metadata' => [
                    'created_via' => 'plan_selection',
                ],
            ]);
        }

        return response()->json([
            'message' => 'Plan selected. Proceed to payment.',
            'subscription_id' => $subscription->id,
        ]);
    }

    public function createCheckout(Request $request, RazorpayService $razorpayService): JsonResponse
    {
        $validated = $request->validate([
            'subscription_id' => [
                'required',
                'integer',
                Rule::exists(CentralDatabase::table('subscriptions'), 'id'),
            ],
        ]);

        $tenantId = (string) tenant('id');
        $subscription = Subscription::query()->with('plan')->findOrFail($validated['subscription_id']);

        abort_unless($subscription->tenant_id === $tenantId, 403);

        $amount = (float) ($subscription->billing_cycle === 'yearly'
            ? ($subscription->plan->yearly_price ?? 0)
            : $subscription->plan->monthly_price);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'subscription_id' => 'Selected plan does not have a valid payable amount.',
            ]);
        }

        if (! $razorpayService->isConfigured()) {
            throw ValidationException::withMessages([
                'payment_gateway' => 'Payment gateway is not configured. Please contact support.',
            ]);
        }

        try {
            $order = $razorpayService->createOrder(
                (int) round($amount * 100),
                'sub_'.$subscription->id.'_'.now()->timestamp
            );
        } catch (\Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'payment_gateway' => 'Unable to create checkout session right now. Please try again.',
            ]);
        }

        Payment::query()->create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'gateway' => 'razorpay',
            'gateway_order_id' => (string) data_get($order, 'id'),
            'amount' => $amount,
            'currency' => (string) data_get($order, 'currency', 'INR'),
            'status' => 'pending',
        ]);

        return response()->json([
            'key' => config('services.razorpay.key_id'),
            'order_id' => data_get($order, 'id'),
            'amount' => data_get($order, 'amount'),
            'currency' => data_get($order, 'currency'),
            'name' => config('app.name'),
            'description' => $subscription->plan->name.' Plan Subscription',
            'tenant_id' => $subscription->tenant_id,
        ]);
    }

    public function paymentSuccess(
        Request $request,
        RazorpayService $razorpayService,
        InvoiceNumberGenerator $invoiceNumberGenerator
    ): JsonResponse {
        $validated = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $isValid = $razorpayService->verifyPaymentSignature(
            $validated['razorpay_order_id'],
            $validated['razorpay_payment_id'],
            $validated['razorpay_signature']
        );

        if (! $isValid) {
            return response()->json(['message' => 'Payment signature mismatch.'], 422);
        }

        $tenantId = (string) tenant('id');

        CentralDatabase::connection()->transaction(function () use ($validated, $invoiceNumberGenerator, $tenantId): void {
            $payment = Payment::query()
                ->where('gateway_order_id', $validated['razorpay_order_id'])
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status === 'paid' && $payment->gateway_payment_id === $validated['razorpay_payment_id']) {
                return;
            }

            if ($payment->status === 'paid' && $payment->gateway_payment_id !== $validated['razorpay_payment_id']) {
                throw ValidationException::withMessages([
                    'razorpay_order_id' => 'Order is already marked paid with a different payment reference.',
                ]);
            }

            $payment->update([
                'gateway_payment_id' => $validated['razorpay_payment_id'],
                'gateway_signature' => $validated['razorpay_signature'],
                'status' => 'paid',
                'paid_at' => now(),
                'invoice_number' => $payment->invoice_number ?: $invoiceNumberGenerator->make('SINV'),
            ]);

            $subscription = $payment->subscription()->lockForUpdate()->first();

            if (! $subscription) {
                throw ValidationException::withMessages([
                    'razorpay_order_id' => 'Subscription not found for payment.',
                ]);
            }

            $anchorDate = $subscription->ends_at && $subscription->ends_at->isFuture()
                ? CarbonImmutable::instance($subscription->ends_at)
                : CarbonImmutable::now();

            $endsAt = match ($subscription->billing_cycle) {
                'yearly' => $anchorDate->addYearNoOverflow(),
                default => $anchorDate->addMonthNoOverflow(),
            };

            $subscription->update([
                'status' => Subscription::STATUS_ACTIVE,
                'started_at' => $subscription->started_at ?: now(),
                'trial_ends_at' => null,
                'ends_at' => $endsAt,
            ]);
        });

        return response()->json(['message' => 'Payment captured successfully.']);
    }
}
