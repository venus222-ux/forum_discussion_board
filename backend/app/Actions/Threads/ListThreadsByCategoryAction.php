<?php
namespace App\Actions\Threads;

use App\Models\Category;
use App\Models\Thread;

class ListThreadsByCategoryAction
{
    public function execute(string $slug): array
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $threads = Thread::with(['user:id,name', 'category:id,name,slug'])
            ->where('category_id', $category->id)
            ->latest()
            ->paginate(10);

        return [
            'data' => $threads->items(),
            'current_page' => $threads->currentPage(),
            'last_page' => $threads->lastPage(),
            'total' => $threads->total(),
        ];
    }
}
