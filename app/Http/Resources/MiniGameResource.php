<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MiniGameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'max_discount' => $this->max_discount,
            'daily_attempts' => $this->daily_attempts,
            'score_per_percent' => $this->score_per_percent,
            'max_score' => $this->max_score,
            // null для гостя: попытки считаются только у авторизованного игрока
            'attempts_left' => $this->attempts_left,
        ];
    }
}
