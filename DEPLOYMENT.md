# Despliegue en Laravel Cloud

> **Documento histórico:** esta guía describe la vía Laravel Cloud que se usó en una
> fase anterior del proyecto. El ejercicio **ya no utiliza Laravel Cloud**: el
> despliegue actual y futuro es un **VPS Ubuntu con Docker Compose** (ver
> [`docs/DOCKER.md`](docs/DOCKER.md)). Se conserva solo como referencia.

Guía operativa para publicar **GameHub** en [Laravel Cloud](https://cloud.laravel.com) con **Laravel MySQL**. Está escrita para ejecutarse desde el panel web (sin SSH) y documenta también el flujo **CI/CD** con GitHub Actions.

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
- [ ] `.github/workflows/ci.yml` (pruebas)
- [ ] `.github/workflows/cd.yml` (despliegue tras CI en verde)
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

### Cómo se conecta Laravel Cloud con GitHub

Al crear la aplicación, Laravel Cloud instala su GitHub App en el repositorio y configura un webhook. A partir de ahí puede recibir los pushes de la rama `main`.

Laravel Cloud ofrece dos mecanismos oficiales:

| Mecanismo | Qué hace | ¿Espera al CI? |
|---|---|---|
| **Push to deploy** | Despliega en cada push a la rama conectada | No |
| **Deploy hook** | Despliega cuando un pipeline hace `POST` a una URL | Sí, si el pipeline lo invoca tras las pruebas |

Para este proyecto usamos **solo el deploy hook**, invocado por GitHub Actions cuando el CI termina en verde. **Push to deploy debe quedar desactivado** para no desplegar commits que aún no han pasado las pruebas. La configuración exacta está en el **apartado 8**.

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
- No añadas `db:seed` al deploy: los datos demo se siembran una sola vez (paso 10).

---

## 8. CI/CD: de GitHub a producción

### Arquitectura

```text
push a main / pull request
        │
        ▼
CI · GitHub Actions
  ├─ Pruebas y compilación (SQLite + Pint + Vite)
  └─ Compatibilidad MySQL 8.4
        │
        │ solo si el evento fue push a main y TODO el CI pasó
        ▼
CD · GitHub Actions
  └─ POST al deploy hook de Laravel Cloud con el SHA exacto
        │
        ▼
Laravel Cloud
  ├─ build (composer, npm, optimize)
  ├─ migrate --force
  ├─ health check /up
  └─ publicación con zero downtime
```

| | CI | CD |
|---|---|---|
| Archivo | `.github/workflows/ci.yml` | `.github/workflows/cd.yml` |
| Disparador | push a `main`, pull request, manual | `workflow_run` cuando termina **CI** |
| Objetivo | verificar el código | publicar en producción |
| Qué ejecuta | PHPUnit, Pint, build de Vite, MySQL 8.4 | `POST` al deploy hook |
| Secretos | ninguno | `LARAVEL_CLOUD_DEPLOY_HOOK` |
| Si falla | no se despliega | Laravel Cloud no publica |

El CD **no** instala dependencias, no compila assets y no ejecuta migraciones: todo eso lo hace Laravel Cloud con el commit indicado. Así no duplicamos el mecanismo de despliegue.

### Por qué deploy hook y no push to deploy

Laravel Cloud tiene **Push to deploy** activado por defecto y despliega en cuanto recibe el push. No espera a que terminen los checks de GitHub. Para cumplir la regla «si el CI falla, no se despliega», usamos el **deploy hook** oficial, invocado por el CD solo cuando el CI ha terminado en verde.

> Solo debe quedar **un mecanismo activo**: el deploy hook. Si dejas Push to deploy activado, un push fallido podría desplegarse igualmente y además se lanzarían dos despliegues por commit.

### Configuración inicial (una sola vez)

1. **Comprobar la conexión en Laravel Cloud**
   - Entra en el entorno de producción.
   - En **Settings → General**, confirma que el repositorio es `PolMaPa/gameLar` y la rama `main`.
   - Si no coincide, corrígelo antes de continuar.

2. **Desactivar Push to deploy**
   - **Settings → Deployments**.
   - Desactiva **Push to deploy**.

3. **Activar el deploy hook**
   - En la misma pantalla, activa **Deploy hook**.
   - Copia la URL generada. Es un **secreto**: quien la tenga puede desplegar.
   - No la pegues en el repositorio, en un `.env` versionado ni en la documentación.

4. **Guardar la URL como secreto de GitHub**
   - Repositorio → **Settings → Secrets and variables → Actions**.
   - **New repository secret**.
   - Nombre: `LARAVEL_CLOUD_DEPLOY_HOOK`.
   - Valor: la URL del deploy hook.
   - GitHub la oculta en los logs y solo la exponen los workflows del repositorio.

5. **Protección opcional de la rama `main`**
   - Repositorio → **Settings → Branches → Add branch protection rule**.
   - Exige pull request y los checks `Pruebas y compilación` y `Compatibilidad MySQL 8.4`.
   - Así solo llega a `main` código revisado y con CI en verde.

6. **Aprobación opcional del despliegue**
   - Repositorio → **Settings → Environments → production**.
   - Añade **Required reviewers** si quieres que un despliegue espere aprobación manual después del CI.
   - Sin revisores, el CD es completamente automático tras el CI.
   - El workflow ya declara `environment: production`, así que GitHub registra cada despliegue.

### Cómo funciona el workflow de CD

`cd.yml` se activa con `workflow_run` cuando termina el workflow llamado exactamente **CI**. El job solo se ejecuta si:

- `conclusion == 'success'` → todas las pruebas pasaron,
- `event == 'push'` → no es una pull request,
- `head_branch == 'main'` → es la rama de producción.

Después hace:

```sh
curl -X POST "<deploy-hook>?commit_hash=<sha-del-ci>"
```

El `commit_hash` garantiza que se despliega exactamente el commit que pasó las pruebas, no otro más reciente. La concurrencia `cd-production` evita que dos despliegues se solapen.

> No renombres el workflow `CI`: el CD lo busca por ese nombre.

### Comprobación de extremo a extremo

1. Haz un cambio pequeño y visible (por ejemplo, el texto del footer).
2. Ejecuta en local `php artisan test`, `vendor\bin\pint --test` y `npm run build`.
3. Haz commit y `git push origin main`.
4. En GitHub → **Actions**, comprueba que **CI** se ejecuta y termina en verde.
5. Al terminar, debe aparecer automáticamente una ejecución **CD · Laravel Cloud**.
6. En Laravel Cloud → **Deployments**, verifica que hay un despliegue nuevo con el mismo SHA.
7. Espera a que el health check `/up` pase y abre la URL pública:
   ```powershell
   curl.exe -I https://tu-entorno.laravel.cloud/up
   curl.exe -s https://tu-entorno.laravel.cloud | Select-String 'Laravel Cloud'
   ```
8. Confirma que el cambio visible aparece en la web.

### Si algo no ocurre

| Síntoma | Causa probable | Solución |
|---|---|---|
| No aparece ejecución CD | El CI falló o fue una PR | Corregir el CI; el CD solo va tras un push a `main` con éxito |
| CD falla con «Falta el secreto» | Secreto no creado o mal nombrado | Crear `LARAVEL_CLOUD_DEPLOY_HOOK` |
| El hook responde 404/401 | URL regenerada o desactivada | Volver a copiarla y actualizar el secreto |
| Se lanzan dos despliegues | Push to deploy sigue activo | Desactivarlo en Laravel Cloud |
| El despliegue falla en Laravel Cloud | Build, migración o health check | Revisar los logs del despliegue; CI no valida el entorno real |
| El CD no se dispara tras renombrar | El workflow ya no se llama `CI` | Mantener el nombre o actualizar `workflows: ["CI"]` |

### Seguridad del deploy hook

- La URL del hook es una credencial: no se versiona ni se imprime.
- Si se filtra, usa **Refresh** en Laravel Cloud y actualiza el secreto de GitHub.
- El CD solo se ejecuta desde `main`, no desde pull requests ni forks.
- Para despliegues manuales de emergencia existe el botón **Deploy** de Laravel Cloud, pero **se salta el CI**: úsalo solo para rollback o recuperación.

---

## 9. Health checks

En **Settings → Deployments → Health checks**:

| Check | Endpoint |
|---|---|
| Startup | `GET /up` |
| Readiness | `GET /up` |
| Liveness | Dejar el valor por defecto |

`/up` ya está registrado en `bootstrap/app.php`. Un despliegue solo pasa a producción cuando responde correctamente.

---

## 10. Sembrar los 12 juegos demo (una sola vez)

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

## 11. Crear la cuenta administradora

1. Abre `https://tu-entorno.laravel.cloud/register`.
2. Crea tu cuenta.
3. Inicia sesión: ya puedes añadir, editar y eliminar juegos.

El registro es **público** por decisión de producto: cualquier visitante puede crear una cuenta y, con ella, gestionar el catálogo. Si más adelante quieres cerrarlo:

- Comenta las rutas `GET/POST register` en `routes/auth.php`, o
- Añade una allowlist de correos en `RegisteredUserController`.

---

## 12. Dominio y TLS

1. En **Settings → Domains**, añade tu dominio (por ejemplo `gamehub.example`).
2. Copia los registros DNS que indique Laravel Cloud en tu proveedor.
3. Espera la verificación; el certificado TLS se emite automáticamente.
4. Actualiza `APP_URL=https://tu-dominio.example` y redespliega.
5. Verifica que `https://tu-dominio.example/up` responde `200`.

---

## 13. Backups

En **Organization → Resources → Databases → tu clúster → Backups**:

1. Activa **Backups**.
2. Usa retención de **7 días** (preset Prod) o más.
3. Antes de una migración delicada, crea un **New backup** manual.

Los backups se restauran en un **clúster nuevo**, no sobre el actual. Para volver atrás: restaura, adjunta la base restaurada al entorno y redespliega.

---

## 14. Rollback de aplicación

Si un despliegue falla o rompe la app:

1. Ve a **Deployments**.
2. Selecciona el despliegue anterior que funcionaba.
3. Pulsa **Redeploy** / **Rollback**.

Las migraciones **no** se revierten solas. Si el cambio de código depende de una migración destructiva, restaura el backup en un clúster nuevo antes de volver al código anterior.

---

## 15. Verificación tras publicar

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

## 16. Límites y decisiones a recordar

- **Filesystem efímero:** no guardes archivos en `storage/` esperando persistencia. La app no sube imágenes; si algún día lo hace, usa Object Storage.
- **SQLite no soportado:** no cambies `DB_CONNECTION` a `sqlite`.
- **Sesiones y caché en base de datos:** no uses `file`.
- **Sin worker de colas:** `QUEUE_CONNECTION=sync`. Si añades colas reales, crea un worker o Managed Queues.
- **Sin scheduler:** no hay tareas programadas. Si añades alguna, activa el toggle **Scheduler**.
- **Una sola región:** entorno y base de datos deben coincidir.
- **Build y deploy ≤ 15 minutos:** si el build crece, revisa dependencias.

---

## 17. Solución de problemas

| Síntoma | Causa probable | Solución |
|---|---|---|
| `No application encryption key` | Falta `APP_KEY` | Añadirla y redesplegar |
| `SQLSTATE[HY000] [2002]` | BD no adjunta o credenciales sobrescritas | Quitar `DB_HOST`/`DB_USERNAME`/`DB_PASSWORD`/`DB_DATABASE` personalizadas y redesplegar |
| `Vite manifest not found` | Build sin `npm run build` | Revisar el build command |
| Health check falla con 404 | `/up` no responde | Comprobar `bootstrap/app.php` y el start command |
| Migración falla por permisos | Usuario incorrecto | Usar el usuario inyectado por Laravel Cloud |
| Los estilos no se actualizan | Caché de assets o build antiguo | Redesplegar y forzar recarga |
| `419 Page Expired` | `APP_KEY` cambió o cookies `secure` en HTTP | Usar HTTPS y mantener la misma `APP_KEY` |
| El catálogo aparece vacío | Seeder no ejecutado | Ejecutar el comando del paso 10 |

---

## 18. Comandos que NO debes ejecutar en producción

```text
php artisan migrate:fresh
php artisan migrate:refresh
php artisan db:wipe
php artisan optimize:clear
php artisan storage:link
php artisan db:seed            # usa --class=GameSeeder y solo una vez
```

Cualquiera de los tres primeros borra datos. Los dos siguientes no persisten en Laravel Cloud y pueden dejar la app en un estado inconsistente.
