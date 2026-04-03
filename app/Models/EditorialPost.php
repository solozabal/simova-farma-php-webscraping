<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EditorialPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'title_template',
        'content',
        'content_template',
        'schedule_rule',
        'publish_at',
        'channel',
        'status',
        'last_run_at',
        'next_run_at',
    ];

    protected function casts(): array
    {
        return [
            'publish_at' => 'datetime',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
        ];
    }

    public function isDue(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->next_run_at) {
            return now()->gte($this->next_run_at);
        }

        if ($this->publish_at) {
            return now()->gte($this->publish_at) && $this->last_run_at === null;
        }

        return false;
    }
}
