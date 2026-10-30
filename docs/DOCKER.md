# Docker · GameHub

Guía de la infraestructura Docker de **GameHub** siguiendo la arquitectura de clase:

```text
Navegador
    │
    ▼
Nginx  ──(FastCGI :9000)──►  PHP-FPM 8.4  ──(MySQL :3306)──►  MySQL 8.4
  │                                                              │
  └── assets compilados (Vite)                                   └── volumen persistente
```

- **Nginx** sirve los archivos estáticos y pasa las peticiones PHP a PHP-FPM.
- **PHP-FPM** ejecuta Laravel 12 con PHP 8.4.
- **MySQL 8.4** guarda los datos en un volumen con nombre (no se pierden al reiniciar).
- **Docker Compose** orquesta los tres servicios, las redes y los volúmenes.
- **Variables de entorno** separadas para desarrollo y producción, fuera del repositorio.

> Esta guía cubre la **Fase 2** (Docker local + Docker de producción preparado).
> El despliegue real en el VPS Ubuntu y el CD con GitHub Actions son las fases 3 y 4.

---

## 1. Archivos creados

| Archivo | Función |
|---|---|
| `Dockerfile` | Imagen multi-etapa: `vendor` (Composer), `assets` (Vite), `runtime` (PHP-FPM 8.4) y `webserver` (Nginx). |
| `.dockerignore` | Evita enviar al build `.env`, `vendor/`, `node_modules/`, SQLite local, etc. |
| `docker/php/php.ini` | `memory_limit`, OPcache, zona horaria y límites de subida. |
| `docker/php/entrypoint.sh` | Espera a MySQL, ajusta permisos, migra si se pide y arranca `php-fpm`. |
| `docker/nginx/default.conf` | Nginx de desarrollo (HTTP, puerto 8080 del host). |
| `docker/nginx/prod.conf` | Nginx de producción (compresión, caché de assets, cabeceras de seguridad, bloque TLS comentado). |
| `docker-compose.yml` | Stack de **desarrollo local**. |
| `docker-compose.prod.yml` | Stack de **producción** (VPS). |
| `.env.docker.example` | Plantilla de variables para desarrollo. |
| `.env.production.example` | Plantilla de variables para producción. |

Los archivos reales `.env.docker` y `.env.production` **no se versionan**: `.gitignore` excluye `.env.*` y solo permite los `*.example`.

---

## 2. Diferencias entre desarrollo y producción

| | Desarrollo (`docker-compose.yml`) | Producción (`docker-compose.prod.yml`) |
|---|---|---|
| Proyecto Compose | `gamehub-dev` | `gamehub-prod` |
| Variables | `.env.docker` | `.env.production` |
| APP_ENV / DEBUG | `local` / `true` | `production` / `false` |
| Nginx | `default.conf`, puerto `${APP_PORT}` (8080) | `prod.conf`, puerto `${HTTP_PORT}` (80) |
| MySQL | publica `${DB_PORT_HOST}` (3307) para clientes SQL | **sin puertos**: solo red interna |
| Migraciones | automáticas al arrancar (`RUN_MIGRATIONS=true`) | **manuales y controladas** |
| Caché Laravel | sin cachear | `php artisan optimize` al arrancar |
| Volúmenes | `gamehub-db-dev`, `gamehub-storage-dev` | `gamehub-db-prod`, `gamehub-storage-prod` |
| Redes | una red `gamehub-dev` | `frontend` (Nginx↔PHP) y `backend` interna (PHP↔MySQL) |
| Reinicio | `unless-stopped` | `always` |

---

## 3. Requisitos

- Docker Engine 24+ con Compose v2 (Docker Desktop en Windows).
- Puertos libres: **8080** (desarrollo) y **8081** si pruebas producción en local.
- El puerto 3307 se usa para MySQL en desarrollo y evita chocar con XAMPP (3306).

---

## 4. Desarrollo local paso a paso

### 4.1 Crear el entorno

```powershell
Copy-Item .env.docker.example .env.docker
```

Rellena en `.env.docker`:

