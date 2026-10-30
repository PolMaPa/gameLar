<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    /**
     * @var string
     */
    protected $signature = 'gamehub:admin
                            {email? : Correo del administrador}
                            {--name= : Nombre visible de la cuenta}';

    /**
     * @var string
     */
    protected $description = 'Crea un administrador o promociona una cuenta existente (contraseña pedida de forma segura)';

    public function handle(): int
    {
        $email = (string) ($this->argument('email') ?: $this->ask('Correo del administrador', (string) config('gamehub.admin.email')));

        $validator = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'email', 'max:255']],
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first('email'));

            return self::FAILURE;
        }

        $name = (string) ($this->option('name') ?: config('gamehub.admin.name'));

        $user = User::query()->where('email', $email)->first();

        if ($user !== null) {
            if (! $user->isAdmin()) {
                $user->forceFill(['is_admin' => true])->save();
                $this->info("Cuenta promocionada a administrador: {$email}");
            } else {
                $this->info("La cuenta ya es administradora: {$email}");
            }

            return self::SUCCESS;
        }

        $password = (string) $this->secret('Contraseña (mínimo 12 caracteres)');

        if (mb_strlen($password) < 12) {
            $this->error('La contraseña debe tener al menos 12 caracteres.');

            return self::FAILURE;
        }

        if ($password !== (string) $this->secret('Repite la contraseña')) {
            $this->error('Las contraseñas no coinciden.');

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'is_admin' => true,
        ]);

        $this->info("Administrador creado: {$email}");

        return self::SUCCESS;
    }
}
