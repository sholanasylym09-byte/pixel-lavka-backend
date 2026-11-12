<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Нарушение бизнес-правила (нет товара на складе, купон недействителен,
 * закончились попытки мини-игры и т.п.). Выводится как JSON с кодом 409.
 */
class BusinessException extends RuntimeException
{
    public function __construct(string $message, private int $status = 409)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}
