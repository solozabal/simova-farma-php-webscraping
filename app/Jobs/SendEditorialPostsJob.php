<?php

namespace App\Jobs;

use App\Models\EditorialPost;
use App\Models\SystemLog;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job que envia os posts editoriais programados via Telegram.
 *
 * Executado a cada 5 minutos pelo scheduler — verifica posts 'approved'
 * com scheduled_at <= now() e os envia para todos os usuários elegíveis.
 *
 * Slots padrão configurados no scheduler:
 *   09:10 — morning
 *   17:40 — afternoon
 *
 * Um post só é enviado se status='approved'.
 * Após o envio, status muda para 'sent'.
 */
class SendEditorialPostsJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 300;

    public function handle(TelegramService $telegram): void
    {
        // Busca posts aprovados com horário de envio já atingido
        $posts = EditorialPost::readyToSend()->get();

        if ($posts->isEmpty()) {
            return; // Nenhum post pendente
        }

        // Busca usuários elegíveis para receber Telegram
        $users = User::receivingTelegram()->get();

        if ($users->isEmpty()) {
            SystemLog::info('scheduler', 'SendEditorialPostsJob: nenhum usuário elegível.');
            return;
        }

        foreach ($posts as $post) {
            $messageText = $post->getMessageText();

            if (empty(trim($messageText))) {
                SystemLog::warning('scheduler', "Post editorial #{$post->id} sem conteúdo — ignorado.");
                continue;
            }

            // Despacha um job de envio por usuário
            foreach ($users as $user) {
                SendTelegramMessageJob::dispatch($user->telegram_id, $messageText)
                    ->onQueue('telegram');
            }

            // Marca como enviado
            $post->markAsSent($users->count());

            SystemLog::info('scheduler', "Post editorial #{$post->id} ({$post->slot}) enviado para {$users->count()} usuários.", [
                'post_id'         => $post->id,
                'slot'            => $post->slot,
                'recipients_count'=> $users->count(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        SystemLog::error('scheduler', 'SendEditorialPostsJob falhou: ' . $exception->getMessage(), [
            'error' => $exception->getMessage(),
        ]);
    }
}
