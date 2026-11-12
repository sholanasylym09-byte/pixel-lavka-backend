<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Product;
use Illuminate\Support\Str;

class ProductService
{
    /** Каталог: фильтрация, поиск, сортировка по белому списку и пагинация. */
    public function list(array $f, bool $isAdmin = false)
    {
        $sort = in_array($f['sort'] ?? null, Product::SORTABLE, true) ? $f['sort'] : 'created_at';
        $order = ($f['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return Product::query()
            ->with('category')
            ->when(! ($isAdmin && ! empty($f['include_inactive'])), fn ($q) => $q->active())
            ->filter($f)
            ->search($f['search'] ?? null)
            ->orderBy($sort, $order)
            ->orderBy('id')
            ->paginate((int) ($f['per_page'] ?? 12))
            ->withQueryString();
    }

    public function create(array $data): Product
    {
        $data['slug'] = $this->uniqueSlug($data['name']);

        return Product::create($data)->load('category');
    }

    public function update(Product $product, array $data): Product
    {
        if (isset($data['name']) && $data['name'] !== $product->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $product->id);
        }
        $product->update($data);

        return $product->load('category');
    }

    /** Товар из заказов не удаляется (история заказов должна сохраниться) — 409. */
    public function delete(Product $product): void
    {
        if ($product->orders()->exists()) {
            throw new BusinessException(
                'Нельзя удалить товар: он есть в заказах. Снимите его с продажи (is_active = false).'
            );
        }
        $product->delete();
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'item';
        $slug = $base;
        $i = 2;
        while (Product::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
