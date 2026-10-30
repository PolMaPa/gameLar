<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Iniciar sesión')
            ->assertSee('Crear una cuenta');
    }

    public function test_users_can_authenticate(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('games.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_cannot_authenticate_with_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_users_are_redirected_away_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('games.index'));
    }

    public function test_register_screen_can_be_rendered(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Crear cuenta');
    }

    public function test_new_users_can_register(): void
    {
        $this->post(route('register'), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('games.index'));

        $user = User::where('email', 'ada@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('Ada Lovelace', $user->name);
    }

    public function test_registration_requires_a_unique_email(): void
    {
        $existing = User::factory()->create();

        $this->post(route('register'), [
            'name' => 'Otra persona',
            'email' => $existing->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_registration_requires_a_confirmed_password(): void
    {
        $this->post(route('register'), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password',
            'password_confirmation' => 'otra-cosa',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('games.index'));

        $this->assertGuest();
    }

    public function test_guests_cannot_logout(): void
    {
        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
