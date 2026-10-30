# Despliegue en Laravel Cloud

Guía operativa para publicar **GameHub** en [Laravel Cloud](https://cloud.laravel.com) con **Laravel MySQL**. Está escrita para ejecutarse desde el panel web (sin SSH) y **no** se ha ejecutado todavía: el servicio aún no existe.

> Documentación oficial consultada:
> - [Guía de despliegue de Laravel](https://laravel.com/cloud/docs/deploy-guides/laravel)
> - [Entornos: build, deploy, variables y filesystem](https://laravel.com/cloud/docs/environments)
> - [Laravel MySQL](https://laravel.com/cloud/docs/resources/databases/laravel-mysql)
> - [Health checks](https://laravel.com/cloud/docs/health-checks)
> - [SQLite no soportado](https://laravel.com/cloud/docs/knowledge-base/sqlite)

---

## 1. Decisiones de producción

| Tema | Decisión |
|---|---|
| Proveedor | Laravel Cloud |
| Base de datos | Laravel MySQL (gestionada) |
| Almacenamiento | Ninguno persistente: solo se guarda `image_url` como texto |
| Autenticación | Login y registro públicos (Breeze adaptado) |
| Catálogo | Lectura pública; crear/editar/eliminar requiere sesión |
| Datos iniciales | 12 juegos demo mediante `GameSeeder` (sin usuario de prueba) |
| Colas / scheduler | No se usan (`QUEUE_CONNECTION=sync`) |
| PHP | 8.4 |
| Node.js | 22 o superior |

SQLite **no es viable** en Laravel Cloud: el filesystem es efímero y se reinicia en cada despliegue, al dormir/despertar y al cambiar de host. Por eso se usa MySQL.

---

## 2. Comprobaciones antes de desplegar

En local, desde `gameLar/`:

```powershell
php artisan test
vendor\bin\pint --test
npm run build
git status
```

Debe pasar la suite completa (43 pruebas), Pint sin cambios y el build de Vite sin errores. No hagas commit ni push sin autorización.

Archivos que deben estar versionados:

- [ ] `app/Http/Controllers/Auth/` (login y registro)
- [ ] `app/Http/Requests/Auth/LoginRequest.php`
- [ ] `routes/auth.php`
- [ ] `resources/views/auth/login.blade.php` y `register.blade.php`
- [ ] `bootstrap/app.php` (redirección de usuarios autenticados)
- [ ] `.env.production.example` y `DEPLOYMENT.md`
- [ ] `.gitignore` con `!.env.*.example`

Nunca versiones `.env`, `APP_KEY`, contraseñas ni credenciales de MySQL.

---

## 3. Crear la aplicación en Laravel Cloud

1. Entra en <https://cloud.laravel.com> y selecciona **+ New application**.
2. Conecta **GitHub** y autoriza el repositorio `PolMaPa/gameLar`.
3. Selecciona el repositorio y la rama `main`.
4. Nombre de la aplicación: `GameHub` (o `gameLar`).
5. Región: la más cercana a tus usuarios. **La base de datos debe estar en la misma región.**
6. Confirma la creación. Laravel Cloud detecta Laravel y prepara el entorno `production`.

> El primer despliegue puede lanzarse al crear la aplicación. Si quieres revisar la configuración antes de publicar, deja la configuración por defecto y **no compartas todavía la URL**.

### Desactivar el despliegue automático

Por ahora no queremos despliegues automáticos:

1. Ve a **Settings → Deployments**.
2. Desactiva **Push to deploy**.
3. A partir de ahí, cada publicación se hace con el botón **Deploy**.

El workflow de GitHub Actions (`.github/workflows/ci.yml`) es independiente y solo ejecuta pruebas; no despliega.

---

## 4. Configurar el runtime

En **Settings → General Settings → Runtime**:

| Ajuste | Valor |
|---|---|
| Framework | Laravel (detectado) |
| PHP | **8.4** |
| Node.js | **22** o superior (solo se usa en build) |
| Start command | El que propone Laravel Cloud (no cambiarlo) |

Extensiones necesarias ya incluidas en Laravel Cloud: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`, `curl`, `dom`.

---

## 5. Adjuntar Laravel MySQL

1. En el canvas del entorno, pulsa **Add database**.
2. Crea un clúster **Laravel MySQL** (o adjunta uno existente).
3. Elige tamaño (Flex si quieres scale-to-zero; Pro si necesitas always-on), almacenamiento y **la misma región** que el entorno.
4. Crea la base de datos con nombre `gamehub` (o el que prefieras).
5. Adjúntala al entorno y **redespliega** para que se inyecten las credenciales.

Laravel Cloud inyecta automáticamente `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD` y `DB_DATABASE`.

> **No definas esas cuatro variables a mano.** Las variables personalizadas tienen prioridad y sobrescribirían las credenciales gestionadas.

En la pestaña **Environment Variables** solo hay que añadir:

```ini
DB_CONNECTION=mysql
DB_PORT=3306
```

---

## 6. Variables de entorno

Copia el resto de valores de `.env.production.example`. Como mínimo:

```ini
APP_NAME=GameHub
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://tu-entorno.laravel.cloud

LOG_CHANNEL=stderr
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_PORT=3306

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync

FILESYSTEM_DISK=local
MAIL_MAILER=log
```

### Generar `APP_KEY`

En local:

```powershell
php artisan key:generate --show
```

Copia la salida completa (`base64:...`) y pégala como `APP_KEY` en Laravel Cloud. No la reutilices de otro entorno ni la subas al repositorio.

### `APP_URL`

Usa primero el dominio gratuito que asigna Laravel Cloud (`https://<entorno>.laravel.cloud`). Cuando conectes un dominio propio, actualiza `APP_URL` y vuelve a desplegar.

Después de cambiar cualquier variable, **redespliega** para que la aplicación la lea.

---

## 7. Comandos de build y deploy

En **Settings → Deployments**:

### Build command

```sh
composer install --no-dev --optimize-autoloader && npm ci --audit false && npm run build && php artisan optimize
```

- `php artisan optimize` va en build para que la caché quede dentro del despliegue.
- No ejecutes `optimize:clear` ni `storage:link` en deploy.

### Deploy command

```sh
php artisan migrate --force
```

- Migraciones hacia delante. **Nunca** `migrate:fresh`, `migrate:refresh` ni `db:wipe`.
- No añadas `db:seed` al deploy: los datos demo se siembran una sola vez (paso 9).

---

## 8. Health checks

En **Settings → Deployments → Health checks**:

| Check | Endpoint |
|---|---|
| Startup | `GET /up` |
| Readiness | `GET /up` |
| Liveness | Dejar el valor por defecto |

`/up` ya está registrado en `bootstrap/app.php`. Un despliegue solo pasa a producción cuando responde correctamente.

---

## 9. Sembrar los 12 juegos demo (una sola vez)

Cuando el primer despliegue esté sano:

1. Abre la pestaña **Commands** del entorno.
2. Ejecuta:

```sh
php artisan db:seed --class=GameSeeder --force
```

Notas:

- `GameSeeder` es idempotente (`updateOrCreate`): no duplica registros.
- **No** se ejecuta `DatabaseSeeder` en producción para no crear el usuario de prueba.
- Si vuelves a lanzar el seeder, restaura los 12 juegos demo y sobrescribe los cambios hechos en esos registros. No lo ejecutes en cada deploy.

---

## 10. Crear la cuenta administradora

1. Abre `https://tu-entorno.laravel.cloud/register`.
2. Crea tu cuenta.
3. Inicia sesión: ya puedes añadir, editar y eliminar juegos.

El registro es **público** por decisión de producto: cualquier visitante puede crear una cuenta y, con ella, gestionar el catálogo. Si más adelante quieres cerrarlo:

- Comenta las rutas `GET/POST register` en `routes/auth.php`, o
- Añade una allowlist de correos en `RegisteredUserController`.

---

## 11. Dominio y TLS

1. En **Settings → Domains**, añade tu dominio (por ejemplo `gamehub.example`).
2. Copia los registros DNS que indique Laravel Cloud en tu proveedor.
3. Espera la verificación; el certificado TLS se emite automáticamente.
4. Actualiza `APP_URL=https://tu-dominio.example` y redespliega.
5. Verifica que `https://tu-dominio.example/up` responde `200`.

---

## 12. Backups

En **Organization → Resources → Databases → tu clúster → Backups**:

1. Activa **Backups**.
2. Usa retención de **7 días** (preset Prod) o más.
3. Antes de una migración delicada, crea un **New backup** manual.

Los backups se restauran en un **clúster nuevo**, no sobre el actual. Para volver atrás: restaura, adjunta la base restaurada al entorno y redespliega.

---

## 13. Rollback de aplicación

Si un despliegue falla o rompe la app:

1. Ve a **Deployments**.
2. Selecciona el despliegue anterior que funcionaba.
3. Pulsa **Redeploy** / **Rollback**.

Las migraciones **no** se revierten solas. Si el cambio de código depende de una migración destructiva, restaura el backup en un clúster nuevo antes de volver al código anterior.

---

## 14. Verificación tras publicar

- [ ] `GET /up` devuelve `200`.
- [ ] `GET /games` muestra el catálogo y los 12 juegos demo.
- [ ] La búsqueda y los filtros funcionan.
- [ ] `GET /register` permite crear una cuenta.
- [ ] Tras iniciar sesión, aparecen los botones **Editar** y **Eliminar**.
- [ ] Un visitante anónimo es redirigido a `/login` al intentar `/games/create`.
- [ ] Crear, editar y eliminar un juego de prueba funciona.
- [ ] El logout vuelve al catálogo.
- [ ] Los logs del entorno no muestran errores 500.

---

## 15. Límites y decisiones a recordar

- **Filesystem efímero:** no guardes archivos en `storage/` esperando persistencia. La app no sube imágenes; si algún día lo hace, usa Object Storage.
- **SQLite no soportado:** no cambies `DB_CONNECTION` a `sqlite`.
- **Sesiones y caché en base de datos:** no uses `file`.
- **Sin worker de colas:** `QUEUE_CONNECTION=sync`. Si añades colas reales, crea un worker o Managed Queues.
- **Sin scheduler:** no hay tareas programadas. Si añades alguna, activa el toggle **Scheduler**.
- **Una sola región:** entorno y base de datos deben coincidir.
- **Build y deploy ≤ 15 minutos:** si el build crece, revisa dependencias.

---

## 16. Solución de problemas

| Síntoma | Causa probable | Solución |
|---|---|---|
| `No application encryption key` | Falta `APP_KEY` | Añadirla y redesplegar |
| `SQLSTATE[HY000] [2002]` | BD no adjunta o credenciales sobrescritas | Quitar `DB_HOST`/`DB_USERNAME`/`DB_PASSWORD`/`DB_DATABASE` personalizadas y redesplegar |
| `Vite manifest not found` | Build sin `npm run build` | Revisar el build command |
| Health check falla con 404 | `/up` no responde | Comprobar `bootstrap/app.php` y el start command |
| Migración falla por permisos | Usuario incorrecto | Usar el usuario inyectado por Laravel Cloud |
| Los estilos no se actualizan | Caché de assets o build antiguo | Redesplegar y forzar recarga |
| `419 Page Expired` | `APP_KEY` cambió o cookies `secure` en HTTP | Usar HTTPS y mantener la misma `APP_KEY` |
| El catálogo aparece vacío | Seeder no ejecutado | Ejecutar el comando del paso 9 |

---

## 17. Comandos que NO debes ejecutar en producción

```text
php artisan migrate:fresh
php artisan migrate:refresh
php artisan db:wipe
php artisan optimize:clear
php artisan storage:link
php artisan db:seed            # usa --class=GameSeeder y solo una vez
```

Cualquiera de los tres primeros borra datos. Los dos siguientes no persisten en Laravel Cloud y pueden dejar la app en un estado inconsistente.
