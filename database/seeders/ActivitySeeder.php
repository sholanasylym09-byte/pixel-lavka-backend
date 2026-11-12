<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Coupon;
use App\Models\GameResult;
use App\Models\MiniGame;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\Seeder;

/**
 * История активности: результаты игр, купоны и заказы во всех статусах.
 * Заказы создаются через OrderService, поэтому остатки и бонусы согласованы.
 */
class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $orders = app(OrderService::class);
        $games = MiniGame::all()->keyBy('slug');
        $users = User::where('role', 'user')->get();
        $player = $users->firstWhere('email', 'player@pixel.kz');
        $p = fn (string $name) => Product::where('name', $name)->value('id');

        // Результаты игр за прошлые дни (сегодняшние попытки остаются свободными)
        $scores = ['coin-catcher' => [180, 420, 610, 300], 'perfect-hit' => [210, 380, 455], 'pixel-memory' => [540, 760, 820]];
        foreach ($users as $i => $user) {
            foreach ($scores as $slug => $list) {
                $game = $games[$slug];
                $score = $list[$i % count($list)];
                $percent = min($game->max_discount, intdiv($score, $game->score_per_percent));
                $date = now()->subDays(1 + ($i % 5))->setTime(12 + $i, 15);
                $coupon = $percent >= 3 ? Coupon::create([
                    'code' => 'PIX-'.strtoupper(substr(md5($slug.$user->id), 0, 6)),
                    'user_id' => $user->id, 'mini_game_id' => $game->id, 'discount_percent' => $percent,
                    'expires_at' => $date->copy()->addDays(7),
                ]) : null;
                GameResult::create([
                    'user_id' => $user->id, 'mini_game_id' => $game->id, 'score' => $score,
                    'bonus_points' => intdiv($score, 10), 'discount_percent' => $coupon ? $percent : 0,
                    'coupon_id' => $coupon?->id,
                ])->forceFill(['created_at' => $date, 'updated_at' => $date])->save();
            }
        }

        // Заказ игрока с купоном и бонусами — уже выдан
        $playerCoupon = $player->coupons()->whereNull('used_at')->orderByDesc('discount_percent')->first();
        $o = $orders->create($player, [
            'items' => [['product_id' => $p('Пламенный клинок'), 'quantity' => 1], ['product_id' => $p('Зелье здоровья ×10'), 'quantity' => 2]],
            'coupon_code' => $playerCoupon?->code,
            'bonus_points' => 200,
        ]);
        $orders->changeStatus($o, OrderStatus::Paid);
        $orders->changeStatus($o->fresh(), OrderStatus::Completed);
        $o->forceFill(['created_at' => now()->subDays(6)])->save();

        // Новый заказ игрока (можно отменить)
        $o = $orders->create($player, ['items' => [['product_id' => $p('Сундук искателя'), 'quantity' => 1]]]);
        $o->forceFill(['created_at' => now()->subDays(1)])->save();

        // Заказы остальных игроков в разных статусах
        $plan = [
            [['Клинок рассвета', 1]], [['1000 золотых монет', 3]], [['Щит стража', 1], ['Эликсир маны', 2]],
            [['Кольцо удачи', 1]], [['Шляпа ученика', 1], ['Свиток телепорта', 4]], [['Легендарный сундук', 1]],
            [['5000 золотых монет', 1]], [['Сапфир силы', 2]], [['Лук лесного стрелка', 1]], [['Сундук искателя', 2]],
        ];
        $statuses = [OrderStatus::Completed, OrderStatus::Completed, OrderStatus::Paid, OrderStatus::Cancelled, OrderStatus::New];
        $others = $users->where('id', '!=', $player->id)->values();
        foreach ($plan as $k => $lines) {
            $user = $others[$k % $others->count()];
            $order = $orders->create($user, ['items' => array_map(fn ($l) => ['product_id' => $p($l[0]), 'quantity' => $l[1]], $lines)]);
            $status = $statuses[$k % count($statuses)];
            if (in_array($status, [OrderStatus::Paid, OrderStatus::Completed], true)) {
                $order = $orders->changeStatus($order, OrderStatus::Paid);
            }
            if ($status === OrderStatus::Completed) {
                $order = $orders->changeStatus($order, OrderStatus::Completed);
            }
            if ($status === OrderStatus::Cancelled) {
                $order = $orders->changeStatus($order, OrderStatus::Cancelled);
            }
            $order->forceFill(['created_at' => now()->subDays(14 - $k)])->save();
        }
    }
}
