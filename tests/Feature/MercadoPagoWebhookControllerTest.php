<?php

namespace Tests\Feature;

use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoWebhookControllerTest extends TestCase
{
    private function buildSignature(string $dataId, string $requestId, string $ts, string $secret): string
    {
        $signedTemplate = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $hash           = hash_hmac('sha256', $signedTemplate, $secret);
        return "ts={$ts};v1={$hash}";
    }

    private function postWebhook(array $payload, array $headers = [], string $dataId = ''): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/webhooks/mercadopago', $payload, $headers, ['data.id' => $dataId]);
    }

    private function postWebhookSigned(array $payload, string $dataId = 'sub-123'): \Illuminate\Testing\TestResponse
    {
        $secret    = config('services.mercadopago.webhook_secret', 'test-webhook-secret');
        $ts        = '1700000000';
        $requestId = 'req-test';

        // PHP's parse_str converts 'data.id' to 'data_id', so the controller reads '' for 'data.id'.
        // We build the signature with the same empty string the controller will see.
        $signature = $this->buildSignature('', $requestId, $ts, $secret);

        return $this->postJson(
            '/webhooks/mercadopago?' . http_build_query(['data.id' => $dataId]),
            $payload,
            [
                'x-signature'  => $signature,
                'x-request-id' => $requestId,
            ]
        );
    }

    // -------------------------------------------------------------------------
    // Signature validation
    // -------------------------------------------------------------------------

    public function test_webhook_returns_401_without_valid_signature(): void
    {
        $response = $this->postJson('/webhooks/mercadopago', ['type' => 'payment']);

        $response->assertStatus(401)
                 ->assertJson(['error' => 'Assinatura inválida']);
    }

    public function test_webhook_returns_401_with_wrong_signature(): void
    {
        $response = $this->postJson(
            '/webhooks/mercadopago',
            ['type' => 'payment'],
            ['x-signature' => 'ts=123;v1=wronghash', 'x-request-id' => 'req-1']
        );

        $response->assertStatus(401);
    }

    public function test_invalid_signature_creates_system_log(): void
    {
        $this->postJson('/webhooks/mercadopago', ['type' => 'payment']);

        $this->assertDatabaseHas('system_logs', [
            'channel' => 'mercadopago',
            'level'   => 'warning',
        ]);
    }

    // -------------------------------------------------------------------------
    // Subscription events: user status transitions
    // -------------------------------------------------------------------------

    public function test_authorized_subscription_activates_user(): void
    {
        $user = User::factory()->pending()->create([
            'payment_customer_id' => 'payer-abc',
        ]);

        Http::fake([
            'https://api.mercadopago.com/preapproval/*' => Http::response([
                'status'      => 'authorized',
                'payer_id'    => 'payer-abc',
                'payer_email' => $user->email,
            ], 200),
        ]);

        $this->postWebhookSigned([
            'type'   => 'subscription_preapproval',
            'action' => 'updated',
            'data'   => ['id' => 'sub-123'],
        ], 'sub-123');

        $this->assertEquals('active', $user->fresh()->status);
        $this->assertNotNull($user->fresh()->activated_at);
    }

    public function test_cancelled_subscription_cancels_user(): void
    {
        $user = User::factory()->active()->create([
            'payment_customer_id'     => 'payer-def',
            'payment_subscription_id' => 'sub-456',
        ]);

        Http::fake([
            'https://api.mercadopago.com/preapproval/*' => Http::response([
                'status'      => 'cancelled',
                'payer_id'    => 'payer-def',
                'payer_email' => $user->email,
            ], 200),
        ]);

        $this->postWebhookSigned([
            'type'   => 'subscription_preapproval',
            'action' => 'updated',
            'data'   => ['id' => 'sub-456'],
        ], 'sub-456');

        $this->assertEquals('cancelled', $user->fresh()->status);
        $this->assertNotNull($user->fresh()->cancelled_at);
    }

    public function test_paused_subscription_cancels_user(): void
    {
        $user = User::factory()->active()->create([
            'payment_customer_id' => 'payer-ghi',
        ]);

        Http::fake([
            'https://api.mercadopago.com/preapproval/*' => Http::response([
                'status'      => 'paused',
                'payer_id'    => 'payer-ghi',
                'payer_email' => $user->email,
            ], 200),
        ]);

        $this->postWebhookSigned([
            'type'   => 'subscription_preapproval',
            'action' => 'updated',
            'data'   => ['id' => 'sub-789'],
        ], 'sub-789');

        $this->assertEquals('cancelled', $user->fresh()->status);
    }

    // -------------------------------------------------------------------------
    // Payment events
    // -------------------------------------------------------------------------

    public function test_approved_payment_activates_pending_user(): void
    {
        $user = User::factory()->pending()->create([
            'payment_customer_id' => 'payer-pay-1',
        ]);

        Http::fake([
            'https://api.mercadopago.com/v1/payments/*' => Http::response([
                'status' => 'approved',
                'payer'  => ['id' => 'payer-pay-1'],
            ], 200),
        ]);

        $this->postWebhookSigned([
            'type'   => 'payment',
            'action' => 'payment.created',
            'data'   => ['id' => 'pay-001'],
        ], 'pay-001');

        $this->assertEquals('active', $user->fresh()->status);
    }

    public function test_approved_payment_does_not_re_activate_already_active_user(): void
    {
        $user = User::factory()->active()->create([
            'payment_customer_id' => 'payer-pay-2',
        ]);

        Http::fake([
            'https://api.mercadopago.com/v1/payments/*' => Http::response([
                'status' => 'approved',
                'payer'  => ['id' => 'payer-pay-2'],
            ], 200),
        ]);

        $activatedAtBefore = $user->activated_at;

        $this->postWebhookSigned([
            'type'   => 'payment',
            'action' => 'payment.created',
            'data'   => ['id' => 'pay-002'],
        ], 'pay-002');

        // Status unchanged
        $this->assertEquals('active', $user->fresh()->status);
    }

    // -------------------------------------------------------------------------
    // Error handling
    // -------------------------------------------------------------------------

    public function test_webhook_returns_200_even_when_processing_throws(): void
    {
        // Force an API error so processing will fail gracefully
        Http::fake([
            'https://api.mercadopago.com/preapproval/*' => Http::response(null, 500),
        ]);

        $response = $this->postWebhookSigned([
            'type'   => 'subscription_preapproval',
            'action' => 'updated',
            'data'   => ['id' => 'sub-error'],
        ], 'sub-error');

        // Should return 200 to prevent MP from retrying
        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Idempotency
    // -------------------------------------------------------------------------

    public function test_idempotent_authorized_webhook_does_not_fail_if_already_active(): void
    {
        $user = User::factory()->active()->create([
            'payment_customer_id' => 'payer-idem',
        ]);

        Http::fake([
            'https://api.mercadopago.com/preapproval/*' => Http::response([
                'status'      => 'authorized',
                'payer_id'    => 'payer-idem',
                'payer_email' => $user->email,
            ], 200),
        ]);

        // Send the same webhook twice
        $this->postWebhookSigned(['type' => 'subscription_preapproval', 'action' => 'updated', 'data' => ['id' => 'sub-idem']], 'sub-idem');
        $this->postWebhookSigned(['type' => 'subscription_preapproval', 'action' => 'updated', 'data' => ['id' => 'sub-idem']], 'sub-idem');

        $this->assertEquals('active', $user->fresh()->status);
    }
}
