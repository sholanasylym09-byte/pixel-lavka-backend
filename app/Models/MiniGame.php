<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MiniGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'name', 'description', 'icon', 'max_discount', 'daily_attempts', 'score_per_percent', 'max_score', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_discount' => 'integer',
            'daily_attempts' => 'integer',
            'score_per_percent' => 'integer',
            'max_score' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** Привязка к маршруту по slug: /api/mini-games/coin-catcher */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function results(): HasMany
    {
        return $this->hasMany(GameResult::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }
}
