<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'game' => $this->whenLoaded('miniGame', fn () => $this->miniGame->name),
            'score' => $this->score,
            'bonus_points' => $this->bonus_points,
            'discount_percent' => $this->discount_percent,
            'coupon' => $this->coupon ? new CouponResource($this->coupon) : null,
            'attempts_left' => $this->attempts_left,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
