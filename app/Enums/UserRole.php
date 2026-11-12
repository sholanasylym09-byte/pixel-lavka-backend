<?php

namespace App\Enums;

/** Роли пользователей системы. */
enum UserRole: string
{
    case User = 'user';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::User => 'Игрок',
            self::Admin => 'Администратор',
        };
    }
}
