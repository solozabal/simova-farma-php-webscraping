<?php

namespace Tests\Feature;

use App\Jobs\SendTelegramMessageJob;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests for SendTelegramMessageJob
 *
 * Covers:
 *  - handle() success: message sent, no exception
 *  - handle() failure: RuntimeException thrown (for retry)
 *  - failed(): logs error to system_logs
 */
class SendTelegramMessageJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_sends_message_successfully(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        // Should not throw
        $job = new SendTelegramMessageJob('123456789', 'Mensagem de teste');
        $job->handle(app(\App\Services\TelegramService::class));

        $this->assertDatabaseHas('system_logs', [
            'channel' => 'telegram',
            'level'   => 'info',
        ]);
    }

    public function test_handle_throws_runtime_exception_on_failure(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Bad Request'], 400),
        ]);

        $job = new SendTelegramMessageJob('123456789', 'Mensagem de teste');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Falha ao enviar mensagem Telegram/');

        $job->handle(app(\App\Services\TelegramService::class));
    }

    public function test_failed_logs_error_to_system_log(): void
    {
        $job = new SendTelegramMessageJob('123456789', 'Mensagem de teste');

        $job->failed(new \RuntimeException('Timeout'));

        $this->assertDatabaseHas('system_logs', [
            'channel' => 'telegram',
            'level'   => 'error',
        ]);
    }
}
