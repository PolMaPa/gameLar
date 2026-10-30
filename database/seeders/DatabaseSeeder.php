<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * El orden importa: primero la cuenta de administrador y después el
     * catálogo de demostración. Ambos seeders son idempotentes.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            GameSeeder::class,
        ]);
    }
}
