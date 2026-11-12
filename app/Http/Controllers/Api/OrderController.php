<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\OrderStatus;
use App\Http\Requests\OrderIndexRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    /** Пользователь видит только свои заказы, администратор — все. */
    public function index(OrderIndexRequest $request)
    {
        return OrderResource::collection($this->orders->listFor($request->user(), $request->validated()));
    }

    public function show(Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        return new OrderResource($order->load(['products', 'coupon', 'user']));
    }

    /** Ключевой сценарий: оформление заказа со скидкой и бонусами. */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orders->create($request->user(), $request->validated());

        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    public function cancel(Order $order): OrderResource
    {
        Gate::authorize('cancel', $order);

        return new OrderResource($this->orders->cancel($order));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): OrderResource
    {
        $status = OrderStatus::from($request->validated('status'));

        return new OrderResource($this->orders->changeStatus($order, $status));
    }
}
