<?php

namespace Database\Seeders;

use App\Models\MiniGame;
use Illuminate\Database\Seeder;

class MiniGameSeeder extends Seeder
{
    public function run(): void
    {
        MiniGame::create([
            'slug' => 'coin-catcher', 'name' => 'Ловец монет', 'icon' => 'coin',
            'description' => 'Лови падающие монеты и самоцветы корзиной, уворачивайся от бомб. 30 секунд.',
            'max_discount' => 15, 'daily_attempts' => 3, 'score_per_percent' => 30, 'max_score' => 1500,
        ]);
        MiniGame::create([
            'slug' => 'perfect-hit', 'name' => 'Меткий удар', 'icon' => 'sword',
            'description' => 'Останови бегунок в зелёной зоне. 5 раундов, с каждым раундом быстрее.',
            'max_discount' => 15, 'daily_attempts' => 3, 'score_per_percent' => 35, 'max_score' => 500,
        ]);
        MiniGame::create([
            'slug' => 'pixel-memory', 'name' => 'Пиксельная память', 'icon' => 'scroll',
            'description' => 'Найди все пары предметов за минимальное число ходов.',
            'max_discount' => 20, 'daily_attempts' => 3, 'score_per_percent' => 50, 'max_score' => 1000,
        ]);
    }
}
