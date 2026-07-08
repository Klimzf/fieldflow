<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function stateful(): self
    {
        return $this
            ->withHeader('Referer', 'http://localhost:8080')
            ->withSession([]);
    }

    public function test_user_can_register(): void
    {
        $response = $this->stateful()->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'USER@EXAMPLE.COM',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Test User')
            ->assertJsonPath('data.email', 'user@example.com');

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
        ]);
    }

    public function test_registration_requires_confirmed_password(): void
    {
        $this->stateful()->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPassword123!',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $this->stateful()->postJson('/api/auth/login', [
            'email' => 'USER@EXAMPLE.COM',
            'password' => 'Password123!',
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', 'user@example.com');

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $this->stateful()->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'WrongPassword!',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_guest_cannot_request_current_user(): void
    {
        $this->getJson('/api/auth/user')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_request_current_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/auth/user')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->stateful()
            ->actingAs($user)
            ->postJson('/api/auth/logout')
            ->assertNoContent();
    }
}
