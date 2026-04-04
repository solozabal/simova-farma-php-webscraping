<?php

namespace Tests\Unit\Models;

use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemLogTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Static factory methods
    // -------------------------------------------------------------------------

    public function test_info_creates_log_with_correct_level_and_channel(): void
    {
        $log = SystemLog::info('telegram', 'Mensagem enviada com sucesso.', ['chat_id' => '123']);

        $this->assertDatabaseHas('system_logs', [
            'level'   => 'info',
            'channel' => 'telegram',
            'message' => 'Mensagem enviada com sucesso.',
        ]);
        $this->assertSame('info', $log->level);
        $this->assertSame('telegram', $log->channel);
    }

    public function test_warning_creates_log_with_warning_level(): void
    {
        $log = SystemLog::warning('mercadopago', 'Assinatura pausada.');

        $this->assertSame('warning', $log->level);
        $this->assertSame('mercadopago', $log->channel);
    }

    public function test_error_creates_log_with_error_level(): void
    {
        $log = SystemLog::error('scheduler', 'Job falhou.', ['error' => 'timeout']);

        $this->assertSame('error', $log->level);
        $this->assertSame('scheduler', $log->channel);
    }

    public function test_log_stores_context_as_json(): void
    {
        $context = ['user_id' => 42, 'action' => 'ativação'];
        $log     = SystemLog::info('app', 'Usuário ativado.', $context);

        $log->refresh();
        $this->assertIsArray($log->context);
        $this->assertSame(42, $log->context['user_id']);
        $this->assertSame('ativação', $log->context['action']);
    }

    public function test_log_null_context_when_empty_array(): void
    {
        $log = SystemLog::info('app', 'Sem contexto.');

        $log->refresh();
        $this->assertNull($log->context);
    }

    public function test_log_with_user_id(): void
    {
        $user = User::factory()->create();
        $log  = SystemLog::info('telegram', 'Link de vinculação gerado.', [], $user->id);

        $this->assertSame($user->id, $log->user_id);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function test_scope_by_channel_filters_correctly(): void
    {
        SystemLog::info('telegram', 'Log Telegram 1.');
        SystemLog::info('telegram', 'Log Telegram 2.');
        SystemLog::info('mercadopago', 'Log MP.');

        $telegramLogs = SystemLog::byChannel('telegram')->get();

        $this->assertCount(2, $telegramLogs);
    }

    public function test_scope_by_level_filters_correctly(): void
    {
        SystemLog::info('app', 'Info log.');
        SystemLog::warning('app', 'Warning log.');
        SystemLog::error('app', 'Error log.');

        $warnings = SystemLog::byLevel('warning')->get();

        $this->assertCount(1, $warnings);
        $this->assertSame('warning', $warnings->first()->level);
    }

    public function test_scope_errors_returns_only_errors(): void
    {
        SystemLog::info('app', 'Info.');
        SystemLog::error('app', 'Error 1.');
        SystemLog::error('app', 'Error 2.');

        $errors = SystemLog::errors()->get();

        $this->assertCount(2, $errors);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function test_user_relationship(): void
    {
        $user = User::factory()->create();
        $log  = SystemLog::info('app', 'Log com usuário.', [], $user->id);

        $this->assertInstanceOf(User::class, $log->user);
        $this->assertSame($user->id, $log->user->id);
    }

    // -------------------------------------------------------------------------
    // No updated_at
    // -------------------------------------------------------------------------

    public function test_system_log_has_no_updated_at(): void
    {
        $log = SystemLog::info('app', 'Teste.');

        $this->assertNull(SystemLog::UPDATED_AT);
    }
}
