<?php

namespace App\Jobs;

use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TelegramSendMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        private readonly string|int $chatId,
        private readonly string $text,
        private readonly array $options = []
    ) {}

    public function handle(TelegramService $telegram): void
    {
        $telegram->sendMessage($this->chatId, $this->text, $this->options);
    }
}
