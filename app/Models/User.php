<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Modelo de usuário do SIMOVA FARMA.
 *
 * Papéis (role):
 *   admin      — acesso ao painel Filament
 *   subscriber — assinante, recebe conteúdo via Telegram
 *
 * Status da assinatura:
 *   lead      — cadastrou interesse, ainda não pagou
 *   pending   — pagamento iniciado, aguardando confirmação
 *   active    — assinatura ativa, recebe alertas/digest
 *   cancelled — assinatura cancelada
 *   test      — usuário de teste interno (recebe como se fosse active)
 */
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'telegram_id',
        'telegram_username',
        'telegram_linked_at',
        'telegram_link_token',
        'telegram_link_token_expires_at',
        'payment_provider',
        'payment_customer_id',
        'payment_subscription_id',
        'activated_at',
        'cancelled_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'telegram_link_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'              => 'datetime',
            'password'                       => 'hashed',
            'telegram_linked_at'             => 'datetime',
            'telegram_link_token_expires_at' => 'datetime',
            'activated_at'                   => 'datetime',
            'cancelled_at'                   => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Filament: apenas admins acessam o painel
    // -------------------------------------------------------------------------

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin';
    }

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    public function editorialPostsApproved(): HasMany
    {
        return $this->hasMany(EditorialPost::class, 'approved_by');
    }

    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'author_id');
    }

    public function systemLogs(): HasMany
    {
        return $this->hasMany(SystemLog::class);
    }

    // -------------------------------------------------------------------------
    // Métodos utilitários
    // -------------------------------------------------------------------------

    /** Verifica se o usuário pode receber mensagens Telegram */
    public function canReceiveTelegram(): bool
    {
        return $this->telegram_id !== null
            && in_array($this->status, ['active', 'test'], true);
    }

    /** Gera (ou renova) o token de vinculação com o Telegram */
    public function generateTelegramLinkToken(): string
    {
        $token = Str::random(40);
        $this->update([
            'telegram_link_token'             => $token,
            'telegram_link_token_expires_at'  => now()->addHours(24),
        ]);
        return $token;
    }

    /** Vincula o Telegram ao usuário e ativa a conta se estava pendente */
    public function linkTelegram(string $telegramId, ?string $username = null): void
    {
        $this->update([
            'telegram_id'             => $telegramId,
            'telegram_username'       => $username,
            'telegram_linked_at'      => now(),
            'telegram_link_token'     => null,
            'telegram_link_token_expires_at' => null,
        ]);
    }

    /** Ativa a assinatura */
    public function activate(): void
    {
        $this->update([
            'status'       => 'active',
            'activated_at' => now(),
            'cancelled_at' => null,
        ]);
    }

    /** Cancela a assinatura */
    public function cancel(): void
    {
        $this->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeReceivingTelegram($query)
    {
        return $query->whereNotNull('telegram_id')
                     ->whereIn('status', ['active', 'test']);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAdmin($query)
    {
        return $query->where('role', 'admin');
    }
}
