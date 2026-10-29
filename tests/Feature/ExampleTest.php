<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La raíz redirige al catálogo de videojuegos.
     */
    public function test_the_home_page_redirects_to_the_game_catalogue(): void
    {
        $this->get('/')->assertRedirect(route('games.index'));
    }
}
