<?php

namespace Tests\Unit;

use App\Models\EditorialPost;
use Tests\TestCase;

class EditorialPostModelTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Casts
    // -------------------------------------------------------------------------

    public function test_casts_scheduled_at_as_datetime(): void
    {
        $post = EditorialPost::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $post->fresh()->scheduled_at);
    }

    public function test_casts_recipients_count_as_integer(): void
    {
        $post = EditorialPost::factory()->create(['recipients_count' => '42']);

        $this->assertIsInt($post->fresh()->recipients_count);
        $this->assertEquals(42, $post->fresh()->recipients_count);
    }

    // -------------------------------------------------------------------------
    // getMessageText
    // -------------------------------------------------------------------------

    public function test_get_message_text_returns_content_when_set(): void
    {
        $post = EditorialPost::factory()->create([
            'content'          => 'Conteúdo principal',
            'content_fallback' => 'Fallback',
        ]);

        $this->assertEquals('Conteúdo principal', $post->getMessageText());
    }

    public function test_get_message_text_returns_fallback_when_content_empty(): void
    {
        $post = EditorialPost::factory()->create([
            'content'          => '',
            'content_fallback' => 'Texto de fallback',
        ]);

        $this->assertEquals('Texto de fallback', $post->getMessageText());
    }

    public function test_get_message_text_returns_empty_string_when_both_empty(): void
    {
        $post = EditorialPost::factory()->create([
            'content'          => '',
            'content_fallback' => null,
        ]);

        $this->assertEquals('', $post->getMessageText());
    }

    // -------------------------------------------------------------------------
    // markAsSent
    // -------------------------------------------------------------------------

    public function test_mark_as_sent_updates_status_and_sent_at(): void
    {
        $post = EditorialPost::factory()->readyToSend()->create();

        $post->markAsSent(50);

        $fresh = $post->fresh();
        $this->assertEquals('sent', $fresh->status);
        $this->assertNotNull($fresh->sent_at);
        $this->assertEquals(50, $fresh->recipients_count);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function test_scope_ready_to_send_returns_approved_past_scheduled(): void
    {
        // Ready: approved + scheduled in past
        EditorialPost::factory()->readyToSend()->count(2)->create();

        // Not ready: draft
        EditorialPost::factory()->create(['status' => 'draft', 'scheduled_at' => now()->subMinute()]);

        // Not ready: approved but scheduled in future
        EditorialPost::factory()->create(['status' => 'approved', 'scheduled_at' => now()->addHour()]);

        // Not ready: already sent
        EditorialPost::factory()->sent()->create();

        $this->assertEquals(2, EditorialPost::readyToSend()->count());
    }

    public function test_scope_morning_returns_morning_slot_posts(): void
    {
        EditorialPost::factory()->morning()->count(3)->create();
        EditorialPost::factory()->afternoon()->count(2)->create();

        $this->assertEquals(3, EditorialPost::morning()->count());
    }

    public function test_scope_afternoon_returns_afternoon_slot_posts(): void
    {
        EditorialPost::factory()->morning()->count(2)->create();
        EditorialPost::factory()->afternoon()->count(4)->create();

        $this->assertEquals(4, EditorialPost::afternoon()->count());
    }
}
