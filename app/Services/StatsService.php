<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Coupon;
use App\Models\MiniGame;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StatsService
{
    /** Агрегированные данные для панели администратора. */
    public function summary(): array
    {
        $byStatus = Order::query()
            ->selectRaw('status, COUNT(*) as cnt, SUM(total) as sum_total')
            ->groupBy('status')
            ->get()
            ->keyBy(fn ($row) => $row->status->value);

        $paidStatuses = [OrderStatus::Paid->value, OrderStatus::Completed->value];

        $topProducts = DB::table('order_product')
            ->join('orders', 'orders.id', '=', 'order_product.order_id')
            ->join('products', 'products.id', '=', 'order_product.product_id')
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->groupBy('products.id', 'products.name', 'products.icon')
            ->selectRaw('products.id, products.name, products.icon, SUM(order_product.quantity) as sold, SUM(order_product.quantity * order_product.price) as revenue')
            ->orderByDesc('sold')
            ->limit(5)
            ->get();

        $games = MiniGame::query()
            ->withCount('results')
            ->withAvg('results', 'score')
            ->withCount(['coupons as coupons_used_count' => fn ($q) => $q->whereNotNull('used_at')])
            ->get()
            ->map(fn ($g) => [
                'name' => $g->name,
                'plays' => $g->results_count,
                'avg_score' => (int) round($g->results_avg_score ?? 0),
                'coupons_used' => $g->coupons_used_count,
            ]);

        return [
            'users_count' => User::count(),
            'products_count' => Product::where('is_active', true)->count(),
            'orders_count' => Order::count(),
            'revenue' => (int) Order::whereIn('status', $paidStatuses)->sum('total'),
            'discount_total' => (int) Order::where('status', '!=', OrderStatus::Cancelled->value)->sum('discount_amount'),
            'orders_by_status' => collect(OrderStatus::cases())->mapWithKeys(fn ($s) => [
                $s->value => ['label' => $s->label(), 'count' => (int) ($byStatus[$s->value]->cnt ?? 0)],
            ]),
            'top_products' => $topProducts,
            'games' => $games,
            'coupons' => [
                'issued' => Coupon::count(),
                'used' => Coupon::whereNotNull('used_at')->count(),
            ],
        ];
    }
}
