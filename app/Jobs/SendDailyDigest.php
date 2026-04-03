<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\SystemLog;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendDailyDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(TelegramService $telegram): void
    {
        $articles = Article::forDigest()
            ->whereDate('published_at', '>=', now()->subDay())
            ->orderByDesc('score')
            ->take(5)
            ->get();

        if ($articles->isEmpty()) {
            SystemLog::log('telegram', 'Daily digest: no articles to send');
            return;
        }

        $text = $this->buildDigestText($articles);

        $activeUsers = User::whereIn('status', ['active', 'test'])
            ->whereNotNull('telegram_id')
            ->get();

        foreach ($activeUsers as $user) {
            TelegramSendMessage::dispatch($user->telegram_id, $text);
        }

        Article::whereIn('id', $articles->pluck('id'))->update(['is_sent_digest' => true]);

        SystemLog::log('telegram', 'Daily digest dispatched', [
            'articles' => $articles->count(),
            'users' => $activeUsers->count(),
        ]);
    }

    private function buildDigestText($articles): string
    {
        $date = now()->setTimezone('America/Sao_Paulo')->format('d/m/Y');
        $lines = ["📦 <b>Digest SIMOVA FARMA — {$date}</b>\n"];

        foreach ($articles as $i => $article) {
            $n = $i + 1;
            $lines[] = "{$n}. <b>{$article->title}</b>";
            if ($article->impact_label) {
                $lines[] = "   ⚡ {$article->impact_label}";
            }
            if ($article->insight) {
                $lines[] = "   💡 {$article->insight}";
            }
            $lines[] = "   🔗 {$article->url}";
            $lines[] = '';
        }

        $lines[] = '─────────────────────';
        $lines[] = 'Gerencie sua assinatura ou acesse suporte: /ajuda';

        return implode("\n", $lines);
    }
}
