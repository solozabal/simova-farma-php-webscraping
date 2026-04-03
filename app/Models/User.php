<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'telegram_id',
        'telegram_username',
        'telegram_link_token',
        'telegram_linked_at',
        'payment_provider',
        'payment_customer_id',
        'payment_subscription_id',
        'plan_type',
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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'telegram_linked_at' => 'datetime',
            'activated_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->email_verified_at !== null;
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'test']);
    }

    public function blogPosts()
    {
        return $this->hasMany(BlogPost::class, 'author_id');
    }
}
