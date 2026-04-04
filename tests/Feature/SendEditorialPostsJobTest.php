<?php

namespace Tests\Feature;

use App\Jobs\SendEditorialPostsJob;
use App\Jobs\SendTelegramMessageJob;
use App\Models\EditorialPost;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendEditorialPostsJobTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Job behavior
    // -------------------------------------------------------------------------

    public function test_job_dispatches_messages_for_ready_posts_and_eligible_users(): void
    {
        Queue::fake();

        $post  = EditorialPost::factory()->readyToSend()->create(['content' => 'Mensagem editorial de teste.']);
        $users = User::factory()->receivingTelegram()->count(3)->create();

        $telegram = $this->mock(TelegramService::class);

        (new SendEditorialPostsJob())->handle($telegram);

        $this->assertEquals(3, Queue::pushed(SendTelegramMessageJob::class)->count());
    }

    public function test_job_marks_post_as_sent(): void
    {
        Queue::fake();

        $post = EditorialPost::factory()->readyToSend()->create(['content' => 'Conteúdo.']);
        User::factory()->receivingTelegram()->count(2)->create();

        $telegram = $this->mock(TelegramService::class);

        (new SendEditorialPostsJob())->handle($telegram);

        $fresh = $post->fresh();
        $this->assertEquals('sent', $fresh->status);
        $this->assertNotNull($fresh->sent_at);
        $this->assertEquals(2, $fresh->recipients_count);
    }

    public function test_job_does_nothing_when_no_ready_posts(): void
    {
        Queue::fake();

        // Post in future (not ready yet)
        EditorialPost::factory()->create([
            'status'       => 'approved',
            'scheduled_at' => now()->addHour(),
            'content'      => 'Post futuro.',
        ]);

        User::factory()->receivingTelegram()->create();

        $telegram = $this->mock(TelegramService::class);

        (new SendEditorialPostsJob())->handle($telegram);

        Queue::assertNothingPushed();
    }

    public function test_job_skips_post_with_empty_content(): void
    {
        Queue::fake();

        EditorialPost::factory()->readyToSend()->create([
            'content'          => '',
            'content_fallback' => null,
        ]);

        User::factory()->receivingTelegram()->create();

        $telegram = $this->mock(TelegramService::class);

        (new SendEditorialPostsJob())->handle($telegram);

        Queue::assertNothingPushed();
    }

    public function test_job_does_nothing_when_no_eligible_users(): void
    {
        Queue::fake();

        EditorialPost::factory()->readyToSend()->create(['content' => 'Conteúdo editorial.']);

        // Users without telegram
        User::factory()->active()->create(['telegram_id' => null]);

        $telegram = $this->mock(TelegramService::class);

        (new SendEditorialPostsJob())->handle($telegram);

        Queue::assertNothingPushed();
    }

    public function test_job_handles_multiple_ready_posts(): void
    {
        Queue::fake();

        EditorialPost::factory()->readyToSend()->count(3)->create(['content' => 'Post editorial.']);
        User::factory()->receivingTelegram()->count(2)->create();

        $telegram = $this->mock(TelegramService::class);

        (new SendEditorialPostsJob())->handle($telegram);

        // 3 posts × 2 users = 6 jobs
        $this->assertEquals(6, Queue::pushed(SendTelegramMessageJob::class)->count());
    }
}
