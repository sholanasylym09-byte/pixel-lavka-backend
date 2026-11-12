<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'coupon_code' => ['nullable', 'string', 'max:20'],
            'bonus_points' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Корзина пуста — добавьте хотя бы один товар.',
            'items.*.product_id.exists' => 'Товар не найден в каталоге.',
            'items.*.product_id.distinct' => 'Товар указан в заказе дважды.',
        ];
    }
}
