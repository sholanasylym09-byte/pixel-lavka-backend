<?php

namespace Tests\Feature;

use App\Models\MiniGame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MiniGameTest extends TestCase
{
    use RefreshDatabase;

    public function test_play_gives_coupon_and_bonus_by_formula(): void
    {
        $user = User::factory()->create(['bonus_balance' => 0]);
        $game = MiniGame::factory()->create(['score_per_percent' => 30, 'max_discount' => 15]);
        Sanctum::actingAs($user);

        // p = min(15, ⌊360 / 30⌋) = 12 %, B = ⌊360 / 10⌋ = 36
        $this->postJson("/api/v1/mini-games/{$game->slug}/play", ['score' => 360])
            ->assertCreated()
            ->assertJsonPath('data.discount_percent', 12)
            ->assertJsonPath('data.bonus_points', 36)
            ->assertJsonPath('data.coupon.discount_percent', 12)
            ->assertJsonPath('data.attempts_left', 2);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'bonus_balance' => 36]);
        $this->assertDatabaseHas('coupons', ['user_id' => $user->id, 'discount_percent' => 12]);
    }

    public function test_discount_is_capped_and_low_score_gives_no_coupon(): void
    {
        $game = MiniGame::factory()->create(['score_per_percent' => 30, 'max_discount' => 15, 'max_score' => 1500]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/v1/mini-games/{$game->slug}/play", ['score' => 1500])
            ->assertJsonPath('data.discount_percent', 15);
        $this->postJson("/api/v1/mini-games/{$game->slug}/play", ['score' => 60])
            ->assertJsonPath('data.coupon', null)
            ->assertJsonPath('data.bonus_points', 6);
    }

    public function test_daily_attempts_limit(): void
    {
        $game = MiniGame::factory()->create(['daily_attempts' => 2]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/v1/mini-games/{$game->slug}/play", ['score' => 10])->assertCreated();
        $this->postJson("/api/v1/mini-games/{$game->slug}/play", ['score' => 10])->assertCreated();
        $this->postJson("/api/v1/mini-games/{$game->slug}/play", ['score' => 10])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Попытки на сегодня закончились (2 из 2). Возвращайся завтра!');
    }

    public function test_score_above_maximum_is_rejected(): void
    {
        $game = MiniGame::factory()->create(['max_score' => 500]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/v1/mini-games/{$game->slug}/play", ['score' => 501])
            ->assertStatus(422)->assertJsonValidationErrors('score');
    }

    public function test_guest_cannot_play(): void
    {
        $game = MiniGame::factory()->create();

        $this->postJson("/api/v1/mini-games/{$game->slug}/play", ['score' => 100])->assertUnauthorized();
        $this->getJson('/api/v1/mini-games')->assertOk()->assertJsonPath('data.0.attempts_left', null);
    }
}
