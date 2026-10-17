<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

class PublishScheduledPosts extends Command
{
    protected $signature = 'posts:publish-scheduled';

    protected $description = 'Ubah berita berstatus scheduled menjadi published saat waktunya tiba';

    public function handle(): int
    {
        $count = Post::where('status', 'scheduled')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->update(['status' => 'published']);

        $this->info($count.' berita terjadwal dipublikasikan.');

        return self::SUCCESS;
    }
}
