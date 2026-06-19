<?php

namespace App\Http\Repositories\Admin;

use App\Models\Category;

class CategoryRepository
{
    public function count(): int
    {
        return Category::count();
    }

    public function threadsPerCategory(): array
    {
        return Category::withCount('threads')
            ->get()
            ->map(fn($cat) => [
                'name' => $cat->name,
                'count' => $cat->threads_count,
            ])
            ->toArray();
    }
}
