<?php

namespace App\Console\Commands;

use App\Jobs\SendDailyDigest;
use Illuminate\Console\Command;

class SendDigest extends Command
{
    protected $signature = 'simova:digest';
    protected $description = 'Dispatch daily digest to all active Telegram subscribers';

    public function handle(): int
    {
        $this->info('Dispatching daily digest...');
        SendDailyDigest::dispatch();
        $this->info('Daily digest job dispatched.');
        return self::SUCCESS;
    }
}
