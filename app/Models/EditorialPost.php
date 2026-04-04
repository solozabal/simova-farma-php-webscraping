<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Post editorial programado para envio via Telegram.
 *
 * Slots:
 *   morning   → enviado às 09:10 (America/Sao_Paulo)
 *   afternoon → enviado às 17:40 (America/Sao_Paulo)
 *
 * Fluxo de aprovação:
 *   draft → approved → sent
 *   (posts só são enviados quando status='approved')
 */
class EditorialPost extends Model
{
    /** @use HasFactory<\Database\Factories\EditorialPostFactory> */
    use HasFactory;
    protected $fillable = [
        'title',
        'content',
        'content_fallback',
        'slot',
        'scheduled_at',
        'status',
        'sent_at',
        'recipients_count',
        'approved_by',
        'approved_at',
        'article_id',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at'    => 'datetime',
            'sent_at'         => 'datetime',
            'approved_at'     => 'datetime',
            'recipients_count'=> 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /** Posts aprovados e prontos para envio no momento atual */
    public function scopeReadyToSend($query)
    {
        return $query->where('status', 'approved')
                     ->where('scheduled_at', '<=', now());
    }

    /** Posts do slot da manhã (09:10) */
    public function scopeMorning($query)
    {
        return $query->where('slot', 'morning');
    }

    /** Posts do slot da tarde (17:40) */
    public function scopeAfternoon($query)
    {
        return $query->where('slot', 'afternoon');
    }

    // -------------------------------------------------------------------------
    // Utilitários
    // -------------------------------------------------------------------------

    /** Texto final da mensagem (conteúdo ou fallback) */
    public function getMessageText(): string
    {
        return $this->content ?: ($this->content_fallback ?? '');
    }

    /** Marca o post como enviado */
    public function markAsSent(int $recipientsCount = 0): void
    {
        $this->update([
            'status'           => 'sent',
            'sent_at'          => now(),
            'recipients_count' => $recipientsCount,
        ]);
    }
}
