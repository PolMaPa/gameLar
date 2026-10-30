# GameHub · Catálogo de videojuegos

Aplicación web construida con **Laravel 12**, **Blade**, **Tailwind CSS 4** y autenticación propia basada en Breeze para gestionar un catálogo de videojuegos: alta, consulta, edición y eliminación, con búsqueda, filtros, paginación y validación en servidor.

En local usa **SQLite**; en producción está preparada para **Laravel Cloud + Laravel MySQL** (SQLite no es válido allí porque el filesystem es efímero).

> El nombre visible se toma de `APP_NAME`. En `.env.example` aparece como `gameLar`; si quieres que muestre **GameHub**, cámbialo en tu `.env` a `APP_NAME=GameHub` y ejecuta `php artisan config:clear`.

---

## Funcionalidades

- CRUD completo de videojuegos (nombre, descripción, género, plataforma, fecha, valoración e imagen).
- Catálogo público: listado y detalle visibles sin iniciar sesión.
- **Login y registro** de usuarios; crear, editar y eliminar requiere sesión.
- Catálogo ordenado alfabéticamente y paginado (9 por página).
- Búsqueda por nombre y filtros por género y plataforma mediante query string (`GET`).
- Los filtros se conservan al cambiar de página.
- Valoraciones restringidas a **0–10**, validadas en el formulario y también en el modelo.
- Mensajes de éxito y resumen de errores de validación.
- Estado vacío y estado «sin resultados».
- Diseño responsive con componentes Blade reutilizables.
- Todas las salidas dinámicas escapadas con `{{ }}` (sin `{!! !!}`).

---

## Requisitos

| Herramienta | Versión |
|---|---|
| PHP | 8.2 o superior (producción: 8.4) |
| Composer | 2.x |
| Node.js | 20.19+ o 22.12+ |
| npm | 10 o superior |
| Base de datos local | SQLite (incluida en PHP) |
| Base de datos en producción | Laravel MySQL |

Extensiones PHP necesarias en local: `openssl`, `pdo`, `pdo_sqlite`, `sqlite3`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`.

> En XAMPP, `pdo_sqlite` y `sqlite3` suelen venir activadas. Comprueba con `php -m`.

---

## Instalación

```powershell
# 1. Clona el repositorio
git clone https://github.com/PolMaPa/gameLar.git
cd gameLar

# 2. Instala dependencias, crea .env, genera la clave,
#    crea la base SQLite, migra y compila los assets
composer run setup

# 3. Carga los datos de ejemplo (12 videojuegos y un usuario de prueba)
php artisan db:seed
```

`composer run setup` ejecuta por ti:

1. `composer install`
2. copia `.env.example` a `.env` si no existe
3. `php artisan key:generate`
4. `php artisan migrate --force` (crea `database/database.sqlite` si falta)
5. `npm install`
6. `npm run build`

### Instalación manual (equivalente)

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
```

### Usuario de prueba (solo local)

`DatabaseSeeder` crea en entornos que no son producción:

| Correo | Contraseña |
|---|---|
| `test@example.com` | `password` |

En producción **no** se crea este usuario: solo se siembran los 12 juegos demo.

---

## Ejecutar el proyecto

```powershell
php artisan serve
```

Abre **http://127.0.0.1:8000**. La raíz `/` redirige a `/games`.

Para desarrollo con recarga de CSS/JS en caliente:

```powershell
composer run dev
```

> `composer run dev` levanta el servidor, el worker de colas y Vite a la vez.

---

## Rutas principales

| Método | Ruta | Acción | Acceso |
|---|---|---|---|
| `GET` | `/` | Redirige a `/games` | Público |
| `GET` | `/games` | Catálogo con búsqueda, filtros y paginación | Público |
| `GET` | `/games/{game}` | Detalle | Público |
| `GET` | `/register` | Formulario de registro | Invitados |
| `POST` | `/register` | Crea la cuenta | Invitados |
| `GET` | `/login` | Formulario de login | Invitados |
| `POST` | `/login` | Inicia sesión | Invitados |
| `POST` | `/logout` | Cierra sesión | Autenticado |
| `GET` | `/games/create` | Formulario de creación | Autenticado |
| `POST` | `/games` | Guarda un videojuego | Autenticado |
| `GET` | `/games/{game}/edit` | Formulario de edición | Autenticado |
| `PUT/PATCH` | `/games/{game}` | Actualiza | Autenticado |
| `DELETE` | `/games/{game}` | Elimina | Autenticado |

Parámetros del catálogo: `?search=`, `?genre=`, `?platform=`, `?page=`.

Las rutas de gestión están protegidas con el middleware `auth`; `GameRequest::authorize()` vuelve a exigir usuario autenticado como segunda capa.

---

## Pruebas

El proyecto usa **PHPUnit 11** (no Pest). La suite corre sobre **SQLite en memoria** (`:memory:`), por lo que **no toca ni borra** la base de datos local.

```powershell
php artisan test
# o
composer test
```

Cobertura actual (43 pruebas):

