# GameHub · Catálogo de videojuegos

Aplicación web educativa construida con **Laravel 12**, **Blade** y **Tailwind CSS 4** para consultar y administrar un catálogo de videojuegos. Incluye Docker (Nginx + PHP-FPM + MySQL 8.4), integración continua con GitHub Actions y la base para el despliegue automático en un VPS Ubuntu por SSH.

> Ejercicio de clase. El objetivo es demostrar el ciclo completo: desarrollo Laravel, contenedores, repositorio, CI y CD.

---

## Funcionalidades

- **Página de inicio** con presentación, estadísticas y juegos mejor valorados.
- **Catálogo público** en tarjetas con nombre, descripción, género, plataforma, fecha, valoración e imagen.
- **Búsqueda por nombre** y **filtros por género y plataforma**, combinables y conservados al paginar.
- **Página de detalle** de cada videojuego.
- **Login** y registro de usuarios.
- **Rol de administrador**: solo las cuentas con `is_admin` pueden gestionar el catálogo.
- **Panel de administración** en `/admin` con métricas, búsqueda y tabla de gestión.
- **Formularios de creación y edición** con validación en servidor.
- **Confirmación de eliminación** en el detalle, las tarjetas y el panel.
- Valoraciones restringidas a **0–10**, validadas en el formulario y en el modelo.
- Mensajes de éxito, errores de validación y estados vacíos.
- Diseño responsive, accesible y sin dependencias de servicios privados.

---

## Tecnologías

| Capa | Tecnología |
|---|---|
| Backend | Laravel 12.70 · PHP 8.4 |
| Vistas | Blade + Tailwind CSS 4 |
| Base de datos | MySQL 8.4 (Docker) · SQLite (desarrollo sin Docker y tests) |
| Servidor web | Nginx |
| PHP | PHP-FPM 8.4 |
| Contenedores | Docker + Docker Compose |
| Frontend build | Node.js 22 + Vite 7 |
| CI/CD | GitHub Actions |
| Producción | Ubuntu Server + Docker Compose + SSH |

---

## Requisitos

**Con Docker (recomendado):**

- Docker Engine 24+ con Compose v2 (Docker Desktop en Windows 11 con WSL 2).
- Puertos libres: `8080` (desarrollo) y opcionalmente `8081` para probar producción en local.

**Sin Docker:**

- PHP 8.2+ con `pdo_sqlite`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`.
- Composer 2.x.
- Node.js 20.19+ o 22.12+ y npm.

---

## Inicio rápido con Docker

```powershell
# 1. Crear el entorno local (no se versiona)
Copy-Item .env.docker.example .env.docker

# 2. Rellenar en .env.docker: APP_KEY, DB_PASSWORD y DB_ROOT_PASSWORD
php artisan key:generate --show

# 3. Construir y arrancar Nginx, PHP-FPM y MySQL
docker compose --env-file .env.docker up -d --build

