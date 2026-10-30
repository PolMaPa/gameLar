<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Crea (o promociona) la cuenta de administrador inicial.
     *
     * - Es idempotente: si el correo ya existe solo garantiza is_admin=true,
     *   sin cambiar la contraseña ni duplicar usuarios.
     * - Nunca escribe credenciales en el código: se leen de config/gamehub.php,
     *   alimentado por ADMIN_NAME, ADMIN_EMAIL y ADMIN_PASSWORD.
     * - Si no hay contraseña configurada:
     *     · en local/testing usa una contraseña de demostración ("password");
     *     · en producción genera una aleatoria y la muestra una sola vez.
     */
    public function run(): void
    {
        $name = (string) config('gamehub.admin.name');
        $email = (string) config('gamehub.admin.email');
        $password = config('gamehub.admin.password');

        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null) {
            if (! $existing->isAdmin()) {
                $existing->forceFill(['is_admin' => true])->save();
                $this->command?->info("Usuario existente promocionado a administrador: {$email}");
            } else {
                $this->command?->info("El administrador ya existe: {$email}");
            }

            return;
        }

        $generated = false;

        if (! is_string($password) || $password === '') {
            if (app()->environment('production')) {
                $password = Str::password(20);
                $generated = true;
            } else {
                $password = 'password';
            }
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'is_admin' => true,
        ]);

        $this->command?->info("Administrador creado: {$email}");

        if ($generated) {
            $this->command?->warn("Contraseña generada: {$password}");
            $this->command?->warn('Guárdala ahora: no se vuelve a mostrar. Cámbiala tras el primer inicio de sesión.');
        } elseif (app()->environment('local', 'testing')) {
            $this->command?->warn('Entorno local: contraseña de demostración "password". No usar en producción.');
        }
    }
}
