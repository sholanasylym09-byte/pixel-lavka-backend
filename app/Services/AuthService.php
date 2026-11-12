<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Exceptions\BusinessException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthService
{
    /** Стартовый подарок новому игроку — 100 бонусов. */
    public const WELCOME_BONUS = 100;

    /** @return array{0: User, 1: string} */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'password' => $data['password'],          // хэшируется cast 'hashed'
            'role' => UserRole::User,
            'bonus_balance' => self::WELCOME_BONUS,
        ]);

        return [$user, $user->createToken('api')->plainTextToken];
    }

    /** @return array{0: User, 1: string} */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', mb_strtolower($email))->first();

        // Общее сообщение: не уточняем, что именно неверно
        if (! $user || ! Hash::check($password, $user->password)) {
            Log::warning('Неудачная попытка входа', ['email' => $email]);
            throw new BusinessException('Неверный email или пароль.', 401);
        }

        return [$user, $user->createToken('api')->plainTextToken];
    }
}
