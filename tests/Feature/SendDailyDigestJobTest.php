<?php

namespace Tests\Feature;

use App\Jobs\SendDailyDigestJob;
use App\Jobs\SendTelegramMessageJob;
use App\Models\Article;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendDailyDigestJobTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Job behavior
    // -------------------------------------------------------------------------

    public function test_job_dispatches_messages_for_eligible_users_and_articles(): void
    {
        Queue::fake();

        // 3 eligible articles (score >= 5, not sent, today)
        Article::factory()->count(3)->create([
            'score'        => 6,
            'digest_sent'  => false,
            'published_at' => now(),
            'insight'      => 'Insight de teste.',
        ]);

        // 2 users receiving telegram
        User::factory()->receivingTelegram()->count(2)->create();

        $telegram = $this->mock(TelegramService::class);
        $telegram->shouldReceive('formatDigestItem')->times(3)->andReturn('item');
        $telegram->shouldReceive('formatDailyDigest')->once()->andReturn('digest text');

        (new SendDailyDigestJob())->handle($telegram);

        Queue::assertPushedOn('telegram', SendTelegramMessageJob::class);
        $this->assertEquals(2, Queue::pushed(SendTelegramMessageJob::class)->count());
    }

    public function test_job_marks_articles_as_digest_sent(): void
    {
        Queue::fake();

        $articles = Article::factory()->count(2)->create([
            'score'        => 7,
            'digest_sent'  => false,
            'published_at' => now(),
        ]);

        User::factory()->receivingTelegram()->create();

        $telegram = $this->mock(TelegramService::class);
        $telegram->shouldReceive('formatDigestItem')->andReturn('item');
        $telegram->shouldReceive('formatDailyDigest')->andReturn('digest');

        (new SendDailyDigestJob())->handle($telegram);

        foreach ($articles as $article) {
            $this->assertTrue($article->fresh()->digest_sent);
        }
    }

    public function test_job_does_nothing_when_no_eligible_articles(): void
    {
        Queue::fake();

        // Articles published yesterday (not eligible)
        Article::factory()->count(3)->create([
            'score'        => 7,
            'digest_sent'  => false,
            'published_at' => now()->subDay(),
        ]);

        $telegram = $this->mock(TelegramService::class);
        $telegram->shouldNotReceive('formatDailyDigest');

        (new SendDailyDigestJob())->handle($telegram);

        Queue::assertNothingPushed();
    }

    public function test_job_does_nothing_when_no_eligible_users(): void
    {
        Queue::fake();

        Article::factory()->create([
            'score'        => 8,
            'digest_sent'  => false,
            'published_at' => now(),
        ]);

        // User with no telegram_id
        User::factory()->active()->create(['telegram_id' => null]);

        $telegram = $this->mock(TelegramService::class);
        $telegram->shouldReceive('formatDigestItem')->andReturn('item');
        $telegram->shouldReceive('formatDailyDigest')->andReturn('digest');

        (new SendDailyDigestJob())->handle($telegram);

        Queue::assertNothingPushed();
    }

    public function test_job_selects_at_most_5_articles(): void
    {
        Queue::fake();

        // 7 eligible articles
        Article::factory()->count(7)->create([
            'score'        => 6,
            'digest_sent'  => false,
            'published_at' => now(),
        ]);

        User::factory()->receivingTelegram()->create();

        $calls = 0;
        $telegram = $this->mock(TelegramService::class);
        $telegram->shouldReceive('formatDigestItem')
                 ->andReturnUsing(function () use (&$calls) {
                     $calls++;
                     return 'item';
                 });
        $telegram->shouldReceive('formatDailyDigest')->andReturn('digest');

        (new SendDailyDigestJob())->handle($telegram);

        $this->assertEquals(5, $calls);
    }
}
