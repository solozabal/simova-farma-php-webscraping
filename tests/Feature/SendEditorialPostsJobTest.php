<?php

namespace Tests\Feature;

use App\Jobs\SendEditorialPostsJob;
use App\Jobs\SendTelegramMessageJob;
use App\Models\EditorialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Tests for SendEditorialPostsJob
 *
 * Covers:
 *  - Only approved posts with scheduled_at <= now() are sent
 *  - Draft posts are NOT sent
 *  - Future-scheduled posts are NOT sent
 *  - Already-sent posts are NOT sent again
 *  - Posts are marked as 'sent' after processing
 *  - One SendTelegramMessageJob dispatched per user per post
 *  - Posts with empty content are skipped with a warning
 *  - No eligible users → job exits early
 *  - Morning (09:10) and afternoon (17:40) slots are handled
 *  - Artisan command dispatches the job
 */
class SendEditorialPostsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);
    }

    // -------------------------------------------------------------------------
    // Only approved + due posts are sent
    // -------------------------------------------------------------------------

    public function test_sends_approved_posts_with_past_scheduled_at(): void
    {
        Queue::fake();

        EditorialPost::factory()->readyToSend()->count(2)->create();
        User::factory()->receivingTelegram()->count(3)->create();

        (new SendEditorialPostsJob())->handle(app(\App\Services\TelegramService::class));

        // 2 posts × 3 users = 6 jobs
        Queue::assertPushed(SendTelegramMessageJob::class, 6);
    }

    public function test_does_not_send_draft_posts(): void
    {
        Queue::fake();

        EditorialPost::factory()->create(['status' => 'draft', 'scheduled_at' => now()->subMinutes(5)]);
        User::factory()->receivingTelegram()->create();

        (new SendEditorialPostsJob())->handle(app(\App\Services\TelegramService::class));

        Queue::assertNothingPushed();
    }

    public function test_does_not_send_future_scheduled_posts(): void
    {
        Queue::fake();

        EditorialPost::factory()->scheduledFuture()->create();
        User::factory()->receivingTelegram()->create();

        (new SendEditorialPostsJob())->handle(app(\App\Services\TelegramService::class));

        Queue::assertNothingPushed();
    }

    public function test_does_not_resend_already_sent_posts(): void
    {
        Queue::fake();

        EditorialPost::factory()->sent()->create();
        User::factory()->receivingTelegram()->create();

        (new SendEditorialPostsJob())->handle(app(\App\Services\TelegramService::class));

        Queue::assertNothingPushed();
    }

    // -------------------------------------------------------------------------
    // Posts are marked as sent
    // -------------------------------------------------------------------------

    public function test_post_is_marked_as_sent_after_processing(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $post = EditorialPost::factory()->readyToSend()->create();
        User::factory()->receivingTelegram()->count(2)->create();

        (new SendEditorialPostsJob())->handle(app(\App\Services\TelegramService::class));

        $post->refresh();
        $this->assertSame('sent', $post->status);
        $this->assertNotNull($post->sent_at);
        $this->assertSame(2, $post->recipients_count);
    }

    // -------------------------------------------------------------------------
    // Posts with empty content are skipped
    // -------------------------------------------------------------------------

    public function test_post_with_empty_content_is_skipped(): void
    {
        Queue::fake();

        EditorialPost::factory()->readyToSend()->create([
            'content'          => '',
            'content_fallback' => null,
        ]);
        User::factory()->receivingTelegram()->create();

        (new SendEditorialPostsJob())->handle(app(\App\Services\TelegramService::class));

        Queue::assertNothingPushed();
    }

    // -------------------------------------------------------------------------
    // No eligible users
    // -------------------------------------------------------------------------

    public function test_no_eligible_users_exits_without_sending(): void
    {
        Queue::fake();

        $post = EditorialPost::factory()->readyToSend()->create();
        User::factory()->active()->create(['telegram_id' => null]);

        (new SendEditorialPostsJob())->handle(app(\App\Services\TelegramService::class));

        Queue::assertNothingPushed();
        // Post should NOT be marked as sent
        $post->refresh();
        $this->assertSame('approved', $post->status);
    }

    // -------------------------------------------------------------------------
    // Morning and afternoon slots
    // -------------------------------------------------------------------------

    public function test_morning_slot_post_is_sent(): void
    {
        Queue::fake();

        EditorialPost::factory()->readyToSend()->morning()->create();
        User::factory()->receivingTelegram()->create();

        (new SendEditorialPostsJob())->handle(app(\App\Services\TelegramService::class));

        Queue::assertPushed(SendTelegramMessageJob::class, 1);
    }

    public function test_afternoon_slot_post_is_sent(): void
    {
        Queue::fake();

        EditorialPost::factory()->readyToSend()->afternoon()->create();
        User::factory()->receivingTelegram()->create();

        (new SendEditorialPostsJob())->handle(app(\App\Services\TelegramService::class));

        Queue::assertPushed(SendTelegramMessageJob::class, 1);
    }

    // -------------------------------------------------------------------------
    // Multiple posts in same run
    // -------------------------------------------------------------------------

    public function test_multiple_posts_are_all_sent_in_one_run(): void
    {
        Queue::fake();

        $morning   = EditorialPost::factory()->readyToSend()->morning()->create();
        $afternoon = EditorialPost::factory()->readyToSend()->afternoon()->create();
        User::factory()->receivingTelegram()->count(2)->create();

        (new SendEditorialPostsJob())->handle(app(\App\Services\TelegramService::class));

        // 2 posts × 2 users = 4 jobs
        Queue::assertPushed(SendTelegramMessageJob::class, 4);

        $morning->refresh();
        $this->assertSame('sent', $morning->status);

        $afternoon->refresh();
        $this->assertSame('sent', $afternoon->status);
    }

    // -------------------------------------------------------------------------
    // Artisan command dispatches job
    // -------------------------------------------------------------------------

    public function test_artisan_command_dispatches_editorial_posts_job(): void
    {
        Queue::fake();

        $this->artisan('simova:editorial-posts')->assertExitCode(0);

        Queue::assertPushed(SendEditorialPostsJob::class);
    }
}
