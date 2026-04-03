<?php

namespace App\Console\Commands;

use App\Models\SystemLog;
use Illuminate\Console\Command;

class Cleanup extends Command
{
    protected $signature = 'simova:cleanup';
    protected $description = 'Clean up old system logs and expired data';

    public function handle(): int
    {
        // Delete system logs older than 90 days
        $deleted = SystemLog::where('created_at', '<', now()->subDays(90))->delete();
        $this->info("Deleted {$deleted} old system log entries.");

        return self::SUCCESS;
    }
}
