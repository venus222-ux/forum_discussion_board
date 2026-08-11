<?php

namespace App\Actions\Threads;

use App\Models\Thread;
use Illuminate\Support\Facades\Cache;

class ShowThreadAction
{
    public function execute(string $slug, string $ip): array
    {
        $thread = Thread::with(['user:id,name', 'category:id,name,slug'])
            ->where('slug', $slug)
            ->firstOrFail();

        $key = "thread_view_{$thread->id}_{$ip}";

        if (! Cache::has($key)) {
            $thread->increment('views');
            Cache::put($key, true, now()->addHours(24));
            $thread->refresh();
        }

        $thread->replies = $thread->fetchComments();

        return $thread->toArray();
    }
}
