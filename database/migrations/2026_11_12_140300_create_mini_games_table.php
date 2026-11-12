<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mini_games', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name', 100);
            $table->string('description', 500);
            $table->string('icon', 30)->default('coin');
            $table->unsignedTinyInteger('max_discount');          // максимальная скидка, %
            $table->unsignedTinyInteger('daily_attempts')->default(3);
            $table->unsignedSmallInteger('score_per_percent');    // очков за 1 % скидки
            $table->unsignedSmallInteger('max_score');            // верхняя граница очков (античит)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mini_games');
    }
};
