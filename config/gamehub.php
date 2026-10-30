<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cuenta de administrador inicial
    |--------------------------------------------------------------------------
    |
    | La usan AdminUserSeeder y el comando gamehub:admin. Nunca se guardan
    | credenciales en el repositorio: los valores llegan por variables de
    | entorno (.env local o .env.production en el VPS).
    |
    | Si ADMIN_PASSWORD queda vacío en producción, el seeder genera una
    | contraseña aleatoria y la muestra una única vez por consola.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrador GameHub'),
        'email' => env('ADMIN_EMAIL', env('APP_ENV') === 'production' ? 'admin@gamehub.local' : 'admin@gamehub.test'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];
