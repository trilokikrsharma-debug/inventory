<?php

namespace Tests\Unit\Billing;

use App\Services\Billing\RazorpayService;
use Tests\TestCase;

class RazorpayServiceTest extends TestCase
{
    public function test_it_reports_configuration_state(): void
    {
        config()->set('services.razorpay.key_id', '');
        config()->set('services.razorpay.key_secret', '');

        $service = new RazorpayService();
        $this->assertFalse($service->isConfigured());

        config()->set('services.razorpay.key_id', 'rzp_test_key');
        config()->set('services.razorpay.key_secret', 'rzp_test_secret');

        $service = new RazorpayService();
        $this->assertTrue($service->isConfigured());
    }

    public function test_payment_signature_verification_requires_configured_secret(): void
    {
        $orderId = 'order_test_001';
        $paymentId = 'pay_test_001';
        $payload = "{$orderId}|{$paymentId}";

        config()->set('services.razorpay.key_id', '');
        config()->set('services.razorpay.key_secret', '');

        $service = new RazorpayService();
        $this->assertFalse($service->verifyPaymentSignature($orderId, $paymentId, 'anything'));

        config()->set('services.razorpay.key_id', 'rzp_test_key');
        config()->set('services.razorpay.key_secret', 'rzp_test_secret');

        $validSignature = hash_hmac('sha256', $payload, 'rzp_test_secret');
        $service = new RazorpayService();

        $this->assertTrue($service->verifyPaymentSignature($orderId, $paymentId, $validSignature));
        $this->assertFalse($service->verifyPaymentSignature($orderId, $paymentId, 'invalid-signature'));
    }

    public function test_webhook_signature_verification_requires_webhook_secret(): void
    {
        $payload = json_encode([
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_123']]],
        ], JSON_THROW_ON_ERROR);

        config()->set('services.razorpay.webhook_secret', '');

        $service = new RazorpayService();
        $this->assertFalse($service->verifyWebhookSignature($payload, 'anything'));

        config()->set('services.razorpay.webhook_secret', 'webhook_secret');

        $validSignature = hash_hmac('sha256', $payload, 'webhook_secret');
        $service = new RazorpayService();

        $this->assertTrue($service->verifyWebhookSignature($payload, $validSignature));
        $this->assertFalse($service->verifyWebhookSignature($payload, 'invalid-signature'));
    }
}
