<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'hash',
        'title',
        'content',
        'url',
        'source_key',
        'category',
        'language',
        'impact_label',
        'score',
        'insight',
        'published_at',
        'editorial_status',
        'is_sent_alert',
        'is_sent_digest',
        'is_blog_published',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'score' => 'float',
            'is_sent_alert' => 'boolean',
            'is_sent_digest' => 'boolean',
            'is_blog_published' => 'boolean',
        ];
    }

    public function isHighScore(): bool
    {
        return $this->score >= 8;
    }

    public function isDigestWorthy(): bool
    {
        return $this->score >= 5 && $this->score < 8;
    }

    public function scopeForAlert($query)
    {
        return $query->where('score', '>=', 8)->where('is_sent_alert', false);
    }

    public function scopeForDigest($query)
    {
        return $query->where('score', '>=', 5)->where('score', '<', 8)->where('is_sent_digest', false);
    }
}