```ini
APP_KEY=base64:...
DB_PASSWORD=una-contrasena-larga
DB_ROOT_PASSWORD=otra-contrasena-larga
```

Genera la clave (no reutilices la de otro entorno):

```powershell
php artisan key:generate --show
```

### 4.2 Construir y arrancar

```powershell
docker compose --env-file .env.docker up -d --build
```

El `--env-file` es obligatorio: Compose necesita esas variables para interpolar el
puerto y las credenciales de MySQL. Sin él, Compose leería el `.env` de Laravel
(que apunta a SQLite) y fallaría.

Al arrancar, el entrypoint:

1. Valida `APP_KEY`.
2. Espera a que MySQL acepte conexiones.
3. Ajusta permisos de `storage/` y `bootstrap/cache/`.
4. Ejecuta `php artisan migrate --force` (`RUN_MIGRATIONS=true`).
5. Arranca `php-fpm`.

### 4.3 Sembrar datos

```powershell
docker compose --env-file .env.docker exec app php artisan db:seed --force
```

En `local`, `DatabaseSeeder` ejecuta `AdminUserSeeder` y `GameSeeder`:

- Crea la cuenta administradora `admin@gamehub.test` / `password` (solo local).
- Carga los 12 juegos demo con portadas SVG locales en `public/images/games/`.
- Es idempotente: repetirlo no duplica registros ni cambia contraseñas existentes.

### 4.4 Abrir la aplicación

- Inicio: <http://localhost:8080/>
- Catálogo: <http://localhost:8080/games>
- Panel de administración: <http://localhost:8080/admin>
- Salud: <http://localhost:8080/up>
- Login: <http://localhost:8080/login>

### 4.5 Comandos útiles

```powershell
# Estado de los servicios
docker compose --env-file .env.docker ps

# Logs en vivo (entrypoint + PHP-FPM + Nginx)
docker compose --env-file .env.docker logs -f app nginx db

# Shell dentro del contenedor
docker compose --env-file .env.docker exec app sh

# Artisan y pruebas dentro del contenedor
docker compose --env-file .env.docker exec app php artisan migrate:status
docker compose --env-file .env.docker exec app php artisan test

# MySQL desde el host
mysql -h 127.0.0.1 -P 3307 -u gamehub -p gamehub

# Parar conservando datos
docker compose --env-file .env.docker down

# Parar y BORRAR volumenes (destructivo: elimina la base de datos)
docker compose --env-file .env.docker down -v
```

> `down` sin `-v` **conserva** los volúmenes. `down -v` los elimina: úsalo solo
> cuando quieras una base de datos limpia.

---

## 5. Persistencia comprobada

Los datos viven en volúmenes con nombre, no dentro del contenedor:

| Volumen | Contenido |
|---|---|
| `gamehub-db-dev` | Datos de MySQL 8.4 |
| `gamehub-storage-dev` | `storage/` de Laravel (logs, archivos) |
| `gamehub-db-prod` | Datos de MySQL en producción |
| `gamehub-storage-prod` | `storage/` en producción |

Comprobación realizada en esta fase:

1. Sembrar → `games=12`, `users=1`.
2. `docker compose restart` → los mismos datos.
3. `docker compose down` (sin `-v`) + `up -d` → los volúmenes siguen ahí y los datos se conservan.

Reproducirlo:

```powershell
docker compose --env-file .env.docker exec app php artisan tinker --execute="echo App\Models\Game::count();"
docker compose --env-file .env.docker restart
docker compose --env-file .env.docker exec app php artisan tinker --execute="echo App\Models\Game::count();"
```

> En PowerShell, si el comando anterior falla por el escapado, usa una tubería:
> `'echo App\Models\Game::count();' | docker compose --env-file .env.docker exec -T app php artisan tinker`

---

## 6. Producción en el VPS (Fase 3)

> Todavía **no** se ha desplegado en ningún servidor. Estos pasos son la base
> para la siguiente fase.

### 6.1 Preparar el VPS Ubuntu

