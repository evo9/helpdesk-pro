# docker/php/Dockerfile
# Build context: repo root (.)

FROM php:8.4-fpm AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libpq-dev libzip-dev libicu-dev \
    && docker-php-ext-install \
        pdo_pgsql \
        zip \
        intl \
        opcache \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zzz-app.conf
COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# ---------- dev ----------
FROM base AS dev

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]

# ---------- prod ----------
FROM base AS prod

ENV APP_ENV=prod

COPY api/ .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && php bin/console cache:warmup

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]