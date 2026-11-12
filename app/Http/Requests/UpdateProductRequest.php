<?php

namespace App\Http\Requests;

use App\Enums\Rarity;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'price' => ['sometimes', 'required', 'integer', 'min:1', 'max:10000000'],
            'stock' => ['sometimes', 'required', 'integer', 'min:0', 'max:100000'],
            'rarity' => ['sometimes', 'required', Rule::enum(Rarity::class)],
            'icon' => ['sometimes', 'required', Rule::in(Product::ICONS)],
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
