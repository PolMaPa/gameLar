# GameHub · Catálogo de videojuegos

Aplicación web construida con **Laravel 12**, **Blade**, **Tailwind CSS 4** y **SQLite** para gestionar un catálogo de videojuegos: alta, consulta, edición y eliminación, con búsqueda, filtros, paginación y validación en servidor.

> El nombre visible se toma de `APP_NAME`. En `.env.example` aparece como `gameLar`; si quieres que muestre **GameHub**, cámbialo en tu `.env` a `APP_NAME=GameHub` y ejecuta `php artisan config:clear`.

---

## Funcionalidades

- CRUD completo de videojuegos (nombre, descripción, género, plataforma, fecha, valoración e imagen).
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
| PHP | 8.2 o superior |
| Composer | 2.x |
| Node.js | 20 o superior |
| npm | 10 o superior |
| Base de datos | SQLite (incluida en PHP) |

Extensiones PHP necesarias: `openssl`, `pdo`, `pdo_sqlite`, `sqlite3`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`.

> En XAMPP, `pdo_sqlite` y `sqlite3` suelen venir activadas. Comprueba con `php -m`.

---

## Instalación

```powershell
# 1. Clona el repositorio
git clone https://github.com/TU-USUARIO/TU-REPOSITORIO.git
cd gameLar

# 2. Instala dependencias, crea .env, genera la clave,
#    crea la base SQLite, migra y compila los assets
composer run setup

# 3. Carga los datos de ejemplo (12 videojuegos)
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

| Método | Ruta | Acción |
|---|---|---|
| `GET` | `/` | Redirige a `/games` |
| `GET` | `/games` | Catálogo con búsqueda, filtros y paginación |
| `GET` | `/games/create` | Formulario de creación |
| `POST` | `/games` | Guarda un videojuego |
| `GET` | `/games/{game}` | Detalle |
| `GET` | `/games/{game}/edit` | Formulario de edición |
| `PUT/PATCH` | `/games/{game}` | Actualiza |
| `DELETE` | `/games/{game}` | Elimina |

Parámetros del catálogo: `?search=`, `?genre=`, `?platform=`, `?page=`.

---

## Pruebas

El proyecto usa **PHPUnit 11** (no Pest). La suite corre sobre **SQLite en memoria** (`:memory:`), por lo que **no toca ni borra** la base de datos local.

```powershell
php artisan test
# o
composer test
```

Cobertura actual:

- `tests/Feature/CatalogTest.php`: orden, paginación, búsqueda, filtros, combinación, persistencia de filtros al paginar, filtros inválidos y opciones de los desplegables.
- `tests/Feature/GameCrudTest.php`: listado, alta, detalle, edición, borrado, 404, validación y límites de valoración (0 y 10), incluido el guard del modelo.
- `tests/Feature/DatabaseIsolationTest.php`: confirma que se usa SQLite `:memory:`.
- `tests/Feature/ExampleTest.php`: la raíz redirige al catálogo.

Para comprobar el estilo del código:

```powershell
vendor/bin/pint --test
```

---

## Estructura relevante

```
app/
  Http/Controllers/GameController.php   CRUD + búsqueda, filtros y estadísticas
  Http/Requests/GameRequest.php         Validación compartida de alta y edición
  Models/Game.php                       Modelo, casts y guard de valoración
database/
  migrations/2026_10_29_150603_create_games_table.php
  factories/GameFactory.php
  seeders/GameSeeder.php                12 juegos de ejemplo
resources/
  css/app.css                           Tema Tailwind y componentes
  views/layouts/app.blade.php           Plantilla común
  views/components/                     Tarjetas, póster, campos, estado vacío
  views/games/                          index, show, create, edit y _form
  views/vendor/pagination/              Paginación personalizada
tests/Feature/                          Suite de pruebas
```

---

## Seguridad

- `.env` **no se versiona** (está en `.gitignore`); usa `.env.example` como plantilla.
- `database/database.sqlite`, `vendor/`, `node_modules/` y `public/build/` tampoco se versionan.
- `APP_KEY` se genera por entorno con `php artisan key:generate`; nunca la compartas.
- No hay claves, tokens ni credenciales escritas en el frontend.

---

## Solución de problemas

| Síntoma | Causa / solución |
|---|---|
| `Vite manifest not found` | Ejecuta `npm run build` (o `npm run dev`). |
| `Database file ... does not exist` | `php artisan migrate --force` la crea; también puedes crear `database/database.sqlite` vacío. |
| `419 Page Expired` | Falta `APP_KEY` o cambió: `php artisan key:generate`. |
| Los cambios de CSS no se ven | Recompila con `npm run build` o usa `composer run dev`. |
| Aviso de extensión `intl` en XAMPP | Afecta solo a `php artisan db:show`, no a la aplicación. |

---

## Licencia

Proyecto con fines educativos. Laravel se distribuye bajo licencia MIT.
