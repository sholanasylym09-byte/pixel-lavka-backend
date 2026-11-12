<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_gets_token(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Новый Игрок',
            'email' => 'new@pixel.kz',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.role', 'user')
            ->assertJsonPath('user.bonus_balance', 100)
            ->assertJsonMissingPath('user.password')
            ->assertJsonStructure(['token']);

        $user = User::where('email', 'new@pixel.kz')->first();
        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertNotSame('secret123', $user->password);
    }

    public function test_register_validation_errors_are_in_russian(): void
    {
        $this->postJson('/api/v1/register', ['name' => '', 'email' => 'bad', 'password' => '123'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password'])
            ->assertJsonPath('errors.name.0', 'Поле «имя» обязательно для заполнения.');
    }

    public function test_login_with_wrong_password_returns_401(): void
    {
        User::factory()->create(['email' => 'p@pixel.kz', 'password' => 'right-pass']);

        $this->postJson('/api/v1/login', ['email' => 'p@pixel.kz', 'password' => 'wrong-pass'])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Неверный email или пароль.');
    }

    public function test_logout_deletes_current_token(): void
    {
        User::factory()->create(['email' => 'p@pixel.kz', 'password' => 'right-pass']);
        $token = $this->postJson('/api/v1/login', ['email' => 'p@pixel.kz', 'password' => 'right-pass'])->json('token');

        $this->withToken($token)->postJson('/api/v1/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_protected_route_without_token_returns_401(): void
    {
        $this->getJson('/api/v1/user')->assertStatus(401)->assertJsonPath('message', 'Требуется авторизация.');
    }
}
