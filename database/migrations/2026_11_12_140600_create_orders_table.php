<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('new')->index();
            $table->unsignedInteger('subtotal');                     // сумма без скидок
            $table->unsignedInteger('discount_amount')->default(0);  // скидка по купону
            $table->unsignedInteger('bonus_used')->default(0);       // списано бонусов
            $table->unsignedInteger('total');                        // к оплате
            $table->unsignedInteger('bonus_earned')->default(0);     // кэшбэк 5 % после выдачи
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // Связь «многие ко многим»: заказ - товар (количество и цена на момент заказа)
        Schema::create('order_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('price');
            $table->timestamps();

            $table->unique(['order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_product');
        Schema::dropIfExists('orders');
    }
};