```bash
sudo apt update && sudo apt upgrade -y
# Instalar Docker Engine + Compose plugin segun la documentacion oficial de Docker
sudo usermod -aG docker $USER   # cerrar y volver a entrar
```

### 6.2 Copiar el proyecto y el entorno

```bash
git clone https://github.com/PolMaPa/gameLar.git
cd gameLar
cp .env.production.example .env.production
# Editar .env.production: APP_KEY, APP_URL, dominio, contrasenas
```

### 6.3 Arrancar

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build
```

### 6.4 Migrar y sembrar una sola vez

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml \
  exec app php artisan migrate --force

# Solo la primera vez: catálogo y cuenta administradora.
# Si ADMIN_PASSWORD no está definida, el seeder genera una contraseña
# aleatoria y la muestra una única vez por consola.
docker compose --env-file .env.production -f docker-compose.prod.yml \
  exec app php artisan db:seed --force
```

También se puede crear o promocionar un administrador sin sembrar nada:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml \
  exec app php artisan gamehub:admin admin@tu-dominio.example
```

El comando pide la contraseña de forma oculta. En producción no hay ninguna
contraseña escrita en el repositorio.

### 6.5 Pendiente para la Fase 3

- Dominio y TLS (Certbot o proxy del proveedor) + descomentar el bloque 443 de `prod.conf`.
- Firewall (UFW) permitiendo solo 22, 80 y 443.
- Backups de `gamehub-db-prod` (`mysqldump` programado).
- Rotación de logs.

---

## 7. Seguridad

- Ninguna credencial está en el repositorio: solo plantillas `*.example`.
- `.env.docker` y `.env.production` están ignorados por Git.
- MySQL de producción no publica puertos: vive en una red `internal`.
- Nginx no sirve archivos ocultos (`location ~ /\.`).
- `APP_DEBUG=false` y `SESSION_SECURE_COOKIE=true` en producción.
- El contenedor de la app no se ejecuta como `root` para los workers de PHP-FPM
  (el master lo hace para gestionar el pool; los procesos que atienden peticiones
  corren como `www-data`).
- `APP_KEY` es distinta en cada entorno. Si se filtra, se regenera y se invalida
  la sesión de todos los usuarios.

---

## 8. Solución de problemas

| Síntoma | Causa probable | Solución |
|---|---|---|
| `env file .env.docker not found` | Falta el archivo real | `Copy-Item .env.docker.example .env.docker` |
| `DB_PASSWORD is required` | Variable vacía o falta `--env-file` | Rellenar `.env.docker` y usar `--env-file` |
| `Access denied for user` | Contraseña distinta a la del volumen ya creado | Cambiar la contraseña en MySQL o recrear el volumen con `down -v` |
| `MySQL no respondió en 60 segundos` | El contenedor `db` no arranca | `docker compose logs db` |
| `APP_KEY no está definida` | Falta la clave en producción | `php artisan key:generate --show` y pegarla |
| 502 Bad Gateway | PHP-FPM no está listo | `docker compose logs app`; esperar al healthcheck |
| 404 en `/build/assets/...` | Build de Vite falló | `docker compose build --no-cache nginx` |
| Los cambios de código no aparecen | La imagen está cacheada | `docker compose up -d --build` |
| El catálogo está vacío | Seed no ejecutado | `docker compose exec app php artisan db:seed --force` |
| `419 Page Expired` | `APP_KEY` cambió o cookie `secure` sobre HTTP | Mantener la clave; en local `SESSION_SECURE_COOKIE=false` |

---

## 9. Relación con `DEPLOYMENT.md`

`DEPLOYMENT.md` documenta la vía anterior (Laravel Cloud). Se conserva únicamente
como referencia histórica: **el ejercicio no utiliza Laravel Cloud**. El objetivo
actual es el VPS con Docker Compose descrito en esta guía.

---

## 10. Siguiente fase

**Fase 3 · VPS Ubuntu**: aprovisionar el servidor, publicar con este compose,
configurar dominio/TLS, firewall y backups. Después, **Fase 4 · CI/CD**: construir
la imagen en GitHub Actions y desplegarla en el VPS sin intervención manual.
