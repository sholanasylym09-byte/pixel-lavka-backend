<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mini_game_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('score');
            $table->unsignedInteger('bonus_points')->default(0);
            $table->unsignedTinyInteger('discount_percent')->default(0);
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            // Подсчёт попыток пользователя в игре за сутки
            $table->index(['user_id', 'mini_game_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_results');
    }
};
