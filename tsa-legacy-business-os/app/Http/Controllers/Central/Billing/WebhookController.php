<?php

namespace App\Http\Controllers\Central\Billing;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\WebhookEvent;
use App\Support\CentralDatabase;
use App\Services\Billing\InvoiceNumberGenerator;
use App\Services\Billing\RazorpayService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __invoke(
        Request $request,
        RazorpayService $razorpayService,
        InvoiceNumberGenerator $invoiceNumberGenerator
    ): Response {
        $payload = $request->getContent();
        $signature = (string) $request->header('X-Razorpay-Signature', '');
        $payloadHash = hash('sha256', $payload);

        if (! $signature || ! $razorpayService->verifyWebhookSignature($payload, $signature)) {
            return response('Invalid signature', 400);
        }

        $data = $request->json()->all();
        $eventType = data_get($data, 'event');
        $headerEventId = trim((string) $request->header('X-Razorpay-Event-Id', ''));
        $eventId = $headerEventId !== '' ? $headerEventId : 'hash_'.$payloadHash;

        try {
            $webhook = WebhookEvent::query()->firstOrCreate(
                ['event_id' => $eventId],
                [
                    'provider' => 'razorpay',
                    'event_type' => $eventType,
                    'signature' => $signature,
                    'payload_hash' => $payloadHash,
                    'payload' => $data,
                    'status' => 'received',
                ]
            );
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'Duplicate entry')) {
                return response('ok', 200);
            }

            throw $exception;
        }

        if (! $webhook->wasRecentlyCreated) {
            return response('ok', 200);
        }

        try {
            if ($eventType === 'payment.captured') {
                $gatewayOrderId = (string) data_get($data, 'payload.payment.entity.order_id');
                $gatewayPaymentId = (string) data_get($data, 'payload.payment.entity.id');
                $capturedAtUnix = (int) data_get($data, 'payload.payment.entity.created_at', time());

                CentralDatabase::connection()->transaction(function () use (
                    $gatewayOrderId,
                    $gatewayPaymentId,
                    $signature,
                    $capturedAtUnix,
                    $invoiceNumberGenerator
                ): void {
                    $payment = Payment::query()
                        ->where('gateway_order_id', $gatewayOrderId)
                        ->lockForUpdate()
                        ->first();

                    if (! $payment) {
                        return;
                    }

                    if ($payment->status === 'paid' && $payment->gateway_payment_id === $gatewayPaymentId) {
                        return;
                    }

                    if ($payment->status === 'paid' && $payment->gateway_payment_id !== $gatewayPaymentId) {
                        throw new \RuntimeException('Payment already captured with a different gateway payment ID.');
                    }

                    $paidAt = CarbonImmutable::createFromTimestamp($capturedAtUnix);

                    $payment->update([
                        'gateway_payment_id' => $gatewayPaymentId,
                        'gateway_signature' => $signature,
                        'status' => 'paid',
                        'paid_at' => $paidAt,
                        'invoice_number' => $payment->invoice_number ?: $invoiceNumberGenerator->make('SINV'),
                    ]);

                    $subscription = $payment->subscription()->lockForUpdate()->first();

                    if (! $subscription) {
                        return;
                    }

                    $anchorDate = $subscription->ends_at && $subscription->ends_at->isFuture()
                        ? CarbonImmutable::instance($subscription->ends_at)
                        : $paidAt;

                    $endsAt = match ($subscription->billing_cycle) {
                        'yearly' => $anchorDate->addYearNoOverflow(),
                        default => $anchorDate->addMonthNoOverflow(),
                    };

                    $subscription->update([
                        'status' => Subscription::STATUS_ACTIVE,
                        'started_at' => $subscription->started_at ?: $paidAt,
                        'trial_ends_at' => null,
                        'ends_at' => $endsAt,
                    ]);
                });
            }

            $webhook->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Razorpay webhook processing failed', [
                'message' => $exception->getMessage(),
            ]);

            $webhook->update([
                'status' => 'failed',
                'processed_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);

            return response('Webhook processing failed', 500);
        }

        return response('ok', 200);
    }
}
