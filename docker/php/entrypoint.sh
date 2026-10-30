#!/bin/sh
#
# Punto de entrada del contenedor PHP-FPM.
#
# Responsabilidades:
#   1. Validar que APP_KEY existe en producción.
#   2. Esperar a que MySQL acepte conexiones (evita fallos de arranque).
#   3. Preparar permisos de storage/ y bootstrap/cache/.
#   4. Ejecutar migraciones si RUN_MIGRATIONS=true.
#   5. Cachear configuración/rutas/vistas si APP_OPTIMIZE=true.
#   6. Arrancar el proceso principal (php-fpm).
#
# El script es idempotente: se puede reiniciar el contenedor sin efectos raros.

set -eu

APP_ENV="${APP_ENV:-production}"
DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"

log() {
    printf '[entrypoint] %s\n' "$*"
}

if [ "${APP_ENV}" = "production" ] && [ -z "${APP_KEY:-}" ]; then
    log "ERROR: APP_KEY no está definida. Genera una con 'php artisan key:generate --show'."
    exit 1
fi

log "Esperando a MySQL en ${DB_HOST}:${DB_PORT} ..."
attempt=1
while ! php -r 'exit(@fsockopen(getenv("DB_HOST") ?: "db", (int) (getenv("DB_PORT") ?: 3306)) ? 0 : 1);' >/dev/null 2>&1; do
    if [ "${attempt}" -ge 30 ]; then
        log "ERROR: MySQL no respondió en 60 segundos."
        exit 1
    fi
    attempt=$((attempt + 1))
    sleep 2
done
log "MySQL disponible."

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    log "Ejecutando migraciones (php artisan migrate --force) ..."
    php artisan migrate --force --no-interaction
fi

if [ "${APP_OPTIMIZE:-false}" = "true" ]; then
    log "Cacheando configuración, rutas y vistas (php artisan optimize) ..."
    php artisan optimize --no-interaction
fi

# Los comandos anteriores se ejecutan como root: devolvemos la propiedad a
# www-data para que PHP-FPM pueda escribir logs y cachés.
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

log "Iniciando: $*"
exec "$@"
