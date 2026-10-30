<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * La página de inicio responde y presenta la aplicación.
     */
    public function test_the_home_page_returns_a_successful_response(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Bienvenido a GameHub')
            ->assertSee('Explorar catálogo');
    }

    /**
     * La página de inicio destaca los juegos mejor valorados.
     */
    public function test_the_home_page_shows_the_best_rated_games(): void
    {
        Game::factory()->create(['name' => 'Obra maestra', 'rating' => 9.9]);
        Game::factory()->create(['name' => 'Juego normal', 'rating' => 5.0]);
        Game::factory()->create(['name' => 'Sin nota', 'rating' => null]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Obra maestra')
            ->assertSee('Juego normal')
            ->assertDontSee('Sin nota');
    }
}
