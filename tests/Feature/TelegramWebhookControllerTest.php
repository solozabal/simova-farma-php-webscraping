<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Support\Str;
use Tests\TestCase;

class TelegramWebhookControllerTest extends TestCase
{
    private function postUpdate(array $update): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/webhooks/telegram', $update);
    }

    private function makeMessageUpdate(
        string $text,
        string $chatId = '100001',
        string $userId = '100001',
        string $username = 'testuser',
        string $firstName = 'Test'
    ): array {
        return [
            'update_id' => random_int(1000, 9999),
            'message'   => [
                'message_id' => 1,
                'from'       => [
                    'id'         => (int) $userId,
                    'username'   => $username,
                    'first_name' => $firstName,
                ],
                'chat' => ['id' => (int) $chatId],
                'text' => $text,
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // /start without token
    // -------------------------------------------------------------------------

    public function test_start_without_token_sends_signup_message(): void
    {
        $telegram = $this->mock(TelegramService::class);
        $telegram->shouldReceive('sendMessage')
                 ->once()
                 ->withArgs(fn ($chatId, $text) =>
                     str_contains($text, 'Bem-vindo') || str_contains($text, 'assinar')
                 );

        $this->postUpdate($this->makeMessageUpdate('/start'))
             ->assertStatus(200)
             ->assertJson(['ok' => true]);
    }

    // -------------------------------------------------------------------------
    // /start with invalid token
    // -------------------------------------------------------------------------

    public function test_start_with_invalid_token_sends_error_message(): void
    {
        $telegram = $this->mock(TelegramService::class);
        $telegram->shouldReceive('sendMessage')
                 ->once()
                 ->withArgs(fn ($chatId, $text) =>
                     str_contains($text, 'inválido') || str_contains($text, 'expirado')
                 );

        $this->postUpdate($this->makeMessageUpdate('/start INVALIDTOKEN000000000000000000000000'))
             ->assertStatus(200);
    }

    public function test_start_with_expired_token_sends_error_message(): void
    {
        $user = User::factory()->create([
            'telegram_link_token'            => 'expiredtoken123456789012345678901234567',
            'telegram_link_token_expires_at' => now()->subHour(), // Expired
        ]);

        $telegram = $this->mock(TelegramService::class);
        $telegram->shouldReceive('sendMessage')
                 ->once()
                 ->withArgs(fn ($chatId, $text) =>
                     str_contains($text, 'inválido') || str_contains($text, 'expirado')
                 );

        $this->postUpdate($this->makeMessageUpdate('/start expiredtoken123456789012345678901234567'))
             ->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // /start with valid token — linking flow
    // -------------------------------------------------------------------------

    public function test_start_with_valid_token_links_telegram_to_user(): void
    {
        $token = Str::random(40);
        $user  = User::factory()->active()->create([
            'telegram_id'                    => null,
            'telegram_link_token'            => $token,
            'telegram_link_token_expires_at' => now()->addHours(24),
        ]);

        $telegram = $this->mock(TelegramService::class);
        $telegram->shouldReceive('sendMessage')
                 ->once()
                 ->withArgs(fn ($chatId, $text) =>
                     str_contains($text, 'sucesso') || str_contains($text, 'vinculada')
                 );

        $this->postUpdate($this->makeMessageUpdate(
            "/start {$token}",
            chatId  : '555666777',
            userId  : '555666777',
            username: 'farmaciadono',
        ))->assertStatus(200);

        $fresh = $user->fresh();
        $this->assertEquals('555666777', $fresh->telegram_id);
        $this->assertEquals('farmaciadono', $fresh->telegram_username);
        $this->assertNotNull($fresh->telegram_linked_at);
        $this->assertNull($fresh->telegram_link_token);
    }

    public function test_start_with_valid_token_activates_pending_user(): void
    {
        $token = Str::random(40);
        $user  = User::factory()->pending()->create([
            'telegram_id'                    => null,
            'telegram_link_token'            => $token,
            'telegram_link_token_expires_at' => now()->addHours(24),
        ]);

        $this->mock(TelegramService::class)
             ->shouldReceive('sendMessage')->once();

        $this->postUpdate($this->makeMessageUpdate("/start {$token}"));

        $this->assertEquals('active', $user->fresh()->status);
    }

    // -------------------------------------------------------------------------
    // Already linked user
    // -------------------------------------------------------------------------

    public function test_start_already_linked_user_receives_welcome_back(): void
    {
        $user = User::factory()->active()->create([
            'telegram_id' => '111222333',
        ]);

        $telegram = $this->mock(TelegramService::class);
        $telegram->shouldReceive('sendMessage')
                 ->once()
                 ->withArgs(fn ($chatId, $text) =>
                     str_contains($text, 'já está vinculada') || str_contains($text, 'Olá')
                 );

        $this->postUpdate($this->makeMessageUpdate('/start', userId: '111222333'))
             ->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // /ajuda and /help
    // -------------------------------------------------------------------------

    public function test_ajuda_sends_help_message(): void
    {
        $telegram = $this->mock(TelegramService::class);
        $telegram->shouldReceive('sendMessage')
                 ->once()
                 ->withArgs(fn ($chatId, $text) =>
                     str_contains($text, 'Comandos') || str_contains($text, '/start')
                 );

        $this->postUpdate($this->makeMessageUpdate('/ajuda'))
             ->assertStatus(200)
             ->assertJson(['ok' => true]);
    }

    public function test_help_command_sends_help_message(): void
    {
        $telegram = $this->mock(TelegramService::class);
        $telegram->shouldReceive('sendMessage')->once();

        $this->postUpdate($this->makeMessageUpdate('/help'))
             ->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Unknown message
    // -------------------------------------------------------------------------

    public function test_unknown_message_sends_default_response(): void
    {
        $telegram = $this->mock(TelegramService::class);
        $telegram->shouldReceive('sendMessage')
                 ->once()
                 ->withArgs(fn ($chatId, $text) =>
                     str_contains($text, '/ajuda') || str_contains($text, 'comandos')
                 );

        $this->postUpdate($this->makeMessageUpdate('Olá, como vai?'))
             ->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Webhook always returns 200
    // -------------------------------------------------------------------------

    public function test_webhook_returns_200_for_empty_update(): void
    {
        $this->postUpdate(['update_id' => 1234])
             ->assertStatus(200)
             ->assertJson(['ok' => true]);
    }
}
