<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Учётные записи с известными паролями для проверки ролей
        User::create([
            'name' => 'Хранитель Лавки', 'email' => 'admin@pixel.kz', 'password' => 'admin123',
            'role' => UserRole::Admin, 'bonus_balance' => 0, 'email_verified_at' => now(),
        ]);
        User::create([
            'name' => 'Игрок Пиксель', 'email' => 'player@pixel.kz', 'password' => 'player123',
            'role' => UserRole::User, 'bonus_balance' => 350, 'email_verified_at' => now(),
        ]);

        User::factory()->count(6)->create();
    }
}
