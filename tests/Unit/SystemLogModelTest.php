<?php

namespace Tests\Unit;

use App\Models\SystemLog;
use App\Models\User;
use Tests\TestCase;

class SystemLogModelTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Casts
    // -------------------------------------------------------------------------

    public function test_casts_context_as_array(): void
    {
        $log = SystemLog::info('app', 'Test', ['key' => 'value']);

        $this->assertIsArray($log->fresh()->context);
        $this->assertEquals('value', $log->fresh()->context['key']);
    }

    public function test_context_null_when_empty_array(): void
    {
        $log = SystemLog::info('app', 'No context');

        $this->assertNull($log->fresh()->context);
    }

    // -------------------------------------------------------------------------
    // Static factory methods
    // -------------------------------------------------------------------------

    public function test_info_creates_log_with_correct_level(): void
    {
        $log = SystemLog::info('telegram', 'Mensagem enviada.', ['user_id' => 1]);

        $this->assertEquals('info', $log->level);
        $this->assertEquals('telegram', $log->channel);
        $this->assertEquals('Mensagem enviada.', $log->message);
        $this->assertEquals(['user_id' => 1], $log->context);
    }

    public function test_warning_creates_log_with_warning_level(): void
    {
        $log = SystemLog::warning('mercadopago', 'Assinatura não encontrada.');

        $this->assertEquals('warning', $log->level);
        $this->assertEquals('mercadopago', $log->channel);
    }

    public function test_error_creates_log_with_error_level(): void
    {
        $log = SystemLog::error('scheduler', 'Job falhou.', ['error' => 'Timeout']);

        $this->assertEquals('error', $log->level);
        $this->assertEquals('scheduler', $log->channel);
    }

    public function test_info_with_user_id_associates_user(): void
    {
        $user = User::factory()->create();
        $log  = SystemLog::info('app', 'User action', [], $user->id);

        $this->assertEquals($user->id, $log->user_id);
        $this->assertNotNull($log->user);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function test_scope_by_channel_filters_correctly(): void
    {
        SystemLog::factory()->telegram()->count(3)->create();
        SystemLog::factory()->mercadopago()->count(2)->create();

        $this->assertEquals(3, SystemLog::byChannel('telegram')->count());
        $this->assertEquals(2, SystemLog::byChannel('mercadopago')->count());
    }

    public function test_scope_by_level_filters_correctly(): void
    {
        SystemLog::factory()->error()->count(2)->create();
        SystemLog::factory()->info()->count(4)->create();
        SystemLog::factory()->warning()->create();

        $this->assertEquals(2, SystemLog::byLevel('error')->count());
        $this->assertEquals(4, SystemLog::byLevel('info')->count());
    }

    public function test_scope_errors_returns_error_logs(): void
    {
        SystemLog::factory()->error()->count(3)->create();
        SystemLog::factory()->info()->count(2)->create();

        $this->assertEquals(3, SystemLog::errors()->count());
    }

    // -------------------------------------------------------------------------
    // No updated_at
    // -------------------------------------------------------------------------

    public function test_log_has_no_updated_at_column(): void
    {
        $log = SystemLog::factory()->create();

        $this->assertNull($log->updated_at);
    }
}
