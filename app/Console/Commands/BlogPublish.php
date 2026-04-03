<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use Illuminate\Console\Command;

class BlogPublish extends Command
{
    protected $signature = 'simova:blog:publish';
    protected $description = 'Publish scheduled blog posts that are due';

    public function handle(): int
    {
        $count = BlogPost::where('status', 'scheduled')
            ->where('publish_at', '<=', now())
            ->update(['status' => 'published']);

        if ($count > 0) {
            $this->info("Published {$count} scheduled blog post(s).");
        }

        return self::SUCCESS;
    }
}
