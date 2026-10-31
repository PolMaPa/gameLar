#!/usr/bin/env bash
#
# GameHub · Despliegue en el VPS (Fases 8 y 12)
#
# Este es el script que ejecuta el CD por SSH y que también se puede lanzar a
# mano para el primer despliegue o para un rollback.
#
# Uso:
#   ./deploy/deploy.sh                 # despliega origin/main
#   ./deploy/deploy.sh <commit-sha>    # despliega (o revierte a) un commit concreto
#
# Variables opcionales:
#   APP_DIR    ruta del repositorio en el VPS       (por defecto /opt/gamehub)
#   REPO_URL   URL del repositorio                  (por defecto GitHub de GameHub)
#   BRANCH     rama a desplegar                     (por defecto main)
#   HEALTH_URL URL de comprobación                  (por defecto http://127.0.0.1/up)
#
# Garantías:
#   - Nunca borra volúmenes: no se usa "docker compose down -v".
#   - Nunca sobrescribe .env.production: si falta, aborta y lo explica.
#   - Actualiza el código con git, reconstruye imágenes, migra y comprueba /up.
#   - Si algo falla, el script termina con código distinto de cero y el CD se
#     marca en rojo; el commit anterior sigue disponible para volver atrás.

set -euo pipefail

APP_DIR="${APP_DIR:-/opt/gamehub}"
REPO_URL="${REPO_URL:-https://github.com/PolMaPa/gameLar.git}"
BRANCH="${BRANCH:-main}"
HEALTH_URL="${HEALTH_URL:-http://127.0.0.1/up}"

COMPOSE=(docker compose --env-file .env.production -f docker-compose.prod.yml)

log() {
    printf '\n==> %s\n' "$*"
}

fail() {
    printf '\nERROR: %s\n' "$*" >&2
    exit 1
}

# Un solo despliegue a la vez (dos pushes seguidos no deben pisarse).
exec 9>/var/lock/gamehub-deploy.lock
if ! flock -n 9; then
    fail "Ya hay un despliegue en curso (lock en /var/lock/gamehub-deploy.lock)."
fi

command -v git >/dev/null 2>&1 || fail "git no está instalado en el VPS."
command -v docker >/dev/null 2>&1 || fail "docker no está instalado en el VPS."
docker compose version >/dev/null 2>&1 || fail "falta el plugin 'docker compose'."

log "Preparando el repositorio en $APP_DIR"

if [ ! -d "$APP_DIR/.git" ]; then
    mkdir -p "$APP_DIR"
    git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
fi

cd "$APP_DIR"

[ -f .env.production ] || fail "No existe $APP_DIR/.env.production.
Créalo una sola vez a partir de .env.production.example:
  cp .env.production.example .env.production
  php artisan key:generate --show   # o cualquier APP_KEY base64 válida
El archivo no se versiona y este script nunca lo sobrescribe."

log "Actualizando referencias desde origin"
git fetch --prune origin "+refs/heads/*:refs/remotes/origin/*"

TARGET="${1:-origin/$BRANCH}"

if ! git rev-parse --verify --quiet "$TARGET^{commit}" >/dev/null; then
    log "El commit $TARGET no está en local; intentando traerlo"
    git fetch origin "$TARGET" || fail "No se pudo obtener '$TARGET' del repositorio."
fi

COMMIT="$(git rev-parse --verify "$TARGET^{commit}")"

log "Desplegando el commit $COMMIT"
git checkout --force --detach "$COMMIT"
git clean -fd -e .env.production

log "Validando la configuración de Compose"
"${COMPOSE[@]}" config --quiet || fail "docker-compose.prod.yml o .env.production no son válidos."

log "Construyendo imágenes"
"${COMPOSE[@]}" build app nginx

log "Levantando contenedores (sin borrar volúmenes)"
"${COMPOSE[@]}" up -d --remove-orphans

log "Esperando a que la aplicación responda"
attempt=1
until curl -fsS --max-time 5 "$HEALTH_URL" >/dev/null 2>&1; do
    if [ "$attempt" -ge 40 ]; then
        "${COMPOSE[@]}" ps || true
        "${COMPOSE[@]}" logs --tail 60 app nginx || true
        fail "La aplicación no respondió en $HEALTH_URL tras 120 segundos."
    fi
    attempt=$((attempt + 1))
    sleep 3
done
log "Aplicación disponible en $HEALTH_URL"

log "Aplicando migraciones"
"${COMPOSE[@]}" exec -T app php artisan migrate --force --no-interaction

log "Regenerando cachés de configuración, rutas y vistas"
"${COMPOSE[@]}" exec -T app php artisan optimize --no-interaction

log "Comprobando el catálogo"
curl -fsS --max-time 10 -o /dev/null -w '    GET /games -> %{http_code}\n' http://127.0.0.1/games \
    || fail "El catálogo no responde correctamente."

printf '%s %s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "$COMMIT" > .last-deploy

# Limpieza conservadora: solo imágenes antiguas, nunca volúmenes.
docker image prune -f --filter "until=168h" >/dev/null 2>&1 || true

log "Despliegue completado"
echo "    Commit:   $COMMIT"
echo "    Estado:   $("${COMPOSE[@]}" ps --format '{{.Service}}={{.State}}' | tr '\n' ' ')"
echo "    Rollback: ./deploy/deploy.sh <commit-anterior>"
