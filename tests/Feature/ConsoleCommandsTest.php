<?php

namespace Tests\Feature;

use App\Jobs\SendDailyDigestJob;
use App\Jobs\SendEditorialPostsJob;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ConsoleCommandsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // simova:daily-digest
    // -------------------------------------------------------------------------

    public function test_daily_digest_command_dispatches_job(): void
    {
        Queue::fake();

        $this->artisan('simova:daily-digest')
             ->assertExitCode(0);

        Queue::assertPushedOn('telegram', SendDailyDigestJob::class);
    }

    public function test_daily_digest_command_outputs_success_messages(): void
    {
        Queue::fake();

        $this->artisan('simova:daily-digest')
             ->expectsOutputToContain('Despachando SendDailyDigestJob')
             ->expectsOutputToContain('Job despachado com sucesso')
             ->assertExitCode(0);
    }

    // -------------------------------------------------------------------------
    // simova:editorial-posts
    // -------------------------------------------------------------------------

    public function test_editorial_posts_command_dispatches_job(): void
    {
        Queue::fake();

        $this->artisan('simova:editorial-posts')
             ->assertExitCode(0);

        Queue::assertPushedOn('telegram', SendEditorialPostsJob::class);
    }

    public function test_editorial_posts_command_outputs_success_message(): void
    {
        Queue::fake();

        $this->artisan('simova:editorial-posts')
             ->expectsOutputToContain('Despachando SendEditorialPostsJob')
             ->assertExitCode(0);
    }
}
