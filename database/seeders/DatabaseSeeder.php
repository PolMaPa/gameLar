<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // En producción solo se siembra el catálogo demo. No se crean
        // usuarios de prueba: las cuentas se crean desde el registro público.
        if (app()->environment('production')) {
            $this->call([
                GameSeeder::class,
            ]);

            return;
        }

        // Idempotente: no duplica el usuario de prueba si se vuelve a sembrar.
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
            ],
        );

        $this->call([
            GameSeeder::class,
        ]);
    }
}
