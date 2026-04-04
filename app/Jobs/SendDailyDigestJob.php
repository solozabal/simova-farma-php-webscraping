<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\SystemLog;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Job que envia o digest diário de artigos via Telegram.
 *
 * Seleção: artigos com score >= 5 publicados hoje, ainda não enviados no digest.
 * Agendamento: 09:00 (America/Sao_Paulo) via scheduler
 *
 * Para cada usuário elegível (status active ou test com telegram_id):
 *   - Formata o digest com até 5 artigos do dia
 *   - Despacha SendTelegramMessageJob para cada usuário (via fila)
 *   - Marca os artigos como digest_sent=true
 */
class SendDailyDigestJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 300; // 5 minutos

    public function handle(TelegramService $telegram): void
    {
        // 1. Busca os artigos elegíveis do dia (score >= 5, não enviados ainda)
        $articles = Article::forDailyDigest()
            ->orderByDesc('score')
            ->limit(5)
            ->get();

        if ($articles->isEmpty()) {
            SystemLog::info('scheduler', 'Digest diário: nenhum artigo elegível hoje.');
            return;
        }

        // 2. Formata os itens do digest
        $items = $articles->map(fn ($article) => $telegram->formatDigestItem(
            title      : $article->title,
            impactLabel: $article->impact_label ?? '',
            insight    : $article->insight ?? 'Sem insight disponível.',
            url        : $article->url,
        ))->all();

        $digestText = $telegram->formatDailyDigest(
            $items,
            now()->translatedFormat('d/m/Y')
        );

        // 3. Busca usuários elegíveis para receber Telegram
        $users = User::receivingTelegram()->get();

        if ($users->isEmpty()) {
            SystemLog::info('scheduler', 'Digest diário: nenhum usuário elegível para receber.');
            return;
        }

        // 4. Despacha um job de envio por usuário (respeita rate limit via fila)
        foreach ($users as $user) {
            SendTelegramMessageJob::dispatch($user->telegram_id, $digestText)
                ->onQueue('telegram')
                ->delay(now());
        }

        // 5. Marca artigos como enviados no digest
        Article::whereIn('id', $articles->pluck('id'))
            ->update(['digest_sent' => true]);

        SystemLog::info('scheduler', "Digest diário enviado para {$users->count()} usuários.", [
            'articles_count' => $articles->count(),
            'users_count'    => $users->count(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        SystemLog::error('scheduler', 'SendDailyDigestJob falhou: ' . $exception->getMessage(), [
            'error' => $exception->getMessage(),
        ]);
    }
}
