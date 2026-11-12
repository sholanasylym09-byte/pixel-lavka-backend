<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/** Владелец видит и отменяет только свои заказы, администратор — любые. */
class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin() || $order->user_id === $user->id;
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->isAdmin() || $order->user_id === $user->id;
    }
}
