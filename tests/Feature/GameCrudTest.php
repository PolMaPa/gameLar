<?php

namespace Tests\Feature;

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Chrono Trigger',
            'description' => 'Un clásico RPG sobre viajes en el tiempo.',
            'genre' => 'RPG',
            'platform' => 'SNES',
            'release_date' => '1995-03-11',
            'rating' => '9.6',
            'image_url' => '/images/games/chrono-trigger.jpg',
        ], $overrides);
    }

    public function test_index_lists_games(): void
    {
        $game = Game::factory()->create(['name' => 'Celeste']);

        $this->get(route('games.index'))
            ->assertOk()
            ->assertSee('Celeste')
            ->assertSee($game->platform);
    }

    public function test_index_shows_empty_state(): void
    {
        $this->get(route('games.index'))
            ->assertOk()
            ->assertSee('Todavía no hay videojuegos');
    }

    public function test_create_form_is_accessible_and_includes_csrf_protection(): void
    {
        $this->get(route('games.create'))
            ->assertOk()
            ->assertSee('name="_token"', false)
            ->assertSee('Crear juego');
    }

    public function test_store_creates_a_game(): void
    {
        $response = $this->post(route('games.store'), $this->validPayload());

        $response->assertRedirect(route('games.show', Game::firstOrFail()))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('games', [
            'name' => 'Chrono Trigger',
            'genre' => 'RPG',
            'platform' => 'SNES',
        ]);
    }

    public function test_store_converts_empty_optional_fields_to_null(): void
    {
        $this->post(route('games.store'), $this->validPayload([
            'release_date' => '',
            'rating' => '',
            'image_url' => '',
        ]))->assertRedirect();

        $game = Game::firstOrFail();

        $this->assertNull($game->release_date);
        $this->assertNull($game->rating);
        $this->assertNull($game->image_url);
    }

    public function test_store_rejects_invalid_data(): void
    {
        $this->from(route('games.create'))
            ->post(route('games.store'), [
                'name' => '',
                'description' => '',
                'genre' => 'RPG',
                'platform' => 'PC',
                'rating' => '11',
                'release_date' => 'no-es-una-fecha',
            ])
            ->assertRedirect(route('games.create'))
            ->assertSessionHasErrors(['name', 'description', 'rating', 'release_date']);

        $this->assertDatabaseCount('games', 0);
    }

    public function test_show_displays_a_game(): void
    {
        $game = Game::factory()->create([
            'name' => 'Hades',
            'description' => 'Escapa del inframundo.',
        ]);

        $this->get(route('games.show', $game))
            ->assertOk()
            ->assertSee('Hades')
            ->assertSee('Escapa del inframundo.');
    }

    public function test_show_returns_404_for_a_missing_game(): void
    {
        $this->get('/games/999999')->assertNotFound();
    }

    public function test_edit_form_uses_put_method_and_csrf(): void
    {
        $game = Game::factory()->create(['name' => 'Portal 2']);

        $this->get(route('games.edit', $game))
            ->assertOk()
            ->assertSee('Portal 2')
            ->assertSee('name="_token"', false)
            ->assertSee('name="_method"', false)
            ->assertSee('value="PUT"', false);
    }

    public function test_update_modifies_a_game(): void
    {
        $game = Game::factory()->create(['name' => 'Viejo nombre', 'rating' => 5]);

        $this->put(route('games.update', $game), $this->validPayload([
            'name' => 'Nuevo nombre',
            'rating' => '8.5',
        ]))->assertRedirect(route('games.show', $game))
            ->assertSessionHas('success');

        $game->refresh();

        $this->assertSame('Nuevo nombre', $game->name);
        $this->assertSame('8.5', $game->rating);
    }

    public function test_update_rejects_invalid_data(): void
    {
        $game = Game::factory()->create(['name' => 'Intacto']);

        $this->from(route('games.edit', $game))
            ->put(route('games.update', $game), $this->validPayload(['rating' => '25']))
            ->assertRedirect(route('games.edit', $game))
            ->assertSessionHasErrors('rating');

        $this->assertSame('Intacto', $game->fresh()->name);
    }

    public function test_destroy_deletes_a_game(): void
    {
        $game = Game::factory()->create(['name' => 'Borrame']);

        $this->delete(route('games.destroy', $game))
            ->assertRedirect(route('games.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('games', ['id' => $game->id]);
    }

    public function test_destroy_returns_404_for_a_missing_game(): void
    {
        $this->delete('/games/999999')->assertNotFound();
    }

    public function test_store_accepts_the_rating_boundaries(): void
    {
        $this->post(route('games.store'), $this->validPayload(['name' => 'Nota cero', 'rating' => '0']))
            ->assertRedirect();

        $this->post(route('games.store'), $this->validPayload(['name' => 'Nota diez', 'rating' => '10']))
            ->assertRedirect();

        $this->assertDatabaseHas('games', ['name' => 'Nota cero', 'rating' => 0]);
        $this->assertDatabaseHas('games', ['name' => 'Nota diez', 'rating' => 10]);
    }

    public function test_store_rejects_ratings_outside_the_allowed_range(): void
    {
        foreach (['10.1', '-0.1', '99', 'abc'] as $index => $rating) {
            $this->from(route('games.create'))
                ->post(route('games.store'), $this->validPayload([
                    'name' => "Fuera de rango {$index}",
                    'rating' => $rating,
                ]))
                ->assertRedirect(route('games.create'))
                ->assertSessionHasErrors('rating');
        }

        $this->assertDatabaseCount('games', 0);
    }

    public function test_update_rejects_ratings_outside_the_allowed_range(): void
    {
        $game = Game::factory()->create(['rating' => 5]);

        $this->from(route('games.edit', $game))
            ->put(route('games.update', $game), $this->validPayload(['rating' => '10.5']))
            ->assertRedirect(route('games.edit', $game))
            ->assertSessionHasErrors('rating');

        $this->assertSame('5.0', $game->fresh()->rating);
    }

    public function test_model_rejects_an_out_of_range_rating_even_without_form_validation(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Game::factory()->create(['rating' => 10.5]);
    }
}
