<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameResult extends Model
{
    protected $fillable = ['user_id', 'mini_game_id', 'score', 'bonus_points', 'discount_percent', 'coupon_id'];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'bonus_points' => 'integer',
            'discount_percent' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function miniGame(): BelongsTo
    {
        return $this->belongsTo(MiniGame::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
