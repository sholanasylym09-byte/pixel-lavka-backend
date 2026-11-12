<?php

namespace App\Http\Requests;

use App\Enums\Rarity;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'integer', 'min:1', 'max:10000000'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'rarity' => ['required', Rule::enum(Rarity::class)],
            'icon' => ['required', Rule::in(Product::ICONS)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'price.min' => 'Цена должна быть больше нуля.',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'название'];
    }
}
