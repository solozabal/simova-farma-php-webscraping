<?php

namespace Tests\Unit\Models;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // makeHash
    // -------------------------------------------------------------------------

    public function test_make_hash_returns_sha256_string(): void
    {
        $hash = Article::makeHash('Título teste', 'https://example.com/artigo');

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    public function test_make_hash_is_deterministic(): void
    {
        $hash1 = Article::makeHash('Título Teste', 'https://example.com/artigo');
        $hash2 = Article::makeHash('Título Teste', 'https://example.com/artigo');

        $this->assertSame($hash1, $hash2);
    }

    public function test_make_hash_normalizes_title_case(): void
    {
        $hash1 = Article::makeHash('TÍTULO TESTE', 'https://example.com/artigo');
        $hash2 = Article::makeHash('título teste', 'https://example.com/artigo');

        $this->assertSame($hash1, $hash2);
    }

    public function test_make_hash_strips_query_string_from_url(): void
    {
        $hash1 = Article::makeHash('Título', 'https://example.com/artigo?utm_source=test');
        $hash2 = Article::makeHash('Título', 'https://example.com/artigo');

        $this->assertSame($hash1, $hash2);
    }

    public function test_make_hash_strips_fragment_from_url(): void
    {
        $hash1 = Article::makeHash('Título', 'https://example.com/artigo#section');
        $hash2 = Article::makeHash('Título', 'https://example.com/artigo');

        $this->assertSame($hash1, $hash2);
    }

    public function test_different_titles_produce_different_hashes(): void
    {
        $hash1 = Article::makeHash('Título A', 'https://example.com/artigo');
        $hash2 = Article::makeHash('Título B', 'https://example.com/artigo');

        $this->assertNotSame($hash1, $hash2);
    }

    // -------------------------------------------------------------------------
    // Scope: forDailyDigest
    // -------------------------------------------------------------------------

    public function test_scope_for_daily_digest_returns_articles_with_score_gte_5_published_today(): void
    {
        Article::factory()->digestEligible()->count(3)->create();
        Article::factory()->lowScore()->create(['published_at' => today()->addHours(5)]);
        Article::factory()->digestSent()->create(['score' => 7, 'published_at' => today()->addHours(5)]);

        $result = Article::forDailyDigest()->get();

        $this->assertCount(3, $result);
        foreach ($result as $article) {
            $this->assertGreaterThanOrEqual(5, $article->score);
            $this->assertFalse($article->digest_sent);
        }
    }

    public function test_scope_for_daily_digest_excludes_already_sent_articles(): void
    {
        Article::factory()->digestEligible()->create();
        Article::factory()->digestEligible()->digestSent()->create();

        $result = Article::forDailyDigest()->get();

        $this->assertCount(1, $result);
    }

    public function test_scope_for_daily_digest_excludes_articles_from_other_days(): void
    {
        Article::factory()->digestEligible()->create(['published_at' => today()->addHours(5)]);
        Article::factory()->digestEligible()->create(['published_at' => now()->subDays(2)]);

        $result = Article::forDailyDigest()->get();

        $this->assertCount(1, $result);
    }

    // -------------------------------------------------------------------------
    // Scope: forAlert
    // -------------------------------------------------------------------------

    public function test_scope_for_alert_returns_articles_with_score_gte_8_not_sent(): void
    {
        Article::factory()->alertEligible()->count(2)->create();
        Article::factory()->digestScore()->create(['alert_sent' => false]);
        Article::factory()->alertEligible()->create(['alert_sent' => true]);

        $result = Article::forAlert()->get();

        $this->assertCount(2, $result);
        foreach ($result as $article) {
            $this->assertGreaterThanOrEqual(8, $article->score);
            $this->assertFalse($article->alert_sent);
        }
    }

    // -------------------------------------------------------------------------
    // Unique constraint on hash
    // -------------------------------------------------------------------------

    public function test_hash_must_be_unique(): void
    {
        $hash = Article::makeHash('Título único', 'https://example.com/unico');
        Article::factory()->create(['hash' => $hash]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Article::factory()->create(['hash' => $hash]);
    }
}
