<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function __construct(private CategoryService $categories) {}

    public function index()
    {
        return CategoryResource::collection($this->categories->list());
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category->loadCount('products'));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        return (new CategoryResource($this->categories->create($request->validated())))
            ->response()->setStatusCode(201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        return new CategoryResource($this->categories->update($category, $request->validated()));
    }

    public function destroy(Category $category): Response
    {
        $this->categories->delete($category);

        return response()->noContent();
    }
}