# 4. Sembrar el catálogo y la cuenta administradora
docker compose --env-file .env.docker exec app php artisan db:seed --force
```

- Aplicación: <http://localhost:8080>
- Catálogo: <http://localhost:8080/games>
- Panel: <http://localhost:8080/admin>
- Salud: <http://localhost:8080/up>

> `--env-file .env.docker` es obligatorio. Sin él, Compose leería el `.env` de Laravel (SQLite).

Guía completa de Docker, persistencia, seguridad y solución de problemas: **[`docs/DOCKER.md`](docs/DOCKER.md)**.

---

## Inicio rápido sin Docker (SQLite)

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Abre <http://127.0.0.1:8000>.

`composer run setup` automatiza los pasos anteriores.

---

## Cuentas y roles

### Administrador local

El seeder `AdminUserSeeder` crea (o promociona) una cuenta administradora de forma **idempotente**:

| Entorno | Correo | Contraseña |
|---|---|---|
| Local / testing | `admin@gamehub.test` | `password` |
| Producción | `ADMIN_EMAIL` | `ADMIN_PASSWORD` o una aleatoria generada por el seeder |

- En **producción no hay contraseñas escritas en el repositorio**. Si `ADMIN_PASSWORD` está vacío, el seeder genera una contraseña aleatoria y la muestra **una sola vez** por consola.
- La cuenta antigua `test@example.com` / `password` sigue existiendo en bases de datos ya sembradas y se usa para comprobar que **un usuario normal no puede administrar**.

### Crear o promocionar un administrador

```powershell
# Pide la contraseña de forma oculta (no queda en el historial)
php artisan gamehub:admin admin@example.com --name="Admin GameHub"
```

Si el correo ya existe, solo activa `is_admin`; no cambia la contraseña ni duplica la cuenta.

### Variables de entorno

```ini
ADMIN_NAME="Administrador GameHub"
ADMIN_EMAIL=admin@tu-dominio.example
# ADMIN_PASSWORD=una-contraseña-larga
```

---

## Rutas principales

| Método | Ruta | Acción | Acceso |
|---|---|---|---|
| `GET` | `/` | Página de inicio | Público |
| `GET` | `/games` | Catálogo con búsqueda, filtros y paginación | Público |
| `GET` | `/games/{game}` | Detalle | Público |
| `GET` | `/register` | Formulario de registro | Invitados |
| `POST` | `/register` | Crea una cuenta normal (nunca administradora) | Invitados |
| `GET` | `/login` | Formulario de login | Invitados |
| `POST` | `/login` | Inicia sesión | Invitados |
| `POST` | `/logout` | Cierra sesión | Autenticado |
| `GET` | `/admin` | Panel de administración | Administrador |
| `GET` | `/games/create` | Formulario de creación | Administrador |
| `POST` | `/games` | Guarda un videojuego | Administrador |
| `GET` | `/games/{game}/edit` | Formulario de edición | Administrador |
| `PUT/PATCH` | `/games/{game}` | Actualiza | Administrador |
| `DELETE` | `/games/{game}` | Elimina | Administrador |

Parámetros del catálogo: `?search=`, `?genre=`, `?platform=`, `?page=`.

La protección es doble:

1. Middleware `auth` + `admin` en las rutas de gestión.
2. `GameRequest::authorize()` exige `is_admin`, aunque se invoque la petición por otra vía.

Un visitante sin sesión se redirige a `/login`; un usuario autenticado sin rol recibe **403**.

---

## Pruebas

La suite usa **PHPUnit 11** y **SQLite en memoria** (`:memory:`), por lo que no toca la base de datos local.

```powershell
php artisan test
vendor\bin\pint --test
```

Cobertura actual (**58 pruebas, 245 aserciones**):

- `tests/Feature/AuthTest.php`: login, credenciales inválidas, registro, email duplicado, contraseña no confirmada, logout, redirección de administradores al panel y prohibición de autoasignarse `is_admin`.
- `tests/Feature/CatalogTest.php`: orden, paginación, búsqueda, filtros, combinación, persistencia al paginar, filtros inválidos y opciones de los desplegables.
- `tests/Feature/GameCrudTest.php`: listado, detalle, alta, edición, borrado, 404, validación, límites 0–10, guard del modelo, acciones visibles solo para administradores, invitados redirigidos y usuarios normales con 403.
- `tests/Feature/AdminPanelTest.php`: acceso al panel, 403 para usuarios normales, búsqueda y estado vacío.
- `tests/Feature/SeederTest.php`: seeder de administrador idempotente, promoción sin duplicados, 12 juegos y portadas locales existentes.
- `tests/Feature/ExampleTest.php`: página de inicio y destacados.
- `tests/Feature/DatabaseIsolationTest.php`: confirma el uso de SQLite `:memory:`.

---

## Estructura relevante

```text
app/
  Console/Commands/CreateAdmin.php       Alta/promoción segura de administradores
  Http/Controllers/AdminController.php   Panel de administración
  Http/Controllers/HomeController.php    Página de inicio
  Http/Controllers/GameController.php    CRUD + búsqueda, filtros y estadísticas
  Http/Middleware/EnsureUserIsAdmin.php  Middleware del rol admin
  Http/Requests/GameRequest.php          Validación y autorización
  Models/Game.php                        Modelo, casts y guard de valoración
  Models/User.php                        Rol is_admin y helper isAdmin()
config/gamehub.php                       Cuenta administradora inicial
database/
  migrations/                            Usuarios (+is_admin), caché, jobs y juegos
  seeders/AdminUserSeeder.php            Administrador idempotente
  seeders/GameSeeder.php                 12 juegos con portadas locales
resources/views/
  home.blade.php                         Página de inicio
  admin/index.blade.php                  Panel de administración
  games/                                 index, show, create, edit y _form
  layouts/app.blade.php                  Navegación por rol
