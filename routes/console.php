<?php

use App\Console\Commands\EditorialRunner;
use App\Console\Commands\SendDigest;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Schedule — Timezone: America/Sao_Paulo
|--------------------------------------------------------------------------
*/

// Daily digest at 09:00 America/Sao_Paulo
Schedule::command('simova:digest')
    ->dailyAt('09:00')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping();

// Editorial runner every 5 minutes (publishes scheduled editorial posts)
Schedule::command('simova:editorial:run')
    ->everyFiveMinutes()
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping();

// Specific editorial slots: 09:10 and 17:40
Schedule::command('simova:editorial:run --slot=morning')
    ->dailyAt('09:10')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping();

Schedule::command('simova:editorial:run --slot=afternoon')
    ->dailyAt('17:40')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping();

// Auto-publish scheduled blog posts (every minute)
Schedule::command('simova:blog:publish')
    ->everyMinute()
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping();

// Cleanup old system logs (weekly, Sunday 23:00)
Schedule::command('simova:cleanup')
    ->weeklyOn(0, '23:00')
    ->timezone('America/Sao_Paulo');
