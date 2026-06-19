<?php

namespace App\Services\Category;

use App\Http\Repositories\Category\CategoryRepository;
use Illuminate\Support\Str;

class CategoryService
{
    public function __construct(
        private CategoryRepository $repo
    ) {}

    public function getAllCategories()
    {
        return $this->repo->getAllWithThreadCount();
    }

    public function getCategoryBySlug(string $slug)
    {
        return $this->repo->findBySlug($slug);
    }

    public function createCategory(array $data)
    {
        return $this->repo->create($data);
    }

    public function updateCategory(string $slug, array $data)
    {
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return $this->repo->updateBySlug($slug, $data);
    }

    public function deleteCategory(string $slug): void
    {
        $this->repo->deleteBySlug($slug);
    }
}
