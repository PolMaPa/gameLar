# Despliegue en un VPS Ubuntu

Guía operativa de las **fases 7 a 12**: preparar el servidor, publicar GameHub con Docker Compose y automatizar el despliegue desde GitHub Actions por SSH.

> Resumen de la arquitectura: Nginx es el único servicio público (80/443), habla con PHP-FPM por FastCGI y PHP-FPM con MySQL 8.4 por una red interna. MySQL no publica puertos y sus datos viven en el volumen `gamehub-db-prod`.

---

## 1. Requisitos

- Un VPS con **Ubuntu Server 24.04 LTS** (1 vCPU / 2 GB de RAM es suficiente; el build de Vite necesita algo de memoria).
- Acceso SSH como `root` o un usuario con `sudo`.
- El repositorio `PolMaPa/gameLar` es público: el VPS puede clonarlo sin token.
- Un dominio **solo si** se quiere HTTPS (fase 8). Sin dominio se publica por HTTP con la IP.

No se necesita ningún servicio de pago adicional: ni PaaS, ni base de datos gestionada, ni registro de imágenes.

---

## 2. Fase 7 · Aprovisionar el servidor

### 2.1 Entrar por SSH

```bash
ssh root@IP_DEL_VPS
```

### 2.2 Crear el usuario de despliegue

```bash
adduser deploy
usermod -aG sudo deploy
```

Añade **tu clave pública** (la misma que usarás desde GitHub Actions) a la cuenta:

```bash
mkdir -p /home/deploy/.ssh
cp /root/.ssh/authorized_keys /home/deploy/.ssh/authorized_keys
chown -R deploy:deploy /home/deploy/.ssh
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys
```

> El script de aprovisionamiento **no toca SSH a propósito**, para no dejarte fuera del servidor.

### 2.3 Ejecutar el aprovisionamiento

Desde el repositorio ya clonado, o copiando el script:

```bash
apt-get update -y && apt-get install -y git
git clone https://github.com/PolMaPa/gameLar.git /opt/gamehub
cd /opt/gamehub
sudo bash deploy/provision.sh deploy
```

El script instala Docker Engine y Compose, añade `deploy` al grupo `docker`, abre solo **22/80/443** con UFW y crea `/opt/gamehub`. Es idempotente.

Cierra la sesión y vuelve a entrar para que el grupo `docker` tenga efecto.

### 2.4 Crear `.env.production` (una sola vez)

```bash
cd /opt/gamehub
cp .env.production.example .env.production
```

Genera los secretos **en el propio servidor** y pégalos en el archivo:

```bash
# APP_KEY (base64:...)
docker run --rm php:8.4-cli php -r 'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;'

# Contraseñas de MySQL
openssl rand -base64 24   # DB_PASSWORD
openssl rand -base64 24   # DB_ROOT_PASSWORD
```

Valores que **deben** quedar así en producción:

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.example     # o http://IP_DEL_VPS mientras no haya dominio
APP_KEY=base64:...                      # no cambiar entre despliegues
DB_HOST=db
DB_DATABASE=gamehub
DB_USERNAME=gamehub
DB_PASSWORD=...
DB_ROOT_PASSWORD=...
SESSION_SECURE_COOKIE=true              # false solo si pruebas por HTTP sin TLS
RUN_MIGRATIONS=false
```

```bash
chmod 600 .env.production
```

> `.env.production` está ignorado por Git. El script de despliegue **nunca lo sobrescribe** y los volúmenes **nunca se borran**.

### 2.5 Primer despliegue manual

```bash
cd /opt/gamehub
./deploy/deploy.sh
```

Comprueba:

```bash
curl -fsS http://127.0.0.1/up
curl -fsS -o /dev/null -w '%{http_code}\n' http://127.0.0.1/games
docker compose --env-file .env.production -f docker-compose.prod.yml ps
```

### 2.6 Crear la cuenta administradora

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml \
  exec app php artisan gamehub:admin admin@tu-dominio.example --name="Administrador GameHub"
```

