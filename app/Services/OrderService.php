<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\BusinessException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderService
{
    /** Бонусами можно оплатить не более 30 % суммы после скидки. */
    public const MAX_BONUS_PERCENT = 30;

    /** Кэшбэк бонусами после выдачи заказа. */
    public const CASHBACK_PERCENT = 5;

    public function __construct(private CouponService $coupons) {}

    public function listFor(User $user, array $filters)
    {
        return Order::query()
            ->with(['products', 'coupon', 'user'])
            ->when(! $user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->latest('id')
            ->paginate((int) ($filters['per_page'] ?? 10))
            ->withQueryString();
    }

    /**
     * Ключевой сценарий: оформление заказа.
     * В одной транзакции: проверка остатков и купона, расчёт суммы,
     * списание остатков, бонусов и погашение купона.
     */
    public function create(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            // Объединяем повторяющиеся позиции: product_id => количество
            $items = collect($data['items'])
                ->groupBy('product_id')
                ->map(fn ($rows) => (int) $rows->sum('quantity'));

            // SELECT ... FOR UPDATE: два покупателя не смогут купить последний предмет одновременно
            $products = Product::query()->whereIn('id', $items->keys())
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            $subtotal = 0;
            $lines = [];
            foreach ($items as $productId => $qty) {
                $product = $products[$productId];
                if (! $product->is_active) {
                    throw new BusinessException("Товар «{$product->name}» снят с продажи.");
                }
                if ($product->stock < $qty) {
                    throw new BusinessException(
                        "Недостаточно товара «{$product->name}» на складе: доступно {$product->stock} шт."
                    );
                }
                $subtotal += $product->price * $qty;
                $lines[$productId] = ['quantity' => $qty, 'price' => $product->price];
            }

            // Скидка по купону из мини-игры: D = ⌊S × p / 100⌋
            $coupon = null;
            $discount = 0;
            if (! empty($data['coupon_code'])) {
                $coupon = $this->coupons->findUsable($user, $data['coupon_code'], lock: true);
                $discount = intdiv($subtotal * $coupon->discount_percent, 100);
            }
            $afterDiscount = $subtotal - $discount;

            // Оплата бонусами: не больше баланса и не больше 30 % суммы после скидки
            $bonus = (int) ($data['bonus_points'] ?? 0);
            if ($bonus > 0) {
                $balance = (int) User::query()->whereKey($user->id)->lockForUpdate()->value('bonus_balance');
                if ($bonus > $balance) {
                    throw new BusinessException("Недостаточно бонусов: на счёте {$balance}.");
                }
                $maxBonus = intdiv($afterDiscount * self::MAX_BONUS_PERCENT, 100);
                if ($bonus > $maxBonus) {
                    throw new BusinessException(
                        'Бонусами можно оплатить не более '.self::MAX_BONUS_PERCENT." % суммы заказа (до {$maxBonus} ₸)."
                    );
                }
                $user->decrement('bonus_balance', $bonus);
            }

            $order = Order::create([
                'code' => $this->generateCode(),
                'user_id' => $user->id,
                'coupon_id' => $coupon?->id,
                'status' => OrderStatus::New,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'bonus_used' => $bonus,
                'total' => $afterDiscount - $bonus,
                'bonus_earned' => 0,
            ]);

            $order->products()->attach($lines);
            foreach ($lines as $productId => $line) {
                $products[$productId]->decrement('stock', $line['quantity']);
            }
            $coupon?->update(['used_at' => now()]);

            Log::info('Создан заказ', ['code' => $order->code, 'user_id' => $user->id, 'total' => $order->total]);

            return $order->load(['products', 'coupon', 'user']);
        });
    }

    /** Отмена заказа пользователем: только в статусе «Новый». */
    public function cancel(Order $order): Order
    {
        if ($order->status !== OrderStatus::New) {
            throw new BusinessException(
                "Отменить можно только новый заказ. Текущий статус: «{$order->status->label()}»."
            );
        }

        return $this->changeStatus($order, OrderStatus::Cancelled);
    }

    /** Смена статуса по допустимым переходам (OrderStatus::allowedTransitions). */
    public function changeStatus(Order $order, OrderStatus $next): Order
    {
        if (! $order->status->canTransitionTo($next)) {
            throw new BusinessException(
                "Нельзя перевести заказ из статуса «{$order->status->label()}» в «{$next->label()}»."
            );
        }

        return DB::transaction(function () use ($order, $next) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($next === OrderStatus::Cancelled) {
                $this->restore($order);
            }
            if ($next === OrderStatus::Completed) {
                $order->bonus_earned = intdiv($order->total * self::CASHBACK_PERCENT, 100);
                $order->user()->increment('bonus_balance', $order->bonus_earned);
            }

            $order->status = $next;
            $order->save();
            Log::info('Статус заказа изменён', ['code' => $order->code, 'status' => $next->value]);

            return $order->load(['products', 'coupon', 'user']);
        });
    }

    /** Возврат остатков, купона и бонусов при отмене. */
    private function restore(Order $order): void
    {
        foreach ($order->products as $product) {
            $product->increment('stock', $product->pivot->quantity);
        }
        if ($order->coupon_id) {
            $order->coupon()->update(['used_at' => null]);
        }
        if ($order->bonus_used > 0) {
            $order->user()->increment('bonus_balance', $order->bonus_used);
        }
    }

    private function generateCode(): string
    {
        do {
            $code = 'PL-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
        } while (Order::where('code', $code)->exists());

        return $code;
    }
}
