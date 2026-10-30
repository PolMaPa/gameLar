<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_users_cannot_access_the_panel(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.index'))
            ->assertForbidden();
    }

    public function test_admins_can_see_the_panel_with_catalogue_data(): void
    {
        $admin = User::factory()->admin()->create();

        Game::factory()->create([
            'name' => 'Celeste',
            'genre' => 'Platformer',
            'platform' => 'PC',
            'rating' => 9.1,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Panel de administración')
            ->assertSee('Celeste')
            ->assertSee('Platformer')
            ->assertSee(route('games.create'), false)
            ->assertSee(route('games.edit', Game::firstOrFail()), false);
    }

    public function test_the_panel_can_search_by_name(): void
    {
        $admin = User::factory()->admin()->create();

        Game::factory()->create(['name' => 'Hades']);
        Game::factory()->create(['name' => 'Celeste']);

        $this->actingAs($admin)
            ->get(route('admin.index', ['search' => 'hades']))
            ->assertOk()
            ->assertSee('Hades')
            ->assertDontSee('Celeste');
    }

    public function test_the_panel_shows_an_empty_state(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Todavía no hay videojuegos');
    }
}
