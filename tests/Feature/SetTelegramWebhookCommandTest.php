<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests for SetTelegramWebhookCommand
 *
 * php artisan telegram:set-webhook          → registers webhook
 * php artisan telegram:set-webhook --delete → removes webhook
 */
class SetTelegramWebhookCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_webhook_succeeds(): void
    {
        Http::fake([
            'api.telegram.org/*/setWebhook'    => Http::response(['ok' => true], 200),
            'api.telegram.org/*/getWebhookInfo' => Http::response([
                'ok'     => true,
                'result' => ['url' => 'https://test.com/webhooks/telegram'],
            ], 200),
        ]);

        $this->artisan('telegram:set-webhook')
            ->assertExitCode(0)
            ->expectsOutputToContain('sucesso');
    }

    public function test_set_webhook_fails_when_api_returns_error(): void
    {
        Http::fake([
            'api.telegram.org/*/setWebhook' => Http::response(['ok' => false], 400),
        ]);

        $this->artisan('telegram:set-webhook')
            ->assertExitCode(1);
    }

    public function test_delete_webhook_succeeds(): void
    {
        Http::fake([
            'api.telegram.org/*/deleteWebhook' => Http::response(['ok' => true], 200),
        ]);

        $this->artisan('telegram:set-webhook', ['--delete' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('removido');
    }

    public function test_delete_webhook_fails_when_api_returns_error(): void
    {
        Http::fake([
            'api.telegram.org/*/deleteWebhook' => Http::response(['ok' => false], 400),
        ]);

        $this->artisan('telegram:set-webhook', ['--delete' => true])
            ->assertExitCode(1);
    }
}
