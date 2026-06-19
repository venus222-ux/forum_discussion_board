<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Category\CategoryService;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $service
    ) {}

    public function index()
    {
        return response()->json(
            $this->service->getAllCategories()
        );
    }

    public function show(string $slug)
    {
        return response()->json(
            $this->service->getCategoryBySlug($slug)
        );
    }

    public function store(Request $request)
    {
        return response()->json(
            $this->service->createCategory($request->all()),
            201
        );
    }

    public function update(Request $request, string $slug)
    {
        return response()->json(
            $this->service->updateCategory($slug, $request->all())
        );
    }

    public function destroy(string $slug)
    {
        $this->service->deleteCategory($slug);

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }
}
