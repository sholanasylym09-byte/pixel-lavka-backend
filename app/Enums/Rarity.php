<?php

namespace App\Enums;

/** Редкость игрового предмета. */
enum Rarity: string
{
    case Common = 'common';
    case Rare = 'rare';
    case Epic = 'epic';
    case Legendary = 'legendary';

    public function label(): string
    {
        return match ($this) {
            self::Common => 'Обычный',
            self::Rare => 'Редкий',
            self::Epic => 'Эпический',
            self::Legendary => 'Легендарный',
        };
    }
}
