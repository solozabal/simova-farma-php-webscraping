<?php

namespace Tests\Unit;

use App\Models\BlogPost;
use Tests\TestCase;

class BlogPostModelTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Casts
    // -------------------------------------------------------------------------

    public function test_casts_tags_as_array(): void
    {
        $post = BlogPost::factory()->create(['tags' => ['farmácia', 'gestão']]);

        $this->assertIsArray($post->fresh()->tags);
        $this->assertContains('farmácia', $post->fresh()->tags);
    }

    public function test_casts_published_at_as_datetime(): void
    {
        $post = BlogPost::factory()->published()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $post->fresh()->published_at);
    }

    // -------------------------------------------------------------------------
    // Slug generation
    // -------------------------------------------------------------------------

    public function test_slug_is_auto_generated_from_title(): void
    {
        $post = BlogPost::factory()->create([
            'title' => 'Como Aumentar o Giro de Estoque',
            'slug'  => '',
        ]);

        $this->assertStringContainsString('como-aumentar-o-giro-de-estoque', $post->fresh()->slug);
    }

    public function test_generate_unique_slug_avoids_duplicates(): void
    {
        $base = 'gestao-de-estoque';

        BlogPost::factory()->create(['slug' => $base]);

        $slug = BlogPost::generateUniqueSlug('Gestao de estoque');

        $this->assertNotEquals($base, $slug);
        $this->assertStringContainsString($base, $slug);
    }

    public function test_explicit_slug_is_preserved(): void
    {
        $post = BlogPost::factory()->create(['slug' => 'meu-slug-customizado']);

        $this->assertEquals('meu-slug-customizado', $post->fresh()->slug);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function test_scope_published_returns_only_published_posts(): void
    {
        BlogPost::factory()->published()->count(3)->create();
        BlogPost::factory()->count(2)->create(['status' => 'draft']);
        BlogPost::factory()->archived()->create();

        $this->assertEquals(3, BlogPost::published()->count());
    }

    public function test_scope_published_excludes_future_published_at(): void
    {
        BlogPost::factory()->create([
            'status'       => 'published',
            'published_at' => now()->addDay(), // Future
        ]);
        BlogPost::factory()->create([
            'status'       => 'published',
            'published_at' => now()->subHour(), // Past
        ]);

        $this->assertEquals(1, BlogPost::published()->count());
    }

    public function test_scope_published_includes_null_published_at(): void
    {
        BlogPost::factory()->create([
            'status'       => 'published',
            'published_at' => null,
        ]);

        $this->assertEquals(1, BlogPost::published()->count());
    }

    public function test_scope_draft_returns_only_drafts(): void
    {
        BlogPost::factory()->count(2)->create(['status' => 'draft']);
        BlogPost::factory()->published()->create();

        $this->assertEquals(2, BlogPost::draft()->count());
    }
}
