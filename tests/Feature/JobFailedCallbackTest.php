<?php

namespace Tests\Feature;

use App\Jobs\SendDailyDigestJob;
use App\Jobs\SendEditorialPostsJob;
use App\Models\SystemLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for failed() callbacks in jobs.
 * These are called when the job exhausts all retries.
 */
class JobFailedCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_daily_digest_job_failed_logs_error(): void
    {
        $job       = new SendDailyDigestJob();
        $exception = new \RuntimeException('Timeout na API do Telegram');

        $job->failed($exception);

        $this->assertDatabaseHas('system_logs', [
            'channel' => 'scheduler',
            'level'   => 'error',
        ]);
    }

    public function test_send_editorial_posts_job_failed_logs_error(): void
    {
        $job       = new SendEditorialPostsJob();
        $exception = new \RuntimeException('Queue connection lost');

        $job->failed($exception);

        $this->assertDatabaseHas('system_logs', [
            'channel' => 'scheduler',
            'level'   => 'error',
        ]);
    }
}
