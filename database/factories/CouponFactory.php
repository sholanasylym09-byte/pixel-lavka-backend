<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'PIX-'.Str::upper(Str::random(6)),
            'user_id' => User::factory(),
            'mini_game_id' => null,
            'discount_percent' => 10,
            'expires_at' => now()->addDays(7),
            'used_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }

    public function used(): static
    {
        return $this->state(fn () => ['used_at' => now()->subHour()]);
    }
}
