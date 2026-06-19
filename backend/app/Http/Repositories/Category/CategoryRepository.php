<?php

namespace App\Http\Repositories\Category;

use App\Models\Category;

class CategoryRepository
{
    public function getAllWithThreadCount()
    {
        return Category::withCount('threads')
            ->orderByDesc('threads_count')
            ->get();
    }

    public function findBySlug(string $slug)
    {
        return Category::with('children', 'threads')
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function create(array $data)
    {
        return Category::create($data);
    }

    public function updateBySlug(string $slug, array $data)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $category->update($data);

        return $category;
    }

    public function deleteBySlug(string $slug): void
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $category->delete();
    }
}
