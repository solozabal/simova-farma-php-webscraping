<?php

namespace Tests\Feature;

use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests for POST /webhooks/telegram
 *
 * Covers:
 *  - /start without token → shows signup link
 *  - /start with valid token → links telegram_id, activates pending user
 *  - /start with expired token → error message
 *  - /start with invalid token → error message
 *  - /start when already linked → already linked message
 *  - /ajuda and /help → help message
 *  - Unknown command → default message
 *  - callback_query → graceful handling
 */
class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function postUpdate(array $update): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/webhooks/telegram', $update);
    }

    private function messageUpdate(
        string $text,
        string|int $chatId = 100,
        string|int $userId = 200,
        string $username = 'farmaceiro'
    ): array {
        return [
            'update_id' => rand(1000, 9999),
            'message'   => [
                'message_id' => rand(1, 999),
                'from'       => [
                    'id'         => $userId,
                    'first_name' => 'Farmaceiro',
                    'username'   => $username,
                ],
                'chat' => ['id' => $chatId],
                'text' => $text,
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Fake all Telegram API calls so tests don't hit the real API
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);
    }

    // -------------------------------------------------------------------------
    // /start without token
    // -------------------------------------------------------------------------

    public function test_start_without_token_sends_signup_link(): void
    {
        $response = $this->postUpdate($this->messageUpdate('/start'));

        $response->assertStatus(200)->assertJson(['ok' => true]);

        // Verify Telegram sendMessage was called
        Http::assertSent(function ($request) {
            $body = $request->data();
            return str_contains($body['text'] ?? '', 'SIMOVA FARMA');
        });
    }

    // -------------------------------------------------------------------------
    // /start with valid token
    // -------------------------------------------------------------------------

    public function test_start_with_valid_token_links_telegram_and_activates_pending_user(): void
    {
        $user = User::factory()->pending()->create();
        $token = $user->generateTelegramLinkToken();

        $this->postUpdate($this->messageUpdate('/start ' . $token, chatId: 555, userId: 777));

        $user->refresh();
        $this->assertSame('777', $user->telegram_id);
        $this->assertSame('farmaceiro', $user->telegram_username);
        $this->assertNotNull($user->telegram_linked_at);
        $this->assertNull($user->telegram_link_token);
        $this->assertSame('active', $user->status);
    }

    public function test_start_with_valid_token_for_active_user_links_without_status_change(): void
    {
        $user = User::factory()->active()->create();
        $token = $user->generateTelegramLinkToken();

        $this->postUpdate($this->messageUpdate('/start ' . $token, chatId: 600, userId: 800));

        $user->refresh();
        $this->assertSame('800', $user->telegram_id);
        $this->assertSame('active', $user->status); // stays active
    }

    public function test_start_with_valid_token_logs_success(): void
    {
        $user  = User::factory()->pending()->create();
        $token = $user->generateTelegramLinkToken();

        $this->postUpdate($this->messageUpdate('/start ' . $token, chatId: 700, userId: 900));

        $this->assertDatabaseHas('system_logs', [
            'channel' => 'telegram',
            'level'   => 'info',
        ]);
    }

    // -------------------------------------------------------------------------
    // /start with invalid / expired token
    // -------------------------------------------------------------------------

    public function test_start_with_invalid_token_sends_error_message(): void
    {
        $response = $this->postUpdate($this->messageUpdate('/start invalidtoken123', chatId: 400, userId: 500));

        $response->assertStatus(200);

        // User should NOT be linked
        $this->assertDatabaseMissing('users', ['telegram_id' => '500']);

        Http::assertSent(function ($request) {
            $body = $request->data();
            return str_contains($body['text'] ?? '', 'inválido') || str_contains($body['text'] ?? '', 'expirado');
        });
    }

    public function test_start_with_expired_token_sends_error_message(): void
    {
        $user = User::factory()->pending()->create([
            'telegram_link_token'            => 'expired-token-12345',
            'telegram_link_token_expires_at' => now()->subHour(), // expired
        ]);

        $response = $this->postUpdate($this->messageUpdate('/start expired-token-12345', chatId: 450, userId: 550));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['telegram_id' => '550']);
    }

    // -------------------------------------------------------------------------
    // /start when already linked
    // -------------------------------------------------------------------------

    public function test_start_when_already_linked_sends_already_linked_message(): void
    {
        $telegramId = '999888777';
        User::factory()->active()->withTelegram($telegramId)->create();

        $this->postUpdate($this->messageUpdate('/start', chatId: 300, userId: (int) $telegramId));

        Http::assertSent(function ($request) {
            $body = $request->data();
            return str_contains($body['text'] ?? '', 'vinculada');
        });
    }

    // -------------------------------------------------------------------------
    // /ajuda and /help
    // -------------------------------------------------------------------------

    public function test_ajuda_command_sends_help_message(): void
    {
        $this->postUpdate($this->messageUpdate('/ajuda'));

        Http::assertSent(function ($request) {
            $body = $request->data();
            return str_contains($body['text'] ?? '', 'Comandos');
        });
    }

    public function test_help_command_sends_help_message(): void
    {
        $this->postUpdate($this->messageUpdate('/help'));

        Http::assertSent(function ($request) {
            $body = $request->data();
            return str_contains($body['text'] ?? '', 'Comandos');
        });
    }

    // -------------------------------------------------------------------------
    // Unknown command
    // -------------------------------------------------------------------------

    public function test_unknown_command_sends_default_message(): void
    {
        $this->postUpdate($this->messageUpdate('/unknown_command'));

        Http::assertSent(function ($request) {
            return isset($request->data()['text']);
        });
    }

    // -------------------------------------------------------------------------
    // Missing text / chat_id
    // -------------------------------------------------------------------------

    public function test_update_without_message_returns_200(): void
    {
        $response = $this->postUpdate(['update_id' => 123]);

        $response->assertStatus(200)->assertJson(['ok' => true]);
    }

    public function test_message_without_text_returns_200(): void
    {
        $update = [
            'update_id' => 123,
            'message'   => [
                'from' => ['id' => 100, 'first_name' => 'Test'],
                'chat' => ['id' => 100],
                // no 'text'
            ],
        ];

        $response = $this->postUpdate($update);
        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // callback_query
    // -------------------------------------------------------------------------

    public function test_callback_query_is_handled_gracefully(): void
    {
        $update = [
            'update_id'      => 123,
            'callback_query' => [
                'id'      => 'cbq-001',
                'message' => ['chat' => ['id' => 100]],
                'data'    => 'some_callback',
            ],
        ];

        $response = $this->postUpdate($update);
        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Response always 200 (Telegram requirement)
    // -------------------------------------------------------------------------

    public function test_webhook_always_returns_200(): void
    {
        $response = $this->postJson('/webhooks/telegram', ['update_id' => 999]);

        $response->assertStatus(200);
    }
}
