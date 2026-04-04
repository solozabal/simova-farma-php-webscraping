<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Artigo/notícia coletado das fontes externas (RSS, APIs).
 *
 * Score:
 *   >= 8  → alerta imediato via Telegram
 *   5–7   → digest diário (09:00)
 *   < 5   → apenas armazenado
 */
class Article extends Model
{
    /** @use HasFactory<\Database\Factories\ArticleFactory> */
    use HasFactory;
    protected $fillable = [
        'hash',
        'title',
        'content',
        'url',
        'source_key',
        'source_label',
        'category',
        'language',
        'score',
        'impact_label',
        'insight',
        'alert_sent',
        'digest_sent',
        'blog_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'score'         => 'integer',
            'alert_sent'    => 'boolean',
            'digest_sent'   => 'boolean',
            'blog_published'=> 'boolean',
            'published_at'  => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    public function editorialPosts(): HasMany
    {
        return $this->hasMany(EditorialPost::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /** Artigos elegíveis para o digest diário */
    public function scopeForDailyDigest($query)
    {
        return $query->where('score', '>=', 5)
                     ->where('digest_sent', false)
                     ->whereDate('published_at', today());
    }

    /** Artigos para alerta imediato */
    public function scopeForAlert($query)
    {
        return $query->where('score', '>=', 8)
                     ->where('alert_sent', false);
    }

    // -------------------------------------------------------------------------
    // Utilitários
    // -------------------------------------------------------------------------

    /**
     * Gera o hash de deduplicação.
     * sha256 do título normalizado + URL canônica.
     */
    public static function makeHash(string $title, string $url): string
    {
        $normalizedTitle = mb_strtolower(trim(preg_replace('/\s+/', ' ', $title)));
        $canonicalUrl    = preg_replace('/[?#].*$/', '', $url); // remove query string e fragment
        return hash('sha256', $normalizedTitle . $canonicalUrl);
    }
}
