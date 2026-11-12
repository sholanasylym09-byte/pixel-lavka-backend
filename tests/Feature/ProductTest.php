<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_filters_sorts_and_paginates(): void
    {
        $weapons = Category::factory()->create();
        Product::factory()->for($weapons)->create(['name' => 'Меч А', 'price' => 300, 'rarity' => 'rare']);
        Product::factory()->for($weapons)->create(['name' => 'Меч Б', 'price' => 900, 'rarity' => 'rare']);
        Product::factory()->for($weapons)->create(['name' => 'Меч В', 'price' => 500, 'rarity' => 'common']);
        Product::factory()->count(3)->create(['rarity' => 'rare']);

        $this->getJson("/api/v1/products?category_id={$weapons->id}&rarity=rare&sort=price&order=desc&per_page=1")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Меч Б')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_search_by_part_of_name(): void
    {
        Product::factory()->create(['name' => 'Пламенный клинок']);
        Product::factory()->create(['name' => 'Щит стража']);

        $this->getJson('/api/v1/products?search=клин')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_sort_field_is_checked_by_whitelist(): void
    {
        $this->getJson('/api/v1/products?sort=password')->assertStatus(422)->assertJsonValidationErrors('sort');
    }

    public function test_missing_product_returns_404_with_message(): void
    {
        $this->getJson('/api/v1/products/99999')->assertNotFound()->assertJsonPath('message', 'Товар не найден.');
    }

    public function test_admin_can_create_product(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $category = Category::factory()->create();

        $this->postJson('/api/v1/products', [
            'category_id' => $category->id, 'name' => 'Новый меч', 'price' => 1500,
            'stock' => 10, 'rarity' => 'epic', 'icon' => 'sword',
        ])->assertCreated()->assertJsonPath('data.category.id', $category->id);

        $this->assertDatabaseHas('products', ['name' => 'Новый меч', 'price' => 1500]);
    }

    public function test_regular_user_cannot_create_product(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/products', [])->assertForbidden();
    }

    public function test_product_with_orders_cannot_be_deleted(): void
    {
        Sanctum::actingAs($user = User::factory()->create());
        $product = Product::factory()->create(['stock' => 5]);
        $this->postJson('/api/v1/orders', ['items' => [['product_id' => $product->id, 'quantity' => 1]]])->assertCreated();

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->deleteJson("/api/v1/products/{$product->id}")->assertStatus(409);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
}
