<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MiniGameFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => 'game-'.fake()->unique()->numberBetween(1, 99999),
            'name' => 'Тестовая игра',
            'description' => 'Игра для автотестов',
            'icon' => 'coin',
            'max_discount' => 15,
            'daily_attempts' => 3,
            'score_per_percent' => 30,
            'max_score' => 1500,
            'is_active' => true,
        ];
    }
}
