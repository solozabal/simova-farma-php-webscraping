<?php

namespace App\Console\Commands;

use App\Jobs\TelegramSendMessage;
use App\Models\EditorialPost;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Console\Command;

class EditorialRunner extends Command
{
    protected $signature = 'simova:editorial:run {--slot= : Named time slot (morning|afternoon)}';
    protected $description = 'Run editorial scheduler: publish due editorial posts to configured channels';

    public function handle(): int
    {
        $slot = $this->option('slot');
        $this->info("Running editorial runner" . ($slot ? " [slot: {$slot}]" : '') . "...");

        $query = EditorialPost::where('status', 'active');

        if ($slot) {
            // Filter by slot-specific schedule rule
            $query->where('schedule_rule', 'LIKE', "%{$slot}%");
        }

        $posts = $query->get();
        $published = 0;

        foreach ($posts as $post) {
            if (!$post->isDue()) {
                continue;
            }

            try {
                $this->publishPost($post);
                $post->update([
                    'last_run_at' => now(),
                    'next_run_at' => $this->calculateNextRun($post),
                ]);
                $published++;
            } catch (\Throwable $e) {
                SystemLog::log('editorial', "Failed to publish post #{$post->id}", [
                    'error' => $e->getMessage(),
                ], 'error');
            }
        }

        $this->info("Published {$published} editorial posts.");
        return self::SUCCESS;
    }

    private function publishPost(EditorialPost $post): void
    {
        $title = $post->title ?? $post->title_template ?? 'SIMOVA FARMA';
        $content = $post->content ?? $post->content_template ?? '';

        $text = "<b>{$title}</b>\n\n{$content}";

        if (in_array($post->channel, ['telegram', 'both'])) {
            $activeUsers = User::whereIn('status', ['active', 'test'])
                ->whereNotNull('telegram_id')
                ->get();

            foreach ($activeUsers as $user) {
                TelegramSendMessage::dispatch($user->telegram_id, $text);
            }

            SystemLog::log('editorial', "Published editorial post #{$post->id} to Telegram", [
                'users' => $activeUsers->count(),
            ]);
        }
    }

    private function calculateNextRun(EditorialPost $post): ?\Carbon\Carbon
    {
        if ($post->type === 'fixed' && $post->schedule_rule) {
            // Simple daily recurrence: next day at same time
            return now()->addDay();
        }
        return null;
    }
}
