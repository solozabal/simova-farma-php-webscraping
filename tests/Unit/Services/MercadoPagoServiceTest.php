<?php

namespace Tests\Unit\Services;

use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MercadoPagoServiceTest extends TestCase
{
    use RefreshDatabase;
    private MercadoPagoService $service;
    private string $secret = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MercadoPagoService();
    }

    // -------------------------------------------------------------------------
    // validateWebhookSignature
    // -------------------------------------------------------------------------

    private function makeSignatureHeader(string $ts, string $dataId, string $requestId): string
    {
        $signedTemplate = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $hash           = hash_hmac('sha256', $signedTemplate, $this->secret);
        return "ts={$ts};v1={$hash}";
    }

    public function test_validates_correct_signature(): void
    {
        $ts         = (string) time();
        $dataId     = '12345';
        $requestId  = 'req-abc-123';
        $xSignature = $this->makeSignatureHeader($ts, $dataId, $requestId);

        $result = $this->service->validateWebhookSignature($xSignature, $requestId, $dataId, '');

        $this->assertTrue($result);
    }

    public function test_rejects_invalid_signature(): void
    {
        $ts         = (string) time();
        $xSignature = "ts={$ts};v1=invalidsignaturehash";

        $result = $this->service->validateWebhookSignature($xSignature, 'req-123', '456', '');

        $this->assertFalse($result);
    }

    public function test_rejects_empty_signature(): void
    {
        $result = $this->service->validateWebhookSignature('', 'req-123', '456', '');

        $this->assertFalse($result);
    }

    public function test_rejects_signature_without_ts(): void
    {
        $result = $this->service->validateWebhookSignature('v1=abc123', 'req-123', '456', '');

        $this->assertFalse($result);
    }

    public function test_rejects_signature_without_v1(): void
    {
        $result = $this->service->validateWebhookSignature('ts=1234567890', 'req-123', '456', '');

        $this->assertFalse($result);
    }

    public function test_rejects_when_webhook_secret_not_configured(): void
    {
        config(['services.mercadopago.webhook_secret' => null]);

        // Re-instantiate to pick up config change
        $service = new MercadoPagoService();
        $ts      = (string) time();

        $result = $service->validateWebhookSignature("ts={$ts};v1=whatever", 'req-123', '456', '');

        $this->assertFalse($result);
    }

    public function test_rejects_signature_tampered_data_id(): void
    {
        $ts         = (string) time();
        $dataId     = '12345';
        $requestId  = 'req-abc-123';
        $xSignature = $this->makeSignatureHeader($ts, $dataId, $requestId);

        // Tampered: different dataId
        $result = $this->service->validateWebhookSignature($xSignature, $requestId, '99999', '');

        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // getCheckoutUrl
    // -------------------------------------------------------------------------

    public function test_get_checkout_url_contains_plan_id(): void
    {
        $url = $this->service->getCheckoutUrl();

        $this->assertStringContainsString('test-plan-id', $url);
        $this->assertStringContainsString('mercadopago.com.br', $url);
    }

    public function test_get_checkout_url_with_user_includes_email(): void
    {
        $user = \App\Models\User::factory()->create(['email' => 'farmacia@teste.com.br']);

        $url = $this->service->getCheckoutUrl($user);

        $this->assertStringContainsString(urlencode('farmacia@teste.com.br'), $url);
    }

    // -------------------------------------------------------------------------
    // Constructor throws without access token
    // -------------------------------------------------------------------------

    public function test_constructor_throws_when_access_token_not_configured(): void
    {
        config(['services.mercadopago.access_token' => null]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/MERCADO_PAGO_ACCESS_TOKEN/');

        new MercadoPagoService();
    }
}
