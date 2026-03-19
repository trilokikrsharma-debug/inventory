<?php

namespace App\Services\Billing;

use Razorpay\Api\Api;
use RuntimeException;

class RazorpayService
{
    private ?Api $client = null;

    /**
     * @return array<string, mixed>
     */
    public function createOrder(int $amountInPaise, string $receipt, string $currency = 'INR'): array
    {
        return $this->client()->order->create([
            'amount' => $amountInPaise,
            'currency' => $currency,
            'receipt' => $receipt,
            'payment_capture' => 1,
        ])->toArray();
    }

    public function isConfigured(): bool
    {
        return $this->keyId() !== '' && $this->keySecret() !== '';
    }

    public function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $payload = "{$orderId}|{$paymentId}";
        $expected = hash_hmac('sha256', $payload, $this->keySecret());

        return hash_equals($expected, $signature);
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $webhookSecret = (string) config('services.razorpay.webhook_secret', '');

        if ($webhookSecret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($expected, $signature);
    }

    private function client(): Api
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Razorpay credentials are not configured.');
        }

        if ($this->client === null) {
            $this->client = new Api($this->keyId(), $this->keySecret());
        }

        return $this->client;
    }

    private function keyId(): string
    {
        return (string) config('services.razorpay.key_id', '');
    }

    private function keySecret(): string
    {
        return (string) config('services.razorpay.key_secret', '');
    }
}
