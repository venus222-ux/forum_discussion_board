<?php

namespace App\Http\Repositories\Admin;

use App\Models\Thread;

class ThreadRepository
{
    public function count(): int
    {
        return Thread::count();
    }

    public function recentWithRelations(int $limit = 5): array
    {
        return Thread::with('user','category')
            ->orderBy('created_at','desc')
            ->limit($limit)
            ->get()
            ->map(fn($thread) => [
                'id' => $thread->id,
                'title' => $thread->title,
                'user' => ['name' => $thread->user->name ?? 'Unknown'],
                'category' => ['name' => $thread->category->name ?? 'Uncategorized'],
                'created_at' => $thread->created_at,
                'comment_count' => $thread->comment_count,
            ])
            ->toArray();
    }
}
