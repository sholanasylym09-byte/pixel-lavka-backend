<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'user_id', 'mini_game_id', 'discount_percent', 'expires_at', 'used_at'];

    protected function casts(): array
    {
        return [
            'discount_percent' => 'integer',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
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

    /** Статус купона: active | used | expired. */
    public function state(): string
    {
        if ($this->used_at) {
            return 'used';
        }

        return $this->expires_at->isPast() ? 'expired' : 'active';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('used_at')->where('expires_at', '>', now());
    }
}
