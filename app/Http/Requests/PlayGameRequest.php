<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Результат мини-игры: очки не могут превышать максимум конкретной игры (античит). */
class PlayGameRequest extends FormRequest
{
    public function rules(): array
    {
        $max = $this->route('miniGame')?->max_score ?? 0;

        return [
            'score' => ['required', 'integer', 'min:0', 'max:'.$max],
        ];
    }

    public function messages(): array
    {
        return [
            'score.max' => 'Результат превышает максимально возможный для этой игры (:max очков).',
        ];
    }
}
