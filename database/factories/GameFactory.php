<?php

namespace Database\Factories;

use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    /**
     * Géneros y plataformas realistas para los datos de prueba.
     *
     * @var list<string>
     */
    private const GENRES = [
        'Action-Adventure',
        'Action RPG',
        'RPG',
        'Metroidvania',
        'Roguelike',
        'Platformer',
        'Puzzle',
        'Sandbox',
        'Simulation',
        'Strategy',
    ];

    /**
     * @var list<string>
     */
    private const PLATFORMS = [
        'PC',
        'PlayStation 5',
        'PlayStation 4',
        'Xbox Series X|S',
        'Xbox One',
        'Nintendo Switch',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $slug = str($name)->slug();

        return [
            'name' => str($name)->title()->limit(150, ''),
            'description' => fake()->paragraph(3),
            'genre' => fake()->randomElement(self::GENRES),
            'platform' => fake()->randomElement(self::PLATFORMS),
            'release_date' => fake()->dateTimeBetween('-30 years', 'now')->format('Y-m-d'),
            'rating' => fake()->randomFloat(1, 0, 10),
            // 20 % de los juegos sin imagen para probar el campo nullable.
            'image_url' => fake()->boolean(80) ? "/images/games/{$slug}.svg" : null,
        ];
    }
}