El comando pide la contraseña de forma oculta. No hay contraseñas en el repositorio.

---

## 3. Fase 8 · Dominio y HTTPS (opcional)

1. Crea un registro **A** `gamehub.tu-dominio.example → IP_DEL_VPS`.
2. Instala Certbot y obtén el certificado (con Nginx parado o usando el modo `standalone` en el puerto 80):

   ```bash
   apt-get install -y certbot
   docker compose --env-file .env.production -f docker-compose.prod.yml stop nginx
   certbot certonly --standalone -d gamehub.tu-dominio.example
   ```

3. Descomenta el bloque 443 de `docker/nginx/prod.conf`, monta `/etc/letsencrypt` en el servicio `nginx` y publica el puerto 443 en `docker-compose.prod.yml`.
4. Renueva con un cron diario: `certbot renew --quiet && docker compose ... restart nginx`.

Sin dominio, el ejercicio funciona igual por HTTP; solo hay que dejar `SESSION_SECURE_COOKIE=false` y `APP_URL=http://IP_DEL_VPS`.

---

## 4. Fase 12 · CD por SSH desde GitHub Actions

### 4.1 Clave de despliegue específica

En tu máquina:

```powershell
ssh-keygen -t ed25519 -C "github-actions-gamehub" -f $env:USERPROFILE\.ssh\gamehub_deploy -N '""'
```

Añade la **pública** al VPS:

```bash
# en el VPS, como usuario deploy
echo "ssh-ed25519 AAAA... github-actions-gamehub" >> ~/.ssh/authorized_keys
```

Guarda la **privada** en GitHub → *Settings → Secrets and variables → Actions*:

| Secreto | Valor |
|---|---|
| `VPS_HOST` | IP o dominio del VPS |
| `VPS_USER` | `deploy` |
| `VPS_PORT` | `22` (opcional) |
| `VPS_SSH_KEY` | contenido completo de `gamehub_deploy` (clave privada) |

Nunca se publica la clave privada ni se escribe en el repositorio.

### 4.2 Workflow

El workflow de CD (`.github/workflows/cd.yml`) se activa **solo** cuando el CI termina en verde para `main`, se conecta por SSH y ejecuta:

```bash
cd /opt/gamehub && ./deploy/deploy.sh <commit>
```

Si el despliegue falla, el commit anterior sigue en el servidor y se puede revertir con:

```bash
cd /opt/gamehub && ./deploy/deploy.sh <commit-anterior>
```

---

## 5. Operación diaria

```bash
cd /opt/gamehub

# Estado y logs
docker compose --env-file .env.production -f docker-compose.prod.yml ps
docker compose --env-file .env.production -f docker-compose.prod.yml logs -f --tail 100 app nginx

# Copia de seguridad de la base de datos (sin parar el servicio)
docker compose --env-file .env.production -f docker-compose.prod.yml \
  exec -T db sh -c 'exec mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" --single-transaction "$MYSQL_DATABASE"' \
  > /root/gamehub-$(date +%F).sql
```

> `docker compose down` conserva los volúmenes. **`down -v` los borra**: no usarlo en producción.

---

## 6. Problemas frecuentes

| Síntoma | Causa / solución |
|---|---|
| `permission denied` al usar Docker | Vuelve a iniciar sesión o `newgrp docker`. |
| El script aborta: «No existe .env.production» | Créalo desde `.env.production.example` (paso 2.4). Es intencionado. |
| `502 Bad Gateway` tras desplegar | `docker compose ... logs app`; el entrypoint espera a MySQL y valida `APP_KEY`. |
| `419 Page Expired` | `APP_KEY` cambió o `SESSION_SECURE_COOKIE=true` sobre HTTP. |
| El puerto 80 está ocupado | Para el servicio que lo use (por ejemplo `apache2` o `nginx` del sistema) y reintenta. |
| El build se queda sin memoria | Añade swap: `fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile`. |
