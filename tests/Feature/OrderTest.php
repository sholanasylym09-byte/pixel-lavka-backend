<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_with_coupon_and_bonus_is_calculated_correctly(): void
    {
        $user = User::factory()->create(['bonus_balance' => 500]);
        $product = Product::factory()->create(['price' => 1000, 'stock' => 10]);
        $coupon = Coupon::factory()->for($user)->create(['discount_percent' => 10]);
        Sanctum::actingAs($user);

        // S = 2 × 1000 = 2000; D = 10 % = 200; бонусы 300 ≤ 30 % от 1800 = 540; итог = 1500
        $this->postJson('/api/v1/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'coupon_code' => $coupon->code,
            'bonus_points' => 300,
        ])->assertCreated()
            ->assertJsonPath('data.subtotal', 2000)
            ->assertJsonPath('data.discount_amount', 200)
            ->assertJsonPath('data.bonus_used', 300)
            ->assertJsonPath('data.total', 1500)
            ->assertJsonPath('data.status', 'new');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 8]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'bonus_balance' => 200]);
        $this->assertNotNull($coupon->fresh()->used_at);
    }

    public function test_cannot_order_more_than_in_stock(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $product = Product::factory()->create(['stock' => 1]);

        $this->postJson('/api/v1/orders', ['items' => [['product_id' => $product->id, 'quantity' => 2]]])
            ->assertStatus(409);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 1]);
    }

    public function test_bonus_cannot_exceed_30_percent(): void
    {
        Sanctum::actingAs(User::factory()->create(['bonus_balance' => 1000]));
        $product = Product::factory()->create(['price' => 1000, 'stock' => 5]);

        $this->postJson('/api/v1/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]], 'bonus_points' => 301,
        ])->assertStatus(409);
    }

    public function test_expired_or_used_coupon_is_rejected(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);
        $expired = Coupon::factory()->for($user)->expired()->create();
        $used = Coupon::factory()->for($user)->used()->create();
        Sanctum::actingAs($user);

        foreach ([$expired, $used] as $coupon) {
            $this->postJson('/api/v1/orders', [
                'items' => [['product_id' => $product->id, 'quantity' => 1]], 'coupon_code' => $coupon->code,
            ])->assertStatus(409);
        }
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_someone_elses_coupon_is_not_found(): void
    {
        $coupon = Coupon::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/coupons/check', ['code' => $coupon->code])->assertNotFound();
    }

    public function test_cancel_returns_stock_coupon_and_bonus(): void
    {
        $user = User::factory()->create(['bonus_balance' => 100]);
        $product = Product::factory()->create(['price' => 1000, 'stock' => 3]);
        $coupon = Coupon::factory()->for($user)->create();
        Sanctum::actingAs($user);
        $id = $this->postJson('/api/v1/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'coupon_code' => $coupon->code, 'bonus_points' => 100,
        ])->json('data.id');

        $this->postJson("/api/v1/orders/{$id}/cancel")->assertOk()->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 3]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'bonus_balance' => 100]);
        $this->assertNull($coupon->fresh()->used_at);
        $this->postJson("/api/v1/orders/{$id}/cancel")->assertStatus(409);
    }

    public function test_user_cannot_see_someone_elses_order(): void
    {
        $owner = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);
        Sanctum::actingAs($owner);
        $id = $this->postJson('/api/v1/orders', ['items' => [['product_id' => $product->id, 'quantity' => 1]]])->json('data.id');

        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/v1/orders/{$id}")->assertForbidden();
        $this->getJson('/api/v1/orders')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_admin_status_flow_and_cashback(): void
    {
        $user = User::factory()->create(['bonus_balance' => 0]);
        $product = Product::factory()->create(['price' => 2000, 'stock' => 5]);
        Sanctum::actingAs($user);
        $id = $this->postJson('/api/v1/orders', ['items' => [['product_id' => $product->id, 'quantity' => 1]]])->json('data.id');

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->patchJson("/api/v1/orders/{$id}/status", ['status' => 'completed'])->assertStatus(409);
        $this->patchJson("/api/v1/orders/{$id}/status", ['status' => 'paid'])->assertOk();
        $this->patchJson("/api/v1/orders/{$id}/status", ['status' => 'completed'])
            ->assertOk()->assertJsonPath('data.bonus_earned', 100);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'bonus_balance' => 100]);
        $this->assertSame('completed', Order::find($id)->status->value);
    }
}
