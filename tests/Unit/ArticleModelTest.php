<?php

namespace Tests\Unit;

use App\Models\Article;
use Tests\TestCase;

class ArticleModelTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Casts
    // -------------------------------------------------------------------------

    public function test_casts_score_as_integer(): void
    {
        $article = Article::factory()->create(['score' => '7']);

        $this->assertIsInt($article->fresh()->score);
        $this->assertEquals(7, $article->fresh()->score);
    }

    public function test_casts_boolean_flags(): void
    {
        $article = Article::factory()->create([
            'alert_sent'     => true,
            'digest_sent'    => false,
            'blog_published' => false,
        ]);

        $fresh = $article->fresh();
        $this->assertIsBool($fresh->alert_sent);
        $this->assertIsBool($fresh->digest_sent);
        $this->assertIsBool($fresh->blog_published);
    }

    public function test_casts_published_at_as_datetime(): void
    {
        $article = Article::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $article->fresh()->published_at);
    }

    // -------------------------------------------------------------------------
    // makeHash
    // -------------------------------------------------------------------------

    public function test_make_hash_is_deterministic(): void
    {
        $hash1 = Article::makeHash('ANVISA altera regras', 'https://anvisa.gov.br/resolucao?page=1#top');
        $hash2 = Article::makeHash('ANVISA altera regras', 'https://anvisa.gov.br/resolucao?page=1#top');

        $this->assertEquals($hash1, $hash2);
    }

    public function test_make_hash_normalizes_title_case(): void
    {
        $hash1 = Article::makeHash('TITLE', 'https://example.com');
        $hash2 = Article::makeHash('title', 'https://example.com');

        $this->assertEquals($hash1, $hash2);
    }

    public function test_make_hash_strips_query_string_and_fragment(): void
    {
        $hash1 = Article::makeHash('Título', 'https://example.com/artigo');
        $hash2 = Article::makeHash('Título', 'https://example.com/artigo?utm_source=email#section');

        $this->assertEquals($hash1, $hash2);
    }

    public function test_make_hash_differs_for_different_titles(): void
    {
        $hash1 = Article::makeHash('Título A', 'https://example.com');
        $hash2 = Article::makeHash('Título B', 'https://example.com');

        $this->assertNotEquals($hash1, $hash2);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function test_scope_for_daily_digest_selects_eligible_articles(): void
    {
        // Eligible: score >= 5, digest_sent=false, published today
        Article::factory()->create([
            'score'        => 6,
            'digest_sent'  => false,
            'published_at' => now(),
        ]);
        Article::factory()->create([
            'score'        => 5,
            'digest_sent'  => false,
            'published_at' => now(),
        ]);

        // Not eligible: already sent
        Article::factory()->create([
            'score'        => 6,
            'digest_sent'  => true,
            'published_at' => now(),
        ]);

        // Not eligible: score too low
        Article::factory()->create([
            'score'        => 3,
            'digest_sent'  => false,
            'published_at' => now(),
        ]);

        // Not eligible: published yesterday
        Article::factory()->create([
            'score'        => 7,
            'digest_sent'  => false,
            'published_at' => now()->subDay(),
        ]);

        $this->assertEquals(2, Article::forDailyDigest()->count());
    }

    public function test_scope_for_alert_selects_high_score_unsent(): void
    {
        Article::factory()->count(3)->create(['score' => 9, 'alert_sent' => false]);
        Article::factory()->create(['score' => 9, 'alert_sent' => true]);
        Article::factory()->create(['score' => 5, 'alert_sent' => false]);

        $this->assertEquals(3, Article::forAlert()->count());
    }
}
