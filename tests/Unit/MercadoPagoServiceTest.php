<?php

namespace Tests\Unit;

use App\Services\MercadoPagoService;
use Tests\TestCase;

class MercadoPagoServiceTest extends TestCase
{
    private MercadoPagoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MercadoPagoService();
    }

    // -------------------------------------------------------------------------
    // Signature validation
    // -------------------------------------------------------------------------

    public function test_validate_signature_returns_false_when_secret_not_configured(): void
    {
        config(['services.mercadopago.webhook_secret' => null]);

        $result = $this->service->validateWebhookSignature(
            'ts=1234;v1=abc',
            'req-id',
            'data-id',
            '{"type":"payment"}'
        );

        $this->assertFalse($result);
    }

    public function test_validate_signature_returns_false_for_missing_ts_or_v1(): void
    {
        config(['services.mercadopago.webhook_secret' => 'secret']);

        // Missing v1
        $result = $this->service->validateWebhookSignature(
            'ts=1234;hash=abc',
            'req-id',
            'data-id',
            '{}'
        );

        $this->assertFalse($result);
    }

    public function test_validate_signature_returns_false_for_wrong_hash(): void
    {
        config(['services.mercadopago.webhook_secret' => 'mysecret']);

        $result = $this->service->validateWebhookSignature(
            'ts=1234567890;v1=wronghash',
            'request-123',
            'data-456',
            '{}'
        );

        $this->assertFalse($result);
    }

    public function test_validate_signature_returns_true_for_correct_hmac(): void
    {
        $secret     = 'test-webhook-secret';
        $ts         = '1700000000';
        $requestId  = 'req-abc';
        $dataId     = 'sub-123';

        config(['services.mercadopago.webhook_secret' => $secret]);

        // Build the signed template exactly as the service does
        $signedTemplate = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $hash           = hash_hmac('sha256', $signedTemplate, $secret);
        $xSignature     = "ts={$ts};v1={$hash}";

        $result = $this->service->validateWebhookSignature(
            $xSignature,
            $requestId,
            $dataId,
            '{}'
        );

        $this->assertTrue($result);
    }

    // -------------------------------------------------------------------------
    // Checkout URL
    // -------------------------------------------------------------------------

    public function test_get_checkout_url_returns_base_url_without_user(): void
    {
        $url = $this->service->getCheckoutUrl(null);

        $this->assertStringContainsString('mercadopago.com.br/subscriptions/checkout', $url);
        $this->assertStringContainsString('test-plan-id', $url);
        $this->assertStringNotContainsString('payer_email', $url);
    }

    public function test_get_checkout_url_includes_email_when_user_given(): void
    {
        $user = \App\Models\User::factory()->create(['email' => 'assinante@exemplo.com']);
        $url  = $this->service->getCheckoutUrl($user);

        $this->assertStringContainsString('payer_email=', $url);
        $this->assertStringContainsString(urlencode('assinante@exemplo.com'), $url);
    }

    // -------------------------------------------------------------------------
    // Constructor
    // -------------------------------------------------------------------------

    public function test_constructor_throws_when_access_token_missing(): void
    {
        config(['services.mercadopago.access_token' => null]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('MERCADO_PAGO_ACCESS_TOKEN');

        new MercadoPagoService();
    }
}
