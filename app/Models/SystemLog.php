<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log de sistema — auditoria de eventos importantes.
 *
 * Canais (channel):
 *   telegram    → envios e webhooks do Telegram
 *   mercadopago → webhooks e pagamentos
 *   scraper     → coleta de artigos
 *   scheduler   → tarefas agendadas
 *   app         → eventos gerais da aplicação
 *
 * Níveis (level): debug, info, warning, error
 */
class SystemLog extends Model
{
    // Tabela não usa updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'level',
        'channel',
        'message',
        'context',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'context'    => 'array',
            'created_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // -------------------------------------------------------------------------
    // Fábrica de logs (métodos estáticos convenientes)
    // -------------------------------------------------------------------------

    public static function info(string $channel, string $message, array $context = [], ?int $userId = null): static
    {
        return static::create([
            'level'   => 'info',
            'channel' => $channel,
            'message' => $message,
            'context' => $context ?: null,
            'user_id' => $userId,
        ]);
    }

    public static function warning(string $channel, string $message, array $context = [], ?int $userId = null): static
    {
        return static::create([
            'level'   => 'warning',
            'channel' => $channel,
            'message' => $message,
            'context' => $context ?: null,
            'user_id' => $userId,
        ]);
    }

    public static function error(string $channel, string $message, array $context = [], ?int $userId = null): static
    {
        return static::create([
            'level'   => 'error',
            'channel' => $channel,
            'message' => $message,
            'context' => $context ?: null,
            'user_id' => $userId,
        ]);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeByLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function scopeErrors($query)
    {
        return $query->where('level', 'error');
    }
}
