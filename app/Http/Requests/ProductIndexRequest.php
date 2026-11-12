<?php

namespace App\Http\Requests;

use App\Enums\Rarity;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Параметры фильтрации, поиска, сортировки и пагинации каталога. */
class ProductIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'rarity' => ['nullable', Rule::enum(Rarity::class)],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => array_merge(['nullable', 'integer', 'min:0'], $this->filled('min_price') ? ['gte:min_price'] : []),
            'in_stock' => ['nullable', 'boolean'],
            'include_inactive' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(Product::SORTABLE)],
            'order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'max_price.gte' => 'Максимальная цена не может быть меньше минимальной.',
            'sort.in' => 'Сортировка возможна только по полям: '.implode(', ', Product::SORTABLE).'.',
        ];
    }
}
