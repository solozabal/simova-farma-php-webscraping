<?php

namespace Tests\Unit\Models;

use App\Models\EditorialPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorialPostTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // getMessageText
    // -------------------------------------------------------------------------

    public function test_get_message_text_returns_content_when_set(): void
    {
        $post = EditorialPost::factory()->make([
            'content'          => 'Conteúdo principal',
            'content_fallback' => 'Conteúdo fallback',
        ]);

        $this->assertSame('Conteúdo principal', $post->getMessageText());
    }

    public function test_get_message_text_returns_fallback_when_content_is_empty(): void
    {
        $post = EditorialPost::factory()->make([
            'content'          => '',
            'content_fallback' => 'Conteúdo fallback',
        ]);

        $this->assertSame('Conteúdo fallback', $post->getMessageText());
    }

    public function test_get_message_text_returns_empty_string_when_both_are_empty(): void
    {
        $post = EditorialPost::factory()->make([
            'content'          => '',
            'content_fallback' => null,
        ]);

        $this->assertSame('', $post->getMessageText());
    }

    // -------------------------------------------------------------------------
    // markAsSent
    // -------------------------------------------------------------------------

    public function test_mark_as_sent_updates_status_and_sent_at(): void
    {
        $post = EditorialPost::factory()->approved()->create();

        $post->markAsSent(42);

        $post->refresh();
        $this->assertSame('sent', $post->status);
        $this->assertNotNull($post->sent_at);
        $this->assertSame(42, $post->recipients_count);
    }

    public function test_mark_as_sent_with_zero_recipients(): void
    {
        $post = EditorialPost::factory()->approved()->create();

        $post->markAsSent(0);

        $post->refresh();
        $this->assertSame('sent', $post->status);
        $this->assertSame(0, $post->recipients_count);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function test_scope_ready_to_send_returns_approved_posts_with_past_scheduled_at(): void
    {
        EditorialPost::factory()->readyToSend()->count(2)->create();
        EditorialPost::factory()->create(['status' => 'draft', 'scheduled_at' => now()->subMinutes(5)]);
        EditorialPost::factory()->scheduledFuture()->create();
        EditorialPost::factory()->sent()->create();

        $ready = EditorialPost::readyToSend()->get();

        $this->assertCount(2, $ready);
        foreach ($ready as $post) {
            $this->assertSame('approved', $post->status);
            $this->assertTrue($post->scheduled_at->isPast());
        }
    }

    public function test_scope_morning_returns_only_morning_slot_posts(): void
    {
        EditorialPost::factory()->morning()->count(3)->create();
        EditorialPost::factory()->afternoon()->count(2)->create();

        $morning = EditorialPost::morning()->get();

        $this->assertCount(3, $morning);
        foreach ($morning as $post) {
            $this->assertSame('morning', $post->slot);
        }
    }

    public function test_scope_afternoon_returns_only_afternoon_slot_posts(): void
    {
        EditorialPost::factory()->morning()->count(2)->create();
        EditorialPost::factory()->afternoon()->count(3)->create();

        $afternoon = EditorialPost::afternoon()->get();

        $this->assertCount(3, $afternoon);
        foreach ($afternoon as $post) {
            $this->assertSame('afternoon', $post->slot);
        }
    }

    // -------------------------------------------------------------------------
    // Slot constants match expected schedule times
    // -------------------------------------------------------------------------

    public function test_morning_slot_corresponds_to_09_10(): void
    {
        // The morning slot is scheduled at 09:10 America/Sao_Paulo — verify the slot value is correct
        $post = EditorialPost::factory()->morning()->create([
            'scheduled_at' => today()->setTime(9, 10)->timezone('America/Sao_Paulo'),
        ]);

        $this->assertSame('morning', $post->slot);
        $this->assertSame(9, (int) $post->scheduled_at->setTimezone('America/Sao_Paulo')->format('G'));
        $this->assertSame(10, (int) $post->scheduled_at->setTimezone('America/Sao_Paulo')->format('i'));
    }

    public function test_afternoon_slot_corresponds_to_17_40(): void
    {
        // The afternoon slot is scheduled at 17:40 America/Sao_Paulo
        $post = EditorialPost::factory()->afternoon()->create([
            'scheduled_at' => today()->setTime(17, 40)->timezone('America/Sao_Paulo'),
        ]);

        $this->assertSame('afternoon', $post->slot);
        $this->assertSame(17, (int) $post->scheduled_at->setTimezone('America/Sao_Paulo')->format('G'));
        $this->assertSame(40, (int) $post->scheduled_at->setTimezone('America/Sao_Paulo')->format('i'));
    }
}
