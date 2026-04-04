<?php

namespace App\Jobs;

use App\Models\SystemLog;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job para envio assíncrono de uma mensagem Telegram para um único destinatário.
 *
 * Uso:
 *   SendTelegramMessageJob::dispatch($chatId, $text)->onQueue('telegram');
 *
 * O job é enfileirado para evitar:
 *  - Bloqueio síncrono do processo principal
 *  - Estouro do rate limit da API Telegram (30 msg/s)
 *  - Falha em cascata por timeouts de rede
 *
 * Rate limiting:
 *  - throttle() em $tries e $backoff garante reenvio seguro em caso de falha
 */
class SendTelegramMessageJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /** Número máximo de tentativas em caso de falha */
    public int $tries = 3;

    /** Tempo (segundos) entre tentativas: 30s, 60s, 120s */
    public array $backoff = [30, 60, 120];

    /** Timeout máximo do job em segundos */
    public int $timeout = 30;

    public function __construct(
        private readonly string|int $chatId,
        private readonly string $text,
        private readonly string $parseMode = 'HTML',
    ) {}

    public function handle(TelegramService $telegram): void
    {
        $success = $telegram->sendMessage($this->chatId, $this->text, $this->parseMode);

        if (! $success) {
            // Lança exceção para que o job seja recolocado na fila
            throw new \RuntimeException("Falha ao enviar mensagem Telegram para {$this->chatId}");
        }

        SystemLog::info('telegram', "Mensagem enviada para chat_id={$this->chatId}");
    }

    public function failed(\Throwable $exception): void
    {
        SystemLog::error('telegram', "Job falhou ao enviar para {$this->chatId}: " . $exception->getMessage(), [
            'chat_id' => $this->chatId,
            'error'   => $exception->getMessage(),
        ]);
    }
}
