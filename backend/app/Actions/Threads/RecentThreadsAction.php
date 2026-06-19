<?php
namespace App\Actions\Threads;

use App\Models\Thread;

class RecentThreadsAction
{
    public function execute(int $page): array
    {
        $threads = Thread::with(['user:id,name', 'category:id,name,slug'])
            ->latest('created_at')
            ->paginate(10, ['*'], 'page', $page);

        $threads->getCollection()->transform(function ($t) {
            $t->created_at = $t->created_at?->toIso8601String();
            $t->like_count = $t->upvotes - $t->downvotes;
            return $t;
        });

        return [
            'data' => $threads->items(),
            'current_page' => $threads->currentPage(),
            'last_page' => $threads->lastPage(),
            'total' => $threads->total(),
        ];
    }
}