- `tests/Feature/AuthTest.php`: login, credenciales inválidas, registro, email duplicado, contraseña no confirmada, logout y bloqueo de invitados.
- `tests/Feature/CatalogTest.php`: orden, paginación, búsqueda, filtros, combinación, persistencia de filtros al paginar, filtros inválidos y opciones de los desplegables.
- `tests/Feature/GameCrudTest.php`: listado, alta, detalle, edición, borrado, 404, validación, límites de valoración (0 y 10), guard del modelo, acciones visibles solo con sesión y redirección de invitados a `/login`.
- `tests/Feature/DatabaseIsolationTest.php`: confirma que se usa SQLite `:memory:`.
- `tests/Feature/ExampleTest.php`: la raíz redirige al catálogo.

Para comprobar el estilo del código:

```powershell
vendor\bin\pint --test
```

---

## Estructura relevante

```
app/
  Http/Controllers/Auth/                Login y registro
  Http/Controllers/GameController.php   CRUD + búsqueda, filtros y estadísticas
  Http/Requests/Auth/LoginRequest.php   Autenticación con rate limiting
  Http/Requests/GameRequest.php         Validación compartida de alta y edición
  Models/Game.php                       Modelo, casts y guard de valoración
bootstrap/app.php                       Health check /up y redirección tras login
database/
  migrations/                           Tablas de usuarios, caché, jobs y juegos
  factories/GameFactory.php
  seeders/DatabaseSeeder.php            Usuario demo solo fuera de producción
  seeders/GameSeeder.php                12 juegos de ejemplo
resources/
  css/app.css                           Tema Tailwind y componentes
  views/layouts/app.blade.php           Plantilla común con navegación @auth/@guest
  views/auth/                           login y register
  views/components/                     Tarjetas, póster, campos, estado vacío
  views/games/                          index, show, create, edit y _form
  views/vendor/pagination/              Paginación personalizada
routes/
  auth.php                              Login, registro y logout
  web.php                               Catálogo público / gestión autenticada
tests/Feature/                          Suite de pruebas
.github/workflows/ci.yml                CI: pruebas y compatibilidad MySQL
.github/workflows/cd.yml                CD: deploy hook tras CI en verde
```

---

## Seguridad

- `.env` **no se versiona** (está en `.gitignore`); usa `.env.example` como plantilla.
- `database/database.sqlite`, `vendor/`, `node_modules/` y `public/build/` tampoco se versionan.
- `APP_KEY` se genera por entorno con `php artisan key:generate`; nunca la compartas.
- En producción: `APP_DEBUG=false`, cookies `secure` y sesiones/caché en base de datos.
- El registro es público: cualquier visitante puede crear una cuenta y gestionar el catálogo. Si quieres cerrarlo, consulta `DEPLOYMENT.md`.
- El deploy hook de Laravel Cloud se guarda como secreto de GitHub Actions; nunca se escribe en el repositorio.
- No hay claves, tokens ni credenciales escritas en el frontend.

---

## Despliegue y CI/CD

El runbook completo para **Laravel Cloud + Laravel MySQL** está en [`DEPLOYMENT.md`](DEPLOYMENT.md): conexión con GitHub, variables, build/deploy, health checks, seed inicial, dominio, backups, rollback y CI/CD.

### CI (verificar)

`.github/workflows/ci.yml` se ejecuta en cada push a `main`, en pull requests y manualmente:

- PHPUnit sobre SQLite en memoria.
- Job adicional sobre **MySQL 8.4** con migraciones y seed.
- Pint y build de Vite.
- No despliega nada.

### CD (publicar)

`.github/workflows/cd.yml` se dispara **solo cuando CI termina en verde** para un push a `main`:

1. Comprueba que existe el secreto `LARAVEL_CLOUD_DEPLOY_HOOK`.
2. Hace `POST` al deploy hook oficial de Laravel Cloud con el SHA exacto que pasó el CI.
3. Laravel Cloud construye, migra, comprueba `/up` y publica con zero downtime.

**Push to deploy de Laravel Cloud debe estar desactivado** para no desplegar commits que no han pasado las pruebas. El único mecanismo activo es el deploy hook, guardado como secreto de GitHub Actions.

Resumen de comandos que ejecuta Laravel Cloud:

```sh
# Build
composer install --no-dev --optimize-autoloader && npm ci --audit false && npm run build && php artisan optimize

# Deploy
php artisan migrate --force
```

---

## Solución de problemas

| Síntoma | Causa / solución |
|---|---|
| `Vite manifest not found` | Ejecuta `npm run build` (o `npm run dev`). |
| `Database file ... does not exist` | `php artisan migrate --force` la crea; también puedes crear `database/database.sqlite` vacío. |
| `419 Page Expired` | Falta `APP_KEY` o cambió: `php artisan key:generate`. |
| `Route [login] not defined` | Falta `require __DIR__.'/auth.php'` en `routes/web.php`. |
| Los cambios de CSS no se ven | Recompila con `npm run build` o usa `composer run dev`. |
| Aviso de extensión `intl` en XAMPP | Afecta solo a `php artisan db:show`, no a la aplicación. |

---

## Licencia

Proyecto con fines educativos. Laravel se distribuye bajo licencia MIT.
