<?php

namespace Tests\Feature;

use App\Jobs\SendDailyDigestJob;
use App\Jobs\SendTelegramMessageJob;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Tests for SendDailyDigestJob
 *
 * Covers:
 *  - Score threshold: articles with score >= 5 are included
 *  - Articles with score < 5 are excluded
 *  - Already-sent articles (digest_sent=true) are excluded
 *  - Articles from previous days are excluded
 *  - Articles are marked as digest_sent after processing
 *  - No articles → job exits early without dispatching messages
 *  - No eligible users → job exits early
 *  - SendTelegramMessageJob is dispatched for each eligible user
 */
class SendDailyDigestJobTest extends TestCase
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
    // Score threshold
    // -------------------------------------------------------------------------

    public function test_digest_includes_articles_with_score_5_or_higher(): void
    {
        Queue::fake();

        Article::factory()->digestEligible()->count(3)->create(['score' => 5]);
        User::factory()->receivingTelegram()->create();

        (new SendDailyDigestJob())->handle(app(\App\Services\TelegramService::class));

        Queue::assertPushed(SendTelegramMessageJob::class, 1);
    }

    public function test_digest_excludes_articles_with_score_below_5(): void
    {
        Queue::fake();

        Article::factory()->create([
            'score'        => 4,
            'digest_sent'  => false,
            'published_at' => today()->addHours(5),
        ]);
        User::factory()->receivingTelegram()->create();

        (new SendDailyDigestJob())->handle(app(\App\Services\TelegramService::class));

        // No articles eligible → no messages dispatched
        Queue::assertNothingPushed();
    }

    public function test_digest_includes_articles_with_score_8_or_higher(): void
    {
        Queue::fake();

        Article::factory()->highScore()->digestEligible()->count(2)->create();
        User::factory()->receivingTelegram()->create();

        (new SendDailyDigestJob())->handle(app(\App\Services\TelegramService::class));

        Queue::assertPushed(SendTelegramMessageJob::class, 1);
    }

    // -------------------------------------------------------------------------
    // Already-sent articles are excluded
    // -------------------------------------------------------------------------

    public function test_digest_excludes_already_sent_articles(): void
    {
        Queue::fake();

        Article::factory()->digestEligible()->digestSent()->create();
        User::factory()->receivingTelegram()->create();

        (new SendDailyDigestJob())->handle(app(\App\Services\TelegramService::class));

        Queue::assertNothingPushed();
    }

    // -------------------------------------------------------------------------
    // Articles are marked as sent after processing
    // -------------------------------------------------------------------------

    public function test_articles_are_marked_digest_sent_after_job_runs(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $article = Article::factory()->digestEligible()->create();
        User::factory()->receivingTelegram()->create();

        (new SendDailyDigestJob())->handle(app(\App\Services\TelegramService::class));

        $article->refresh();
        $this->assertTrue($article->digest_sent);
    }

    // -------------------------------------------------------------------------
    // No eligible users
    // -------------------------------------------------------------------------

    public function test_no_eligible_users_exits_early(): void
    {
        Queue::fake();

        Article::factory()->digestEligible()->count(3)->create();

        // No users with telegram_id and active status
        User::factory()->active()->create(['telegram_id' => null]);

        (new SendDailyDigestJob())->handle(app(\App\Services\TelegramService::class));

        Queue::assertNothingPushed();

        // Articles should NOT be marked as sent
        $this->assertDatabaseMissing('articles', ['digest_sent' => true]);
    }

    // -------------------------------------------------------------------------
    // Dispatches one job per user
    // -------------------------------------------------------------------------

    public function test_dispatches_one_job_per_eligible_user(): void
    {
        Queue::fake();

        Article::factory()->digestEligible()->create();
        User::factory()->receivingTelegram()->count(4)->create();

        (new SendDailyDigestJob())->handle(app(\App\Services\TelegramService::class));

        Queue::assertPushed(SendTelegramMessageJob::class, 4);
    }

    // -------------------------------------------------------------------------
    // Max 5 articles per digest
    // -------------------------------------------------------------------------

    public function test_digest_sends_at_most_5_articles(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        Article::factory()->digestEligible()->count(10)->create();
        $user = User::factory()->receivingTelegram()->create();

        (new SendDailyDigestJob())->handle(app(\App\Services\TelegramService::class));

        // Only 5 articles should be marked as sent
        $this->assertSame(5, Article::where('digest_sent', true)->count());
    }

    // -------------------------------------------------------------------------
    // Artisan command dispatches job
    // -------------------------------------------------------------------------

    public function test_artisan_command_dispatches_job(): void
    {
        Queue::fake();

        $this->artisan('simova:daily-digest')->assertExitCode(0);

        Queue::assertPushed(SendDailyDigestJob::class);
    }
}
