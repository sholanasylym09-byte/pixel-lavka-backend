<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\GameResult;
use App\Models\MiniGame;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameService
{
    /** Купон выдаётся, если заработано не меньше 3 % скидки. */
    public const MIN_COUPON_PERCENT = 3;

    /** 1 бонус за каждые 10 очков. */
    public const POINTS_PER_BONUS = 10;

    public function __construct(private CouponService $coupons) {}

    public function attemptsUsedToday(User $user, MiniGame $game): int
    {
        return GameResult::query()
            ->where('user_id', $user->id)
            ->where('mini_game_id', $game->id)
            ->where('created_at', '>=', today())
            ->count();
    }

    public function attemptsLeft(User $user, MiniGame $game): int
    {
        return max(0, $game->daily_attempts - $this->attemptsUsedToday($user, $game));
    }

    /**
     * Фиксирует результат игры и начисляет награду:
     * p = min(Pmax, ⌊score / k⌋) — процент скидки, B = ⌊score / 10⌋ — бонусы.
     */
    public function play(User $user, MiniGame $game, int $score): GameResult
    {
        if (! $game->is_active) {
            throw new BusinessException('Мини-игра временно недоступна.');
        }

        return DB::transaction(function () use ($user, $game, $score) {
            // Блокируем строку пользователя, чтобы параллельные запросы не обошли лимит попыток
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            $used = $this->attemptsUsedToday($user, $game);
            if ($used >= $game->daily_attempts) {
                throw new BusinessException(
                    "Попытки на сегодня закончились ({$used} из {$game->daily_attempts}). Возвращайся завтра!"
                );
            }

            $percent = min($game->max_discount, intdiv($score, $game->score_per_percent));
            $bonus = intdiv($score, self::POINTS_PER_BONUS);

            $coupon = $percent >= self::MIN_COUPON_PERCENT
                ? $this->coupons->issue($user, $game, $percent)
                : null;

            if ($bonus > 0) {
                $user->increment('bonus_balance', $bonus);
            }

            $result = GameResult::create([
                'user_id' => $user->id,
                'mini_game_id' => $game->id,
                'score' => $score,
                'bonus_points' => $bonus,
                'discount_percent' => $coupon ? $percent : 0,
                'coupon_id' => $coupon?->id,
            ]);

            Log::info('Сыграна мини-игра', [
                'game' => $game->slug, 'user_id' => $user->id, 'score' => $score, 'percent' => $percent,
            ]);

            $result->setRelation('coupon', $coupon);
            $result->setRelation('miniGame', $game);
            $result->attempts_left = max(0, $game->daily_attempts - $used - 1);

            return $result;
        });
    }
}
