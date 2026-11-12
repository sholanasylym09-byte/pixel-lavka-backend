<?php

namespace App\Enums;

/**
 * Статусы заказа и допустимые переходы между ними.
 * new → paid → completed; new/paid → cancelled.
 */
enum OrderStatus: string
{
    case New = 'new';
    case Paid = 'paid';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новый',
            self::Paid => 'Оплачен',
            self::Completed => 'Выдан',
            self::Cancelled => 'Отменён',
        };
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::Paid, self::Cancelled],
            self::Paid => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }
}
