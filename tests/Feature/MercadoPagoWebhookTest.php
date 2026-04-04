<?php

namespace Tests\Feature;

use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests for POST /webhooks/mercadopago
 *
 * Covers:
 *  - Signature validation (valid / invalid / missing secret)
 *  - Subscription event: authorized → activate user
 *  - Subscription event: cancelled/paused → cancel user
 *  - Payment event: approved → ensure user is active
 *  - Idempotency: sending the same event twice does not duplicate state changes
 *  - Unknown event type is handled gracefully
 */
class MercadoPagoWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret = 'test-webhook-secret';

    private function makeSignatureHeader(string $dataId, string $requestId, string $ts): string
    {
        $signedTemplate = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $hash           = hash_hmac('sha256', $signedTemplate, $this->webhookSecret);
        return "ts={$ts};v1={$hash}";
    }

    private function postWebhook(
        array $payload,
        string $dataId = 'sub-001',
        string $requestId = 'req-001',
        ?string $xSignature = null
    ) {
        $ts         = (string) time();
        $signature  = $xSignature ?? $this->makeSignatureHeader($dataId, $requestId, $ts);

        // Use data_id (not data.id) because PHP's parse_str converts dots to underscores
        return $this->postJson(
            "/webhooks/mercadopago?data_id={$dataId}",
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

    public function test_rejects_request_with_invalid_signature(): void
    {
        $response = $this->postWebhook(
            ['type' => 'subscription_preapproval', 'action' => 'updated'],
            xSignature: 'ts=9999;v1=badhash'
        );

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Assinatura inválida']);
    }

    public function test_rejects_request_with_missing_signature(): void
    {
        $response = $this->postJson(
            '/webhooks/mercadopago?data.id=sub-001',
            ['type' => 'subscription_preapproval']
        );

        $response->assertStatus(401);
    }

    public function test_accepts_request_with_valid_signature(): void
    {
        Http::fake([
            'api.mercadopago.com/*' => Http::response(['status' => 'authorized', 'payer_id' => null, 'payer_email' => null], 200),
        ]);

        $response = $this->postWebhook(['type' => 'unknown_event', 'action' => 'test']);

        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Subscription events: state transitions
    // -------------------------------------------------------------------------

    public function test_authorized_subscription_activates_user(): void
    {
        $user = User::factory()->pending()->create([
            'payment_customer_id' => 'payer-123',
        ]);

        Http::fake([
            'api.mercadopago.com/preapproval/*' => Http::response([
                'status'      => 'authorized',
                'payer_id'    => 'payer-123',
                'payer_email' => $user->email,
            ], 200),
        ]);

        $this->postWebhook([
            'type'   => 'subscription_preapproval',
            'action' => 'updated',
            'data'   => ['id' => 'sub-001'],
        ], dataId: 'sub-001');

        $user->refresh();
        $this->assertSame('active', $user->status);
        $this->assertNotNull($user->activated_at);
    }

    public function test_cancelled_subscription_cancels_user(): void
    {
        $user = User::factory()->active()->create([
            'payment_customer_id' => 'payer-456',
        ]);

        Http::fake([
            'api.mercadopago.com/preapproval/*' => Http::response([
                'status'      => 'cancelled',
                'payer_id'    => 'payer-456',
                'payer_email' => $user->email,
            ], 200),
        ]);

        $this->postWebhook([
            'type'   => 'subscription_preapproval',
            'action' => 'updated',
            'data'   => ['id' => 'sub-002'],
        ], dataId: 'sub-002');

        $user->refresh();
        $this->assertSame('cancelled', $user->status);
        $this->assertNotNull($user->cancelled_at);
    }

    public function test_paused_subscription_cancels_user(): void
    {
        $user = User::factory()->active()->create([
            'payment_customer_id' => 'payer-789',
        ]);

        Http::fake([
            'api.mercadopago.com/preapproval/*' => Http::response([
                'status'      => 'paused',
                'payer_id'    => 'payer-789',
                'payer_email' => $user->email,
            ], 200),
        ]);

        $this->postWebhook([
            'type'   => 'subscription_preapproval',
            'action' => 'updated',
            'data'   => ['id' => 'sub-003'],
        ], dataId: 'sub-003');

        $user->refresh();
        $this->assertSame('cancelled', $user->status);
    }

    // -------------------------------------------------------------------------
    // Payment events
    // -------------------------------------------------------------------------

    public function test_approved_payment_activates_pending_user(): void
    {
        $user = User::factory()->pending()->create([
            'payment_customer_id' => 'pay-cust-001',
        ]);

        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::response([
                'status' => 'approved',
                'payer'  => ['id' => 'pay-cust-001'],
            ], 200),
        ]);

        $this->postWebhook([
            'type'   => 'payment',
            'action' => 'payment.created',
            'data'   => ['id' => 'pay-001'],
        ], dataId: 'pay-001');

        $user->refresh();
        $this->assertSame('active', $user->status);
    }

    public function test_payment_event_for_already_active_user_does_not_change_status(): void
    {
        $user = User::factory()->active()->create([
            'payment_customer_id' => 'pay-cust-002',
        ]);

        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::response([
                'status' => 'approved',
                'payer'  => ['id' => 'pay-cust-002'],
            ], 200),
        ]);

        $this->postWebhook([
            'type'   => 'payment',
            'action' => 'payment.created',
            'data'   => ['id' => 'pay-002'],
        ], dataId: 'pay-002');

        $user->refresh();
        $this->assertSame('active', $user->status);
    }

    // -------------------------------------------------------------------------
    // Idempotency: sending the same event multiple times
    // -------------------------------------------------------------------------

    public function test_sending_authorized_event_twice_keeps_user_active(): void
    {
        $user = User::factory()->pending()->create([
            'payment_customer_id' => 'payer-idem',
        ]);

        Http::fake([
            'api.mercadopago.com/preapproval/*' => Http::response([
                'status'      => 'authorized',
                'payer_id'    => 'payer-idem',
                'payer_email' => $user->email,
            ], 200),
        ]);

        $payload = [
            'type'   => 'subscription_preapproval',
            'action' => 'updated',
            'data'   => ['id' => 'sub-idem'],
        ];

        $this->postWebhook($payload, dataId: 'sub-idem');
        $this->postWebhook($payload, dataId: 'sub-idem');

        $user->refresh();
        $this->assertSame('active', $user->status);
    }

    // -------------------------------------------------------------------------
    // Unknown user
    // -------------------------------------------------------------------------

    public function test_unknown_user_does_not_throw(): void
    {
        Http::fake([
            'api.mercadopago.com/preapproval/*' => Http::response([
                'status'      => 'authorized',
                'payer_id'    => 'unknown-payer',
                'payer_email' => 'naoexiste@example.com',
            ], 200),
        ]);

        $response = $this->postWebhook([
            'type'   => 'subscription_preapproval',
            'action' => 'updated',
            'data'   => ['id' => 'sub-unknown'],
        ], dataId: 'sub-unknown');

        // Should not return 500
        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Error handling
    // -------------------------------------------------------------------------

    public function test_returns_200_even_when_mp_api_is_down(): void
    {
        Http::fake([
            'api.mercadopago.com/*' => Http::response(null, 500),
        ]);

        $response = $this->postWebhook([
            'type'   => 'subscription_preapproval',
            'action' => 'updated',
            'data'   => ['id' => 'sub-error'],
        ], dataId: 'sub-error');

        // Should return 200 to prevent MP from retrying in an infinite loop
        $response->assertStatus(200);
    }

    public function test_logs_invalid_signature_attempt(): void
    {
        $this->postJson(
            '/webhooks/mercadopago?data.id=sub-001',
            ['type' => 'subscription_preapproval'],
            ['x-signature' => 'ts=1;v1=bad', 'x-request-id' => 'req-001']
        );

        $this->assertDatabaseHas('system_logs', [
            'channel' => 'mercadopago',
            'level'   => 'warning',
        ]);
    }
}
