<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoryService
{
    public function list()
    {
        return Category::query()
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Category
    {
        $data['slug'] = Str::slug($data['name']) ?: Str::random(8);

        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']) ?: $category->slug;
        }
        $category->update($data);

        return $category;
    }

    public function delete(Category $category): void
    {
        $count = $category->products()->count();
        if ($count > 0) {
            throw new BusinessException("Нельзя удалить категорию: в ней {$count} товаров. Сначала перенесите их.");
        }
        $category->delete();
    }
}