public/images/games/                     Portadas SVG locales
scripts/generate-sample-posters.php      Generador reproducible de portadas
routes/web.php                           Rutas públicas y de administración
tests/Feature/                           Suite de pruebas
.github/workflows/ci.yml                 CI: pruebas, Pint, build y MySQL 8.4
.github/workflows/cd.yml                 CD (se sustituirá por SSH al VPS)
Dockerfile                               Imagen multi-etapa
docker-compose.yml                       Stack de desarrollo
docker-compose.prod.yml                  Stack de producción
docker/                                  Configuración de PHP-FPM y Nginx
docs/DOCKER.md                           Guía de Docker y despliegue
```

---

## Arquitectura Docker

```text
Navegador
    │ :8080 (dev) / :80 (prod)
    ▼
Nginx ──FastCGI :9000──► PHP-FPM 8.4 ──MySQL :3306──► MySQL 8.4
  │                                                       │
  └─ assets compilados por Vite                    volumen persistente
```

- **Nginx** es el único servicio web público. Sirve estáticos y pasa PHP a FastCGI.
- **PHP-FPM** ejecuta Laravel; espera a MySQL en el entrypoint.
- **MySQL 8.4** guarda los datos en un volumen con nombre.
- En producción, MySQL vive en una red interna y **no publica puertos**.
- La imagen se construye en cuatro etapas: `vendor`, `assets`, `runtime` y `webserver`.

Detalles en **[`docs/DOCKER.md`](docs/DOCKER.md)**.

---

## Integración continua (CI)

`.github/workflows/ci.yml` se ejecuta en cada **push a `main`**, en **pull requests** y manualmente.

Hace dos trabajos en paralelo:

1. **Pruebas y compilación**: PHP 8.4, Composer, Node 22, `npm run build`, `pint --test` y `php artisan test` sobre SQLite.
2. **Compatibilidad MySQL 8.4**: servicio MySQL 8.4 real, migraciones, seed ejecutado dos veces y comprobación de 12 juegos + 1 administrador.

Si cualquier test falla, el workflow se marca en rojo. Resultado en **GitHub → Actions**.

---

## Despliegue (CD)

**Estado actual:** el destino es un **VPS Ubuntu con Docker Compose**. El despliegue real y el CD por SSH se completarán en las fases 7–12. Todavía **no hay workflow de CD activo**: el anterior (Laravel Cloud) se ha retirado porque esa plataforma no forma parte del ejercicio.

El futuro despliegue automático hará:

1. Esperar a que CI esté en verde para `main`.
2. Conectarse al VPS con una clave de despliegue propia.
3. `git fetch` + `git checkout` del commit aprobado.
4. `docker compose -f docker-compose.prod.yml up -d --build`.
5. `php artisan migrate --force` y `php artisan optimize`.
6. Comprobar `/up` y el estado de los contenedores.

El `.env.production` permanece en el VPS y los volúmenes nunca se borran.

---

## Seguridad

- `.env`, `.env.docker` y `.env.production` **no se versionan**; solo se publican los `*.example`.
- No hay contraseñas reales en el repositorio.
- El registro público crea siempre usuarios **sin** permisos de administración.
- En producción: `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`.
- MySQL de producción no expone el puerto 3306 y usa una red interna.
- Nginx bloquea el acceso a archivos ocultos (`.env`, `.git`, etc.).
- Los secretos de CI/CD se guardan en **GitHub Secrets**, nunca en el YAML.
- `APP_KEY` es distinta por entorno y se conserva entre despliegues.

---

## Solución de problemas

| Síntoma | Causa / solución |
|---|---|
| `Vite manifest not found` | Ejecuta `npm run build` o `composer run dev`. |
| `Database file ... does not exist` | `php artisan migrate --force` la crea. |
| `419 Page Expired` | Falta `APP_KEY`, cambió o la cookie `secure` no encaja con HTTP. |
| `env file .env.docker not found` | `Copy-Item .env.docker.example .env.docker`. |
| `DB_PASSWORD is required` | Falta `--env-file .env.docker`. |
| 403 en `/admin` | La cuenta no tiene `is_admin`; usa `php artisan gamehub:admin`. |
| 502 Bad Gateway | PHP-FPM aún no está listo; revisa `docker compose logs app`. |
| Los cambios no aparecen en Docker | `docker compose --env-file .env.docker up -d --build`. |

---

## Licencia

Proyecto con fines educativos. Laravel se distribuye bajo licencia MIT.
