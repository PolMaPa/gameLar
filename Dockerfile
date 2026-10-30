# syntax=docker/dockerfile:1

# =============================================================================
# GameHub · imagen de la aplicación
#
# Arquitectura (teoría de clase):
#   Nginx (estático + reverse proxy FastCGI)  ->  PHP-FPM  ->  MySQL 8.4
#
# Etapas:
#   vendor     dependencias PHP de producción (sin dev) con PHP 8.4 real
#   assets     compilación de CSS/JS con Vite y Node 22
#   runtime    PHP-FPM 8.4 con la aplicación lista
#   webserver  Nginx con los assets ya compilados y la configuración elegida
#
# Un único Dockerfile construye app y Nginx para que ambos usen exactamente
# los mismos assets. El compose de desarrollo y el de producción solo cambian
# el argumento NGINX_CONF y las variables de entorno.
# =============================================================================

ARG PHP_VERSION=8.4
ARG NODE_VERSION=22
ARG NGINX_VERSION=1.27

# -----------------------------------------------------------------------------
# 1) vendor · dependencias PHP de producción
# -----------------------------------------------------------------------------
FROM php:${PHP_VERSION}-cli AS vendor

# Composer exige ext-mbstring (requisito de laravel/framework).
RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev unzip git \
    && docker-php-ext-install mbstring \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

# Se copian primero los manifiestos para aprovechar la caché de capas.
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --no-interaction \
        --prefer-dist \
        --no-progress

COPY . .

# Ejecuta los scripts de Composer (package:discover) sobre el código completo.
RUN composer dump-autoload --no-dev --optimize --no-interaction

# -----------------------------------------------------------------------------
# 2) assets · CSS y JS compilados con Vite
# -----------------------------------------------------------------------------
FROM node:${NODE_VERSION}-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

# -----------------------------------------------------------------------------
# 3) runtime · PHP-FPM 8.4
# -----------------------------------------------------------------------------
FROM php:${PHP_VERSION}-fpm AS runtime

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring opcache bcmath \
    && docker-php-ext-enable opcache \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-gamehub.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint

RUN chmod +x /usr/local/bin/entrypoint \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["php-fpm"]

# -----------------------------------------------------------------------------
# 4) webserver · Nginx con los assets de la etapa anterior
#
# NGINX_CONF=default.conf  -> desarrollo local
# NGINX_CONF=prod.conf     -> producción (VPS)
# -----------------------------------------------------------------------------
FROM nginx:${NGINX_VERSION}-alpine AS webserver

ARG NGINX_CONF=default.conf

COPY --from=assets /app/public /var/www/html/public
COPY docker/nginx/${NGINX_CONF} /etc/nginx/conf.d/default.conf

EXPOSE 80
