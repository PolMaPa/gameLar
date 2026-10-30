<?php

namespace Database\Seeders;

use App\Models\Game;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    /**
     * Videojuegos de ejemplo para el catálogo.
     *
     * @var list<array<string, mixed>>
     */
    private const GAMES = [
        [
            'name' => 'The Legend of Zelda: Breath of the Wild',
            'description' => 'Link despierta tras cien años de letargo para explorar un vasto mundo abierto, resolver santuarios y derrotar a Ganon.',
            'genre' => 'Action-Adventure',
            'platform' => 'Nintendo Switch',
            'release_date' => '2017-03-03',
            'rating' => 9.7,
            'image_url' => '/images/games/breath-of-the-wild.svg',
        ],
        [
            'name' => 'Red Dead Redemption 2',
            'description' => 'La historia del forajido Arthur Morgan y la banda de Van der Linde en el crepúsculo del Salvaje Oeste.',
            'genre' => 'Action-Adventure',
            'platform' => 'PlayStation 4',
            'release_date' => '2018-10-26',
            'rating' => 9.7,
            'image_url' => '/images/games/red-dead-redemption-2.svg',
        ],
        [
            'name' => 'The Witcher 3: Wild Hunt',
            'description' => 'Geralt de Rivia busca a Ciri recorriendo un continente devastado por la guerra y plagado de monstruos.',
            'genre' => 'RPG',
            'platform' => 'PC',
            'release_date' => '2015-05-19',
            'rating' => 9.8,
            'image_url' => '/images/games/the-witcher-3.svg',
        ],
        [
            'name' => 'Elden Ring',
            'description' => 'Un mundo abierto de fantasía oscura creado por FromSoftware y George R. R. Martin, donde cada muerte enseña algo.',
            'genre' => 'Action RPG',
            'platform' => 'PC',
            'release_date' => '2022-02-25',
            'rating' => 9.6,
            'image_url' => '/images/games/elden-ring.svg',
        ],
        [
            'name' => 'Hollow Knight',
            'description' => 'Un metroidvania dibujado a mano sobre un caballero que explora las ruinas del reino subterráneo de Hallownest.',
            'genre' => 'Metroidvania',
            'platform' => 'PC',
            'release_date' => '2017-02-24',
            'rating' => 9.0,
            'image_url' => '/images/games/hollow-knight.svg',
        ],
        [
            'name' => 'Hades',
            'description' => 'Roguelike de acción en el que Zagreus intenta escapar del inframundo griego una y otra vez.',
            'genre' => 'Roguelike',
            'platform' => 'PC',
            'release_date' => '2020-09-17',
            'rating' => 9.3,
            'image_url' => '/images/games/hades.svg',
        ],
        [
            'name' => 'Stardew Valley',
            'description' => 'Heredas una granja abandonada y construyes una nueva vida cultivando, pescando y conociendo al pueblo.',
            'genre' => 'Simulation',
            'platform' => 'PC',
            'release_date' => '2016-02-26',
            'rating' => 8.9,
            'image_url' => '/images/games/stardew-valley.svg',
        ],
        [
            'name' => 'Celeste',
            'description' => 'Plataformas de precisión sobre Madeline y su ascenso al monte Celeste, con una historia sobre la ansiedad.',
            'genre' => 'Platformer',
            'platform' => 'PC',
            'release_date' => '2018-01-25',
            'rating' => 9.1,
            'image_url' => '/images/games/celeste.svg',
        ],
        [
            'name' => 'God of War',
            'description' => 'Kratos y su hijo Atreus recorren los reinos nórdicos en una aventura de acción y vínculo familiar.',
            'genre' => 'Action-Adventure',
            'platform' => 'PlayStation 4',
            'release_date' => '2018-04-20',
            'rating' => 9.4,
            'image_url' => '/images/games/god-of-war.svg',
        ],
        [
            'name' => 'Portal 2',
            'description' => 'Puzles en primera persona con la pistola de portales de Aperture Science y el humor de GLaDOS.',
            'genre' => 'Puzzle',
            'platform' => 'PC',
            'release_date' => '2011-04-19',
            'rating' => 9.5,
            'image_url' => '/images/games/portal-2.svg',
        ],
        [
            'name' => 'Minecraft',
            'description' => 'Sandbox de construcción y supervivencia en un mundo de bloques generado proceduralmente.',
            'genre' => 'Sandbox',
            'platform' => 'PC',
            'release_date' => '2011-11-18',
            'rating' => 8.8,
            'image_url' => null,
        ],
        [
            'name' => 'Hollow Knight: Silksong',
            'description' => 'Secuela de Hollow Knight protagonizada por Hornet en el reino de Pharloom. Aún sin fecha confirmada.',
            'genre' => 'Metroidvania',
            'platform' => 'Nintendo Switch',
            'release_date' => null,
            'rating' => null,
            'image_url' => null,
        ],
    ];

    /**
     * Run the database seeds.
     *
     * Usa updateOrCreate para que el seeder sea idempotente: se puede
     * ejecutar varias veces sin duplicar registros ni borrar datos.
     */
    public function run(): void
    {
        foreach (self::GAMES as $game) {
            Game::updateOrCreate(
                [
                    'name' => $game['name'],
                    'platform' => $game['platform'],
                ],
                $game,
            );
        }
    }
}
