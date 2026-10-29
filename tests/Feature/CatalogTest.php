<?php

namespace Tests\Feature;

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalogue_is_ordered_by_name(): void
    {
        Game::factory()->create(['name' => 'Zelda']);
        Game::factory()->create(['name' => 'Celeste']);
        Game::factory()->create(['name' => 'Hades']);

        $this->get(route('games.index'))
            ->assertOk()
            ->assertSeeInOrder(['Celeste', 'Hades', 'Zelda']);
    }

    public function test_catalogue_is_paginated(): void
    {
        Game::factory()->count(12)->create();

        $first = $this->get(route('games.index'));

        $first->assertOk();
        $this->assertCount(9, $first->viewData('games')->items());
        $this->assertSame(12, $first->viewData('games')->total());

        $second = $this->get(route('games.index', ['page' => 2]));

        $second->assertOk();
        $this->assertCount(3, $second->viewData('games')->items());
    }

    public function test_search_filters_games_by_name(): void
    {
        Game::factory()->create(['name' => 'The Legend of Zelda']);
        Game::factory()->create(['name' => 'Celeste']);

        $this->get(route('games.index', ['search' => 'zelda']))
            ->assertOk()
            ->assertSee('The Legend of Zelda')
            ->assertDontSee('Celeste');
    }

    public function test_search_without_matches_shows_the_empty_state(): void
    {
        Game::factory()->create(['name' => 'Celeste']);

        $this->get(route('games.index', ['search' => 'no-existe']))
            ->assertOk()
            ->assertSee('Sin resultados')
            ->assertSee('Limpiar filtros');
    }

    public function test_games_can_be_filtered_by_genre(): void
    {
        Game::factory()->create(['name' => 'Hades', 'genre' => 'Roguelike']);
        Game::factory()->create(['name' => 'Celeste', 'genre' => 'Platformer']);

        $this->get(route('games.index', ['genre' => 'Roguelike']))
            ->assertOk()
            ->assertSee('Hades')
            ->assertDontSee('Celeste');
    }

    public function test_games_can_be_filtered_by_platform(): void
    {
        Game::factory()->create(['name' => 'Hades', 'platform' => 'Nintendo Switch']);
        Game::factory()->create(['name' => 'Elden Ring', 'platform' => 'PC']);

        $this->get(route('games.index', ['platform' => 'PC']))
            ->assertOk()
            ->assertSee('Elden Ring')
            ->assertDontSee('Hades');
    }

    public function test_search_and_filters_can_be_combined(): void
    {
        Game::factory()->create(['name' => 'Hades', 'genre' => 'Roguelike', 'platform' => 'PC']);
        Game::factory()->create(['name' => 'Hades II', 'genre' => 'Roguelike', 'platform' => 'PC']);
        Game::factory()->create(['name' => 'Celeste', 'genre' => 'Platformer', 'platform' => 'PC']);

        $this->get(route('games.index', [
            'search' => 'Hades',
            'genre' => 'Roguelike',
            'platform' => 'PC',
        ]))
            ->assertOk()
            ->assertSee('Hades')
            ->assertSee('Hades II')
            ->assertDontSee('Celeste');
    }

    public function test_pagination_links_keep_the_active_filters(): void
    {
        Game::factory()->count(12)->create(['genre' => 'RPG']);

        $first = $this->get(route('games.index', ['genre' => 'RPG']));

        $first->assertOk()
            ->assertSee('genre=RPG', false)
            ->assertSee('page=2', false);

        $this->get(route('games.index', ['genre' => 'RPG', 'page' => 2]))
            ->assertOk()
            ->assertSee('genre=RPG', false);
    }

    public function test_invalid_filter_values_are_rejected(): void
    {
        $this->from(route('games.index'))
            ->get(route('games.index', ['search' => str_repeat('a', 151)]))
            ->assertRedirect(route('games.index'))
            ->assertSessionHasErrors('search');
    }

    public function test_filter_selects_only_list_values_present_in_the_catalogue(): void
    {
        Game::factory()->create(['genre' => 'RPG', 'platform' => 'PC']);

        $response = $this->get(route('games.index'));

        $this->assertCount(1, $response->viewData('genres'));
        $this->assertCount(1, $response->viewData('platforms'));
    }
}
