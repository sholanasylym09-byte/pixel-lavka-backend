<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\MiniGameController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StatsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.')->group(function () {
    // --- Публичные маршруты (гость) ---
    Route::get('/health', HealthController::class)->name('health');
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
    });
    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
    Route::apiResource('products', ProductController::class)->only(['index', 'show']);
    Route::get('/mini-games', [MiniGameController::class, 'index'])->name('mini-games.index');
    Route::get('/mini-games/{miniGame}', [MiniGameController::class, 'show'])->name('mini-games.show');

    // --- Авторизованный пользователь ---
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/user', [AuthController::class, 'me'])->name('user');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:20,1')->name('orders.store');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

        Route::post('/mini-games/{miniGame}/play', [MiniGameController::class, 'play'])
            ->middleware('throttle:30,1')->name('mini-games.play');

        Route::get('/coupons', [CouponController::class, 'index'])->name('coupons.index');
        Route::post('/coupons/check', [CouponController::class, 'check'])->name('coupons.check');

        // --- Администратор ---
        Route::middleware('role:admin')->group(function () {
            Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);
            Route::apiResource('products', ProductController::class)->only(['store', 'update', 'destroy']);
            Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
            Route::get('/stats', StatsController::class)->name('stats');
        });
    });
});
