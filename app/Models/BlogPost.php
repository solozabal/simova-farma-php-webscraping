<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Post do blog público.
 *
 * IMPORTANTE: Posts são criados como 'draft' e publicados SOMENTE após
 * aprovação manual de um administrador. Não há publicação automática.
 *
 * Status:
 *   draft     → rascunho (não visível publicamente)
 *   published → publicado (visível no blog)
 *   archived  → arquivado (não visível)
 */
class BlogPost extends Model
{
    /** @use HasFactory<\Database\Factories\BlogPostFactory> */
    use HasFactory;
    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'meta_title',
        'meta_description',
        'category',
        'tags',
        'featured_image',
        'author_id',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'tags'         => 'array',
            'published_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /** Posts publicados e visíveis publicamente */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                     ->where(function ($q) {
                         $q->whereNull('published_at')
                           ->orWhere('published_at', '<=', now());
                     });
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    // -------------------------------------------------------------------------
    // Boot: gera slug automaticamente a partir do título
    // -------------------------------------------------------------------------

    protected static function booted(): void
    {
        static::creating(function (BlogPost $post) {
            if (empty($post->slug)) {
                $post->slug = static::generateUniqueSlug($post->title);
            }
        });
    }

    public static function generateUniqueSlug(string $title): string
    {
        $base  = Str::slug($title);
        $slug  = $base;
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$count}";
            $count++;
        }

        return $slug;
    }
}
