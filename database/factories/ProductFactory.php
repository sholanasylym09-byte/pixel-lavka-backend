<?php

namespace Database\Factories;

use App\Enums\Rarity;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake('ru_RU')->randomElement(['Меч', 'Щит', 'Зелье', 'Кольцо', 'Сундук', 'Свиток']).' '.fake()->unique()->numberBetween(1, 99999);

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(5),
            'description' => fake('ru_RU')->sentence(8),
            'price' => fake()->numberBetween(100, 5000),
            'stock' => fake()->numberBetween(5, 50),
            'rarity' => fake()->randomElement(Rarity::cases()),
            'icon' => fake()->randomElement(Product::ICONS),
            'is_active' => true,
        ];
    }
}
