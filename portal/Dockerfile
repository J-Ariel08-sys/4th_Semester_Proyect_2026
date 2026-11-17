FROM node:22.14.0-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci --ignore-scripts
COPY vite.config.js ./
COPY resources resources
COPY public public
RUN npm run build

FROM php:8.5-fpm-bookworm@sha256:eec275cd99580e0a6e103dd343a7c160c86917003f6b9bba2d4c75d82419c683 AS dependencies
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libzip-dev libonig-dev libxml2-dev libsqlite3-dev unzip git \
    && docker-php-ext-install pdo_pgsql pdo_sqlite mbstring zip pcntl \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2.8.9 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader

FROM dependencies AS app
COPY --chown=www-data:www-data . .
COPY --from=assets --chown=www-data:www-data /app/public/build public/build
RUN composer dump-autoload --optimize --no-interaction \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/private \
    && chown -R www-data:www-data storage bootstrap/cache \
    && printf 'memory_limit=512M\nexpose_php=Off\n' > /usr/local/etc/php/conf.d/siclin.ini
USER www-data
EXPOSE 9000
CMD ["php-fpm"]

FROM nginx:1.28.0-alpine AS web
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=app /app/public /app/public
