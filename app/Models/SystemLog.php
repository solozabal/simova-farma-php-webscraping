<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'level',
        'message',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public static function log(string $type, string $message, array $context = [], string $level = 'info'): self
    {
        return self::create([
            'type' => $type,
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ]);
    }
}
