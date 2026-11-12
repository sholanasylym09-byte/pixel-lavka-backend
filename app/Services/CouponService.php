<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Coupon;
use App\Models\MiniGame;
use App\Models\User;
use Illuminate\Support\Str;

class CouponService
{
    public const LIFETIME_DAYS = 7;

    /** Список купонов пользователя: сначала действующие. */
    public function listFor(User $user)
    {
        return $user->coupons()->with('miniGame')
            ->orderByRaw('used_at IS NOT NULL')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Находит купон, который пользователь может применить прямо сейчас.
     * Бизнес-правило: купон существует, принадлежит пользователю, не использован и не истёк.
     */
    public function findUsable(User $user, string $code, bool $lock = false): Coupon
    {
        $query = Coupon::query()->where('code', mb_strtoupper(trim($code)));
        if ($lock) {
            $query->lockForUpdate();
        }
        $coupon = $query->first();

        // Чужой купон не раскрываем — отвечаем так же, как для несуществующего
        if (! $coupon || $coupon->user_id !== $user->id) {
            throw new BusinessException('Купон с таким кодом не найден.', 404);
        }
        if ($coupon->used_at) {
            throw new BusinessException('Купон уже использован в другом заказе.');
        }
        if ($coupon->expires_at->isPast()) {
            throw new BusinessException('Срок действия купона истёк '.$coupon->expires_at->format('d.m.Y').'.');
        }

        return $coupon;
    }

    public function issue(User $user, ?MiniGame $game, int $percent): Coupon
    {
        do {
            $code = 'PIX-'.Str::upper(Str::random(6));
        } while (Coupon::where('code', $code)->exists());

        return Coupon::create([
            'code' => $code,
            'user_id' => $user->id,
            'mini_game_id' => $game?->id,
            'discount_percent' => $percent,
            'expires_at' => now()->addDays(self::LIFETIME_DAYS),
        ]);
    }
}
