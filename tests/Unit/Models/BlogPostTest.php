<?php

namespace Tests\Unit\Models;

use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogPostTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // generateUniqueSlug
    // -------------------------------------------------------------------------

    public function test_generate_unique_slug_creates_slug_from_title(): void
    {
        $slug = BlogPost::generateUniqueSlug('Gestão de Farmácia: 3 Dicas Práticas');

        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $slug);
        $this->assertStringContainsString('gestao', $slug);
    }

    public function test_generate_unique_slug_appends_counter_for_duplicates(): void
    {
        BlogPost::factory()->create(['slug' => 'dicas-para-farmacia-1234']);

        // Re-use same base slug
        $slug = BlogPost::generateUniqueSlug('Dicas Para Farmacia 1234');

        $this->assertNotSame('dicas-para-farmacia-1234', $slug);
    }

    public function test_boot_auto_generates_slug_on_create(): void
    {
        $post = BlogPost::factory()->make(['slug' => null, 'title' => 'Post Com Slug Automático']);

        // Override to let boot generate it
        $post->slug = null;
        $post->save();

        $this->assertNotNull($post->fresh()->slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $post->fresh()->slug);
    }

    public function test_slug_must_be_unique(): void
    {
        BlogPost::factory()->create(['slug' => 'slug-unico-test']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        BlogPost::factory()->create(['slug' => 'slug-unico-test']);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function test_scope_published_returns_published_posts(): void
    {
        BlogPost::factory()->published()->count(2)->create();
        BlogPost::factory()->create(['status' => 'draft']);
        BlogPost::factory()->archived()->create();

        $published = BlogPost::published()->get();

        $this->assertCount(2, $published);
        foreach ($published as $post) {
            $this->assertSame('published', $post->status);
        }
    }

    public function test_scope_draft_returns_only_draft_posts(): void
    {
        BlogPost::factory()->create(['status' => 'draft']);
        BlogPost::factory()->published()->create();

        $drafts = BlogPost::draft()->get();

        $this->assertCount(1, $drafts);
        $this->assertSame('draft', $drafts->first()->status);
    }

    public function test_scope_published_excludes_future_published_at(): void
    {
        BlogPost::factory()->create([
            'status'       => 'published',
            'published_at' => now()->addDay(), // future
        ]);

        $published = BlogPost::published()->get();

        $this->assertCount(0, $published);
    }

    // -------------------------------------------------------------------------
    // Author relationship
    // -------------------------------------------------------------------------

    public function test_author_relationship_returns_user(): void
    {
        $author = \App\Models\User::factory()->admin()->create();
        $post   = BlogPost::factory()->create(['author_id' => $author->id]);

        $this->assertInstanceOf(\App\Models\User::class, $post->author);
        $this->assertSame($author->id, $post->author->id);
    }

    // -------------------------------------------------------------------------
    // Casts
    // -------------------------------------------------------------------------

    public function test_tags_cast_as_array(): void
    {
        $post = BlogPost::factory()->create(['tags' => ['farmacia', 'varejo', 'gestao']]);

        $post->refresh();
        $this->assertIsArray($post->tags);
        $this->assertContains('farmacia', $post->tags);
    }
}
